<?php

namespace App\Controller;

use App\Entity\Carrito;
use App\Entity\Cupon;
use App\Entity\Estados;
use App\Entity\Factura;
use App\Entity\GeneralesApp;
use App\Entity\Login;
use App\Entity\MetodosEnvio;
use App\Entity\MetodosPago;
use App\Entity\Pedidos;
use App\Entity\Productos;
use App\Entity\Tiendas;
use App\Entity\Usuarios;
use App\Entity\UsuariosDirecciones;
use App\Entity\Variaciones;
use App\Form\PayMetodType;
use App\Service\CarritoService;
use App\Service\DynamicMailerFactory;
use App\Service\GuardarPedidoService;
use App\Service\PaypalService;
use App\Service\PlacetoPayService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use App\Interfaces\ErrorsInterface;

final class VentasController extends AbstractController
{

    private $em;

    private $request;

    private $placetoPayService;

    private $paypalService;

    private $carritoService;

    private $mailer;

    private $guardar_pedido;

    private $errorsInterface;

    public function __construct(EntityManagerInterface $em, RequestStack $request, PlacetoPayService $placetoPayService, PaypalService $paypalService, CarritoService $carritoService, DynamicMailerFactory $mailer, GuardarPedidoService $guardar_pedido, ErrorsInterface $errorsInterface)
    {
        $this->em = $em;  // Injecting EntityManager into the controller.
        $this->request = $request->getCurrentRequest();  // Injecting RequestStack into the controller.
        $this->placetoPayService = $placetoPayService;  // Injecting PlacetoPayService into the controller.
        $this->paypalService = $paypalService;  // Injecting PaypalService into the controller.
        $this->carritoService = $carritoService;  // Injecting CarritoService into the controller.
        $this->mailer = $mailer;  // Injecting MailerInterface into the controller.
        $this->guardar_pedido = $guardar_pedido;  // Injecting GuardarPedidoService into the controller.
        $this->errorsInterface = $errorsInterface;

    }

    #[Route('/api/generar_venta', name: 'app_generar_venta', methods: ['POST'])]
    #[OA\Tag(name: 'Pagos')]
    #[OA\Response(
        response: 200,
        description: 'Generar venta en Shopby',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Venta generada con éxito.'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'url', type: 'string'),

                ]),
            ]
        )
    )]
    #[OA\Response(
        response: 409,
        description: 'Error al generar la venta.(para ventas en estados pedientes).',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Error en la validación de los datos'),
                new OA\Property(property: 'errors', type: 'array', items: new OA\Items(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'description', type: 'string'),
                        new OA\Property(property: 'action', type: 'string', example: 'Url de redireccion pago pendiente.'),
                    ]
                )),
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Error al generar la venta.(todo el resto de errores).',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Error en la validación de los datos'),
                new OA\Property(property: 'errors', type: 'array', items: new OA\Items(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'description', type: 'string')
                    ]
                )),
            ]
        )
    )]
    #[OA\RequestBody(
        description: 'Formulario de pago',
        content: new Model(type: PayMetodType::class)
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function pago(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Login) {
            return $this->errorsInterface->error_message('No está autenticado', Response::HTTP_UNAUTHORIZED);
        }

        $form = $this->createForm(PayMetodType::class);
        $form->handleRequest($this->request);

        if (!$form->isValid()) {
            return $this->errorsInterface->form_error($form, 'Error en la validación de los datos del pago.');
        }

        $metodo_pago = $form->get('metodo_pago')->getData();
        $facturaID = $form->get('factura_id')->getData();
        $direccion = $form->get('direccion_id')->getData();
        $metodo_envio = $form->get('metodo_envio')->getData();
        $codigo_cupon = $form->get('codigo_cupon')->getData();
        $pago_mixto = $form->has('pago_mixto') ? (bool) $form->get('pago_mixto')->getData() : false;

        if ($metodo_pago instanceof MetodosPago) {
            $idMetodo = $metodo_pago->getId();
        } else {
            return $this->errorsInterface->error_message('Error metodo de pago.', 400, 'description', 'Método de pago inválido');
        }

        if ($metodo_envio) {
            if ($metodo_envio instanceof MetodosEnvio) {
                $nombre_metodo_envio = $metodo_envio->getNombre();
            } else {
                $nombre_metodo_envio = 'Sin Metodo de envio.';
            }
        } else {
            $metodo_envio = null;
            $nombre_metodo_envio = 'Sin Metodo de envio.';
        }

        $front_url = $this->em->getRepository(GeneralesApp::class)->findOneBy(['nombre' => 'front', 'atributoGeneral' => 'Url']);

        $pedidosPendientes = $this->em->getRepository(Pedidos::class)
            ->findPedidosPendientesConPrefijo($user, $metodo_pago);

        if (!empty($pedidosPendientes)) {
            $nVentasPendientes = array_map(function ($pedido) {
                return [
                    'n_venta' => $pedido->getNVenta(),
                    'tipo_pago' => $pedido->getMetodoPago() ? $pedido->getMetodoPago()->getId() : '',
                    'url_pago' => $pedido->getUrlPago() ?: null,
                    'fecha_pedido' => $pedido->getFechaPedido()->format('Y-m-d H:i:s'),
                    'metodo_pago' => $pedido->getMetodoPago() ? $pedido->getMetodoPago()->getNombre() : '',
                ];
            }, $pedidosPendientes);

            $errorMessage = 'Tienes compras en estado pendiente.';
            $uniqueNVenta = $nVentasPendientes[0];
            $url_retorno = ($uniqueNVenta['url_pago'] ?? null)
                ?? $front_url->getValorGeneral() . '/checkout/resumen/' . ($uniqueNVenta['n_venta'] ?? '');

            $error = [];

            if ($uniqueNVenta['tipo_pago'] == 3) {
                $url = $this->ver_orden2($uniqueNVenta['n_venta']);
                $data_url = json_decode($url->getContent(), true);
                if ($data_url) {
                    $url_retorno = $data_url;
                }
                $error[] = [
                    'description' => 'Ya tienes una compra pendiente con el número de venta: ' . $uniqueNVenta['n_venta'] . ' con ' . $uniqueNVenta['metodo_pago'],
                    'action' => $url_retorno,
                ];
            } elseif ($uniqueNVenta['tipo_pago'] == 1) {
                $error[] = [
                    'description' => 'Ya tienes una compra pendiente con el número de venta: ' . $uniqueNVenta['n_venta'] . ' con ' . $uniqueNVenta['metodo_pago'],
                    'action' => $front_url->getValorGeneral() . '/checkout/deposito/' . $uniqueNVenta['n_venta'],
                ];
            } elseif ($uniqueNVenta['tipo_pago'] == 2) {
                $error[] = [
                    'description' => 'Ya tienes una compra pendiente con el número de venta: ' . ($uniqueNVenta['n_venta'] ?? 'N/A') . ' con ' . $uniqueNVenta['metodo_pago'],
                    'action' => $url_retorno,
                ];
            } else {
                $error[] = [
                    'description' => 'Ya tienes una compra pendiente con el número de venta: ' . $uniqueNVenta['n_venta'],
                    'action' => $front_url->getValorGeneral() . '/checkout/resumen/' . $uniqueNVenta['n_venta'],
                ];
            }

            return $this->errorsInterface->error_message($errorMessage, Response::HTTP_CONFLICT, null, $error);
        }

        $usuario = $this->em->getRepository(Usuarios::class)->findOneBy(['login' => $user]);
        if (!$usuario) {
            return $this->errorsInterface->error_message('El usuario no existe.', Response::HTTP_BAD_REQUEST, 'description', 'El usuario no existe en la base de datos.');
        }

        $nombre = $usuario->getNombre();
        $apellido = $usuario->getApellido();
        $email = $usuario->getEmail();
        $documento = $usuario->getTipoDocumento();
        $telefono = $usuario->getCelular();
        $dni = $usuario->getDni();

        $camposFaltantes = [];
        if (!$nombre)
            $camposFaltantes[] = 'nombre';
        if (!$email)
            $camposFaltantes[] = 'email';
        if (!$documento)
            $camposFaltantes[] = 'documento';
        if (!$telefono)
            $camposFaltantes[] = 'teléfono';
        if (!$dni)
            $camposFaltantes[] = 'DNI';

        $customer = $nombre . ' ' . $apellido;

        if (!empty($camposFaltantes)) {
            $mensajeError = 'Faltan los siguientes datos para proceder con la compra.';
            $error[] = [
                'description' => 'Los siguientes campos están vacíos: ' . implode(', ', $camposFaltantes),
            ];

            return $this->errorsInterface->error_message(
                $mensajeError,
                417,
                null,
                $error
            );
        }

        $direcciones = $this->em->getRepository(UsuariosDirecciones::class)->findOneBy(['id' => $direccion, 'usuario' => $usuario]);

        $ciudad_usuario = null;
        $provincia_usuario = null;
        $region_usuario = null;
        $latitud_usuario = null;
        $longitud_usuario = null;
        $direcion_principal_usuario = '';
        $direcion_secundaria_usuario = '';
        $referencia_usuario = '';
        $id_servientrega = null;
        $codigo_postal_customer = '';

        if ($direcciones !== null) {
            $ciudad_usuario = $direcciones->getCiudad() ? $direcciones->getCiudad()->getCiudad() : null;
            $provincia_usuario = $direcciones->getCiudad() ? $direcciones->getCiudad()->getProvincia()->getProvincia() : null;
            $region_usuario = $direcciones->getCiudad() ? $direcciones->getCiudad()->getProvincia()->getRegion() : null;
            $latitud_usuario = $direcciones->getLatitud() ? $direcciones->getLatitud() : null;
            $longitud_usuario = $direcciones->getLongitud() ? $direcciones->getLongitud() : null;
            $direcion_principal_usuario = $direcciones->getDireccionP() ? $direcciones->getDireccionP() : '';
            $direcion_secundaria_usuario = $direcciones->getDireccionS() ? $direcciones->getDireccionS() : '';
            $referencia_usuario = $direcciones->getReferenciaDireccion() ? $direcciones->getReferenciaDireccion() : '';
            $id_servientrega = $direcciones->getCiudad()->getIdServientrega();
            $codigo_postal_customer = $direcciones->getCodigoPostal() ? $direcciones->getCodigoPostal() : '';
        }

        $factura = $this->em->getRepository(Factura::class)->findOneBy(['login' => $user]);
        if (($facturaID === null && $factura === null)) {
            $factura = new Factura();
            $factura->setEmail($user->getEmail());
            $factura->setLogin($user);
            $factura->setNombre($user->getUsuarios() ? $user->getUsuarios()->getNombre() : '');
            $factura->setApellido($user->getUsuarios() ? $user->getUsuarios()->getApellido() : '');
            $factura->setTelefono($user->getUsuarios() ? $user->getUsuarios()->getCelular() : '');
            $factura->setDni($user->getUsuarios() ? $user->getUsuarios()->getDni() : '');

            $this->em->persist($factura);
            $this->em->flush();
        }

        try {
            $carrito = $this->carritoService->carito($user, $metodo_pago, $direccion, $metodo_envio, $codigo_cupon);

            if ($carrito->getStatusCode() !== Response::HTTP_OK) {
                return $carrito;
            }

            $data = json_decode($carrito->getContent(), true);
            $roundRecursive = function (&$array) use (&$roundRecursive) {
                foreach ($array as $key => &$value) {
                    if (is_array($value)) {
                        $roundRecursive($value);
                    } elseif (is_numeric($value)) {
                        $value = round((float) $value, 2);
                    }
                }
            };

            $roundRecursive($data);
            $api_response = null;
            $request_id = null;
            $c_descuento = 0;
            $url = null;
            $subtotal = $data['subtotal'];
            $iva = $data['iva'];
            $iva_aplicado = $data['iva_aplicado'];
            $costo_envio = $data['costo_envio'];
            $calculo_paypal = $data['calculo_paypal'];
            $total = $data['total'];
            $total_original = $data['subtotal_original'];

        } catch (Exception $e) {
            return $this->errorsInterface->error_message('Error al obtener los datos del carrito.', Response::HTTP_INTERNAL_SERVER_ERROR, 'description', $e->getMessage());
        }


        $totalGeneral = $total;

        $ingresado = $this->em->getRepository(Estados::class)->findOneBy(['id' => 19]);

        // SUMAR SUBTOTAL POR CADA TIENDA (ya vienen agrupados)
        $total_por_tienda = [];

        // SUMAR subtotal_original *por producto* dentro de cada tienda
        foreach ($data['productos_por_vendedor'] as $tienda) {

            $totalTienda = 0;

            foreach ($tienda['productos'] as $producto) {
                if (isset($producto['subtotal_original'])) {
                    $totalTienda += (float) $producto['subtotal_original'];
                }
            }

            $total_por_tienda[] = $totalTienda;
        }

        $cupon = $this->em->getRepository(Cupon::class)
            ->findOneBy(['codigo_cupon' => $codigo_cupon]);

        $gastoMinimo = $cupon ? $cupon->getGastoMinimo() : null;

        // VALIDACIÓN
        if ($cupon && $gastoMinimo !== null) {

            $fallaGeneral = ($total_original > 0 && $total_original < $gastoMinimo);

            $fallaTienda = false;
            foreach ($total_por_tienda as $totalTienda) {
                if ($totalTienda > 0 && $totalTienda < $gastoMinimo) {
                    $fallaTienda = true;
                    break;
                }
            }

            if ($fallaGeneral || $fallaTienda) {
                return $this->errorsInterface->error_message(
                    'Error con el cupón.',
                    Response::HTTP_BAD_REQUEST,
                    'description',
                    'No se cumple el gasto mínimo para usar el cupón.'
                );
            }
        }



        $tipoEnvioEnCarrito = [];
        foreach ($data['productos_sin_agrupar'] as $carritoItem) {
            $tipo_envio_carrito = $carritoItem['tipo_entrega'];
            $tipoEnvioEnCarrito[] = $tipo_envio_carrito;
        }

        $tipoEnvio = 'SIN TIPO DE ENVIO';
        if (in_array('A DOMICILIO', $tipoEnvioEnCarrito) && in_array('RETIRO EN TIENDA FISICA', $tipoEnvioEnCarrito)) {
            $tipoEnvio = 'AMBOS';
        } elseif (in_array('A DOMICILIO', $tipoEnvioEnCarrito)) {
            $tipoEnvio = 'A DOMICILIO';
        } elseif (in_array('RETIRO EN TIENDA FISICA', $tipoEnvioEnCarrito)) {
            $tipoEnvio = 'RETIRO EN TIENDA FISICA';
        }

        if ($metodo_envio == null && ($tipoEnvio === 'A DOMICILIO' || $tipoEnvio === 'AMBOS')) {
            return $this->errorsInterface->error_message('Seleccione un metodo de envio.', 400);
        }

        if ($direcciones == null && ($tipoEnvio === 'A DOMICILIO' || $tipoEnvio === 'AMBOS')) {
            return $this->errorsInterface->error_message('Seleccione una dirección de entrega.', 400);
        }

        if ($direcciones && ($tipoEnvio === 'A DOMICILIO' || $tipoEnvio === 'AMBOS')) {
            if ($direcion_principal_usuario == null || $direcion_principal_usuario == '') {
                return $this->errorsInterface->error_message('Error en la dirección.', 400, 'description', 'La dirección principal no pueden estar vacía.');
            }

            if ($direcion_secundaria_usuario == null || $direcion_secundaria_usuario == '') {
                return $this->errorsInterface->error_message('Error en la dirección.', 400, 'description', 'La dirección secundaria no pueden estar vacía.');
            }
        }

        $p = null;
        $n_venta = 'V-' . rand(0000, 9999);
        $return_url = $front_url->getValorGeneral() . "/checkout/resumen/" . $n_venta;

        // ********************************************************************
        // Validación general del total
        if ($total === null) {
            return $this->errorsInterface->error_message('Error en el total a pagar', Response::HTTP_BAD_REQUEST, 'description', 'No hay valor a pagar');
        }
        // ********************************************************************

        // Revisar si hay producto tipo 3 en todo el carrito (no permitido con saldo)
        $hayProductoTipo3 = false;
        foreach ($data['productos_por_vendedor'] as $pedidoCheck) {
            foreach ($pedidoCheck['productos'] as $productoCheck) {
                if (isset($productoCheck['tipo_producto']) && (int) $productoCheck['tipo_producto'] === 3) {
                    $hayProductoTipo3 = true;
                    break 2;
                }
            }
        }

        // -------------------- CALCULAR SALDO_USADO y MONTO_PASARELA --------------------
        
        $saldo_usado = 0.0;
        $monto_pasarela = 0.0;

        // Obtener saldo actual (entidad)
        $saldoEntity = $user->getSaldo();
        $saldoDisponible = $saldoEntity ? (float) $saldoEntity->getSaldo() : 0.0;
        $saldoDisponible = round($saldoDisponible, 2);

        if ($idMetodo == 1 && $pago_mixto) {

            return $this->errorsInterface->error_message(
                'No se puede usar el medodo de pago por transferencia con pago mixto.',
                Response::HTTP_CONFLICT,
                'description',
                'Metodo de pago invalido.'
            );
        } elseif ($idMetodo == 2) {
            // Si pago mixto está activo y hay saldo disponible, usar saldo primero
            if ($pago_mixto && $saldoDisponible <= 0) {
                return $this->errorsInterface->error_message(
                    'No tienes saldo disponible para pago mixto.',
                    Response::HTTP_CONFLICT,
                    'description',
                    'Tu saldo es insuficiente para realizar un pago mixto.'
                );
            }

            if ($pago_mixto && $saldoDisponible > 0) {
                if ($hayProductoTipo3) {
                    return $this->errorsInterface->error_message(
                        'No se puede comprar recargas o GIFTCARDS con saldo de shopby.',
                        Response::HTTP_CONFLICT,
                        'description',
                        'Tipo de producto invalido.'
                    );
                }

                // Si el saldo disponible cubre o supera el total
                if ($saldoDisponible >= $total) {
                    return $this->errorsInterface->error_message(
                        'Si su saldo cubre todo el valor a pagar, use el metodo de pago con saldo.',
                        Response::HTTP_CONFLICT,
                        'description',
                        'Metodo de pago invalido.'
                    );

                } else {
                    // Pago mixto parcial: usamos todo el saldo disponible y la pasarela cobrará la diferencia
                    $remaining = round($total - $saldoDisponible, 2);

                    try {
                        $response = $this->placetoPayService->processPayment(
                            $nombre,
                            $apellido,
                            $email,
                            $dni,
                            $documento,
                            $telefono,
                            $n_venta,
                            $remaining,
                            $subtotal,
                            $costo_envio,
                            $iva,
                            $return_url
                        );

                        $api_response = json_decode($response);

                        if (!$api_response || !isset($api_response->requestId) || !isset($api_response->processUrl)) {
                            throw new Exception('Respuesta inválida de PlaceToPay.');
                        }

                        $request_id = $api_response->requestId;
                        $url = $api_response->processUrl;

                        // Solo después de crear la orden externa, descontamos la parte de saldo usada
                        try {
                            if (method_exists($this->em, 'lock') && $saldoEntity) {
                                try {
                                    $this->em->lock($saldoEntity, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);
                                } catch (\Throwable $lockEx) {
                                    // manejar lock fail si es necesario
                                }
                            }
                        } catch (\Throwable $e) {
                            // noop
                        }

                        $saldo_usado = round($saldoDisponible, 2); // usamos todo el saldo disponible
                        $monto_pasarela = $remaining; // lo que cobrará PlaceToPay

                        $saldoEntity->setSaldo(round($saldoDisponible - $saldo_usado, 2));
                        $this->em->persist($saldoEntity);
                        $this->em->flush();
                    } catch (Exception $e) {
                        return $this->errorsInterface->error_message('Error al procesar la transacción PlaceToPay.', Response::HTTP_INTERNAL_SERVER_ERROR, 'description', $e->getMessage());
                    }
                }
            } else {
                // Flujo normal: todo en PlaceToPay por $total (sin uso de saldo)
                try {
                    $response = $this->placetoPayService->processPayment(
                        $nombre,
                        $apellido,
                        $email,
                        $dni,
                        $documento,
                        $telefono,
                        $n_venta,
                        $total,
                        $subtotal,
                        $costo_envio,
                        $iva,
                        $return_url
                    );

                    $api_response = json_decode($response);

                    if (!$api_response || !isset($api_response->requestId) || !isset($api_response->processUrl)) {
                        throw new Exception('Respuesta inválida de PlaceToPay.');
                    }

                    $request_id = $api_response->requestId;
                    $url = $api_response->processUrl;

                    $saldo_usado = 0.0;
                    $monto_pasarela = round($total, 2);
                } catch (Exception $e) {
                    return $this->errorsInterface->error_message('Error al procesar la transacción PlaceToPay.', Response::HTTP_INTERNAL_SERVER_ERROR, 'description', $e->getMessage());
                }
            }

            // --------- ID METODO 3 (PayPal) con soporte pago mixto ----------
        } elseif ($idMetodo == 3) {
            if ($pago_mixto && $saldoDisponible <= 0) {
                return $this->errorsInterface->error_message(
                    'No tienes saldo disponible para pago mixto.',
                    Response::HTTP_CONFLICT,
                    'description',
                    'Tu saldo es insuficiente para realizar un pago mixto.'
                );
            }

            if ($pago_mixto && $saldoDisponible > 0) {
                if ($hayProductoTipo3) {
                    return $this->errorsInterface->error_message(
                        'No se puede comprar recargas o GIFTCARDS con saldo de shopby.',
                        Response::HTTP_CONFLICT,
                        'description',
                        'Tipo de producto invalido.'
                    );
                }

                if ($saldoDisponible >= $total) {
                    return $this->errorsInterface->error_message(
                        'Si su saldo cubre todo el valor a pagar, use el metodo de pago con saldo.',
                        Response::HTTP_CONFLICT,
                        'description',
                        'Metodo de pago invalido.'
                    );
                } else {
                    // Parcial: crear orden PayPal por la diferencia
                    $remaining = round($total - $saldoDisponible, 2);

                    try {
                        $response = $this->paypalService->createOrder($n_venta, $remaining, $subtotal, $iva, $costo_envio);
                        $api_response = json_decode($response);

                        if (!$api_response || !isset($api_response->id) || !isset($api_response->links)) {
                            throw new Exception('Respuesta inválida de PayPal.');
                        }

                        $request_id = $api_response->id;
                        $url = null;
                        foreach ($api_response->links as $link) {
                            if (isset($link->rel) && $link->rel === 'approve') {
                                $url = $link->href;
                                break;
                            }
                        }

                        if (!$url)
                            throw new Exception('No se obtuvo URL de aprobación PayPal.');

                        // Descontar la parte de saldo usada
                        try {
                            if (method_exists($this->em, 'lock') && $saldoEntity) {
                                try {
                                    $this->em->lock($saldoEntity, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);
                                } catch (\Throwable $lockEx) {
                                    // noop
                                }
                            }
                        } catch (\Throwable $e) {
                            // noop
                        }

                        $saldo_usado = round($saldoDisponible, 2);
                        $monto_pasarela = $remaining;

                        $saldoEntity->setSaldo(round($saldoDisponible - $saldo_usado, 2));
                        $this->em->persist($saldoEntity);
                        $this->em->flush();
                    } catch (Exception $e) {
                        return $this->errorsInterface->error_message('Error al procesar la transacción PayPal.', Response::HTTP_INTERNAL_SERVER_ERROR, 'description', $e->getMessage());
                    }
                }
            } else {
                // Pago completo por PayPal
                try {
                    $response = $this->paypalService->createOrder($n_venta, $total, $subtotal, $iva, $costo_envio);
                    $api_response = json_decode($response);
                    if (!$api_response || !isset($api_response->id)) {
                        throw new Exception('Respuesta inválida de PayPal.');
                    }
                    $request_id = $api_response->id;
                    $url = $api_response->links[1]->href ?? null;

                    $saldo_usado = 0.0;
                    $monto_pasarela = round($total, 2);
                } catch (Exception $e) {
                    return $this->errorsInterface->error_message('Error al procesar la transacción PayPal.', Response::HTTP_INTERNAL_SERVER_ERROR, 'description', $e->getMessage());
                }
            }

            // --------- ID METODO 4 (Saldo) - pago únicamente con saldo ----------
        } elseif ($idMetodo == 4) {

            if ($pago_mixto) {
                return $this->errorsInterface->error_message(
                    'No se puede utilizar pago mixto con el metodo de pago por Saldo.',
                    Response::HTTP_CONFLICT,
                    'description',
                    'Metodo de pago invalido.'
                );

            }

            if ($totalGeneral == 0 || $totalGeneral === null) {
                return $this->errorsInterface->error_message(
                    'No hay valor a pagar.',
                    Response::HTTP_BAD_REQUEST,
                    'description',
                    'No hay valor a pagar'
                );
            }

            $saldo = $user->getSaldo();

            if (!$saldo || $saldo->getSaldo() <= 0) {
                return $this->errorsInterface->error_message(
                    'No tienes saldo disponible.',
                    Response::HTTP_CONFLICT,
                    'description',
                    'No tienes saldo para realizar la compra.'
                );
            }

            // Requerimos que el saldo cubra el total para método 4
            if ($saldo->getSaldo() < $totalGeneral) {
                return $this->errorsInterface->error_message(
                    'No tienes suficiente saldo para pagar el total con el método seleccionado.',
                    Response::HTTP_CONFLICT,
                    'description',
                    'Saldo insuficiente.'
                );
            }

            // Validación de productos tipo 3 (no permitidos con saldo)
            if ($hayProductoTipo3) {
                return $this->errorsInterface->error_message(
                    'No se puede comprar recargas o GiftCards con saldo.',
                    Response::HTTP_CONFLICT,
                    'description',
                    'Tipo de producto inválido.'
                );
            }

            // Descontar el total del saldo del usuario
            try {
                if (method_exists($this->em, 'lock') && $saldoEntity) {
                    try {
                        $this->em->lock($saldoEntity, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);
                    } catch (\Throwable $lockEx) {
                        // noop
                    }
                }
            } catch (\Throwable $e) {
                // noop
            }

            $saldo_usado = round($totalGeneral, 2);
            $monto_pasarela = 0.0;

            $saldoEntity->setSaldo(round($saldoDisponible - $saldo_usado, 2));
            $this->em->persist($saldoEntity);
            $this->em->flush();

            $request_id = null;
            $url = $front_url->getValorGeneral() . '/checkout/resumen/' . $n_venta;
        }

        // --------------------------------------------------------------------
        // Guardado de pedidos por vendedor (tu lógica original, actualizada para pasar los montos)
        foreach ($data['productos_por_vendedor'] as $pedido) {

            $numero_pedido = null;
            $tienda = $this->em->getReference(Tiendas::class, $pedido['id_tienda']);

            if ($cupon && $cupon->isConEnvio() == true) {
                $c_descuento = $pedido['costo_envio_tienda'];
            } else {
                $c_descuento = $pedido['descuento_cupon'];
            }

            try {
                $p = $this->guardar_pedido->guardarPedido(
                    $user,
                    $n_venta,
                    $factura,
                    $numero_pedido,
                    $tienda,
                    $metodo_envio,
                    $ingresado,
                    $metodo_pago,
                    $cupon,
                    $direcion_principal_usuario,
                    $direcion_secundaria_usuario,
                    $referencia_usuario,
                    $customer,
                    $dni,
                    $telefono,
                    $codigo_postal_customer,
                    $ciudad_usuario,
                    $id_servientrega,
                    $provincia_usuario,
                    $region_usuario,
                    $latitud_usuario,
                    $longitud_usuario,
                    $request_id,
                    $iva_aplicado,
                    $pedido['subtotal'],
                    $pedido['iva'],
                    $pedido['subtotal_mas_iva'],
                    $pedido['subtotal_envio'],
                    $pedido['iva_envio'],
                    $pedido['costo_envio_tienda'],
                    $pedido['comision_paypal'],
                    $pedido['total'],
                    $c_descuento,
                    $pedido['subtotal_original'],
                    $saldo_usado,
                    $monto_pasarela
                );
            } catch (Exception $e) {
                return $this->errorsInterface->error_message('Error al crear el pedido.', Response::HTTP_INTERNAL_SERVER_ERROR, 'description', $e->getMessage());
            }
            $tipoEnvioP = [];
            foreach ($pedido['productos'] as $producto) {
                $tipo_envio_pedido = $producto['tipo_entrega'];
                $tipoEnvioP[] = $tipo_envio_pedido;

                $p_detalle = $this->em->getReference(Productos::class, $producto['id_producto']);
                $v_variacion = $this->em->getRepository(Variaciones::class)->find($producto['id_variacion']);

                try {
                    $this->guardar_pedido->guardarDetallePedido(
                        $p,
                        $p_detalle,
                        $v_variacion,
                        $producto['nombre_producto'],
                        $producto['cantidad'],
                        $tienda,
                        $producto['subtotal'],
                        $producto['iva'],
                        $producto['total'],
                        $producto['ciudad'],
                        $producto['direccion'],
                        $producto['id_direccion'],
                        $producto['provincia'],
                        $producto['region'],
                        $producto['peso'],
                        $producto['latitud'],
                        $producto['longitud'],
                        $producto['celular_producto'],
                        $producto['referencia'] ? $producto['referencia'] : null,
                        $producto['usario_producto'],
                        $producto['total_unitario'],
                        $producto['subtotal_unitario'],
                        $producto['iva_unitario'],
                        0,
                        $producto['codigo_producto']
                    );
                } catch (Exception $e) {
                    return $this->errorsInterface->error_message('Error al crear el detalle del pedido.', Response::HTTP_INTERNAL_SERVER_ERROR, 'description', $e->getMessage());
                }


            }

            if ($cupon && $subtotal == 0) {
                $p->setEstado('APPROVED');
                $p->setSubtotal(0);
                $p->setIva(0);
                $p->setTotal(0);
                $p->setTotalFinal(0);
                $p->setSubtotalEnvio(0);
                $p->setIvaEnvio(0);
                $p->setCostoEnvio(0);
                $cupon->setUsoCupon($cupon->getUsoCupon() + 1);
            }

            if ($idMetodo == 4) {
                $p->setEstado('APPROVED');
            }

            $p->setUrlPago($url);

            $tipoEnvioPedido = 'SIN TIPO DE ENVIO';
            if (in_array('A DOMICILIO', $tipoEnvioP) && in_array('RETIRO EN TIENDA FISICA', $tipoEnvioP)) {
                $tipoEnvioPedido = 'AMBOS';
            } elseif (in_array('A DOMICILIO', $tipoEnvioP)) {
                $tipoEnvioPedido = 'A DOMICILIO';
            } elseif (in_array('RETIRO EN TIENDA FISICA', $tipoEnvioP)) {
                $tipoEnvioPedido = 'RETIRO EN TIENDA FISICA';
            }

            if ($tipoEnvioPedido == 'RETIRO EN TIENDA FISICA') {
                $p->setMetodoEnvio(null);
            }

            $p->setTipoEnvio($tipoEnvioPedido);

            $this->em->flush();

            $eml = (new TemplatedEmail())
                ->to($p->getTienda()->getLogin()->getEmail())
                ->subject('Tienes un nuevo pedido')
                ->htmlTemplate('pedidos/email_notificacion.html.twig')
                ->context([
                    'nombre' => $p->getTienda()->getLogin()->getUsuarios()->getNombre() . ' ' . $p->getTienda()->getLogin()->getUsuarios()->getApellido(),
                    'n_pedido' => $numero_pedido,
                    'metodo_pago' => $metodo_pago->getNombre(),
                    'detalle' => $pedido['productos'],
                    'direccion_cliente' => $direcion_principal_usuario . ' y ' . $direcion_secundaria_usuario . ' ' . $ciudad_usuario,
                    'nombre_cliente' => $nombre . ' ' . $apellido,
                    'metodo_envio' => $nombre_metodo_envio,
                    'subtotal' => $pedido['subtotal'],
                    'impuestos' => $pedido['iva'],
                    'costo_envio' => $pedido['costo_envio_tienda'],
                    'comision_paypal' => $pedido['comision_paypal'],
                    'total' => $pedido['total'],
                    'estado_pago' => $p->getEstado()
                ]);

            $this->mailer->send($eml);
        } // end foreach pedidos_por_vendedor

        // Limpiar carrito si el pago fue con deposito o saldo
        if ($idMetodo === 1 || $idMetodo === 4) {
            $carritoUsuario = $user->getCarritos()->first();
            if ($carritoUsuario instanceof Carrito) {
                $this->deletecarrito($carritoUsuario);
            }
        }

        // Email al cliente
        $eml = (new TemplatedEmail())
            ->to($user->getEmail())
            ->subject('Gracias por usar Shopby')
            ->htmlTemplate('pedidos/email_pedido.html.twig')
            ->context([
                'n_venta' => $n_venta,
                'nombre' => $nombre . ' ' . $apellido,
                'metodo_pago' => $metodo_pago->getNombre(),
                'detalle' => $data['productos_sin_agrupar'],
                'subtotal' => $subtotal,
                'impuestos' => $iva,
                'costo_envio' => $costo_envio,
                'direccion_cliente' => $direcion_principal_usuario . ' y ' . $direcion_secundaria_usuario . ' ' . $ciudad_usuario,
                'comision_paypal' => $calculo_paypal,
                'total' => $total,
                'estado_pago' => $p->getEstado()
            ]);

        $this->mailer->send($eml);

        if (!$url && $metodo_pago->getId() == 1) {
            $url = $front_url->getValorGeneral() . '/checkout/deposito/' . $n_venta;
        } elseif (!$url && $metodo_pago->getId() == 4) {
            $url = $front_url->getValorGeneral() . '/checkout/resumen/' . $n_venta;
        }

        $dataResponse = [
            'url' => $url
        ];

        return $this->errorsInterface->succes_message('Venta generada con éxito.', null, $url);
    }



  
    private function ver_orden2($pedido)
    {
        $pedidos = $this->em->getRepository(Pedidos::class)->findBy(['n_venta' => $pedido]);

        if (!$pedidos) {
            return $this->errorsInterface->error_message('Venta no encontrada', Response::HTTP_NOT_FOUND);

        }

        foreach ($pedidos as $pedido) {

            $id = $pedido->getReferenciaPedido();
            $url = $this->paypalService->data_url() . "/v2/checkout/orders/" . $id;

            $auth = $this->paypalService->getToken();
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "Authorization: Bearer " . $auth
            ]);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            $rest = $result;

            $json = json_decode($rest);

            if (curl_errno($ch)) {
                /*return $this->json(['message' => 'Error Paypal: ' . curl_error($ch)])->setStatusCode($httpCode);*/
                return $this->errorsInterface->error_message('Error Paypal: ' . curl_error($ch), $httpCode);
            }
            if ($httpCode !== 200) {

                /*return $this->json('')->setStatusCode($httpCode); */
                return $this->errorsInterface->error_message('Error al obtener la orden de Paypal.', $httpCode, 'Error al obtener la orden de Paypal.');
            }


        }

        return $this->json($json->links[1]->href);

    }

    private function deletecarrito(Carrito $carrito): void
    {

        $this->em->remove($carrito);
        $this->em->flush();

    }
}

<?php

namespace App\Controller;

use App\Entity\Banco;
use App\Entity\Carrito;
use App\Entity\Ciudades;
use App\Entity\Cupon;
use App\Entity\DetallePedido;
use App\Entity\Estados;
use App\Entity\FuncionesEspeciales;
use App\Entity\Ganancia;
use App\Entity\GeneralesApp;
use App\Entity\Impuestos;
use App\Entity\Login;
use App\Entity\Pedidos;
use App\Entity\Retiros;
use App\Entity\Servientrega;
use App\Entity\Tiendas;
use App\Form\PedidosType;
use App\Form\RetiroType;
use App\Interfaces\ErrorsInterface;
use App\Interfaces\PedidosInterface;
use App\Repository\DetallePedidoRepository;
use App\Repository\GeneralesAppRepository;
use App\Repository\PedidosRepository;
use App\Repository\ServientregaRepository;
use App\Service\DelivereoService;
use App\Service\DynamicMailerFactory;
use App\Service\EmailPedidoService;
use App\Service\GestionarTransacciones;
use App\Service\PaypalService;
use App\Service\PlacetoPayService;
use App\Service\ServientregaService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Psr\Log\LoggerInterface;

class OrdenesController extends AbstractController
{
    private $pedidosInterface;

    private $jwtToken;
    private $tokenExpiry;

    private $gestionarTransacciones;

    private $servientregaService;

    private $DelivereoService;

    private $errorsInterface;

    private $mailer;

    private $placetoPayService;

    private $paypalService;

    private $emailPedidoService;

    public function __construct(PedidosInterface $pedidosInterface, GestionarTransacciones $gestionarTransacciones, ServientregaService $servientregaService, DelivereoService $delivereoService, ErrorsInterface $errorsInterface, DynamicMailerFactory $mailer,PlacetoPayService $placetoPayService, PaypalService $paypalService,EmailPedidoService $emailPedidoService )
    {
        $this->pedidosInterface = $pedidosInterface;
        $this->gestionarTransacciones = $gestionarTransacciones;
        $this->servientregaService = $servientregaService;
        $this->DelivereoService = $delivereoService;
        $this->errorsInterface = $errorsInterface;
        $this->mailer = $mailer;
        $this->placetoPayService = $placetoPayService;
        $this->paypalService = $paypalService;
        $this->emailPedidoService= $emailPedidoService;
    }


    private function autorizar_pago($pedido, EntityManagerInterface $entityManager, GeneralesAppRepository $generalesAppRepository)
    {
        $ingresado = $entityManager->getRepository(Estados::class)->find(19);
        $pendiente = $entityManager->getRepository(Estados::class)->find(23);

        $pedidos = $entityManager->getRepository(Pedidos::class)->findBy(['n_venta' => $pedido]);

        if (!$pedidos) {
            return $this->errorsInterface->error_message('Pedido no encontrado', Response::HTTP_NOT_FOUND);
        }

        $cartsToDelete = [];
        $cuponesToUpdate = [];

        foreach ($pedidos as $pedido) {
            $user = $pedido->getLogin();
            $id_cupon = $pedido->getCupon()?->getId();
            $carrito = $entityManager->getRepository(Carrito::class)->findOneBy(['login' => $user]);
            $cupon = $id_cupon ? $entityManager->getRepository(Cupon::class)->find($id_cupon) : null;

            $id = $pedido->getReferenciaPedido();
            $n_venta = $pedido->getNVenta();

            // Consultar orden PayPal
            $url = $this->paypalService->data_url() . "/v2/checkout/orders/" . $id;
            $auth = $this->paypalService->getToken();

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "Authorization: Bearer " . $auth
                ]
            ]);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                return $this->errorsInterface->error_message('Error Paypal: ' . curl_error($ch), $httpCode);
            }

            $json = json_decode($result);

            if ($httpCode !== 200 || !$json) {
                return $this->errorsInterface->error_message('Respuesta inválida de PayPal', $httpCode);
            }

            // Si PayPal aprueba la orden, capturar el pago
            if ($json->status === 'APPROVED') {
                $captureUrl = $this->paypalService->data_url() . "/v2/checkout/orders/$id/capture";
                $auth = $this->paypalService->getToken();

                $ch = curl_init($captureUrl);
                curl_setopt_array($ch, [
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_HTTPHEADER => [
                        "Content-Type: application/json",
                        "PayPal-Request-Id: " . $n_venta,
                        "Authorization: Bearer " . $auth,
                    ]
                ]);

                $result = curl_exec($ch);
                $j = json_decode($result);

                if (isset($j->status) && $j->status === 'COMPLETED') {
                    $pedido->setEstado("APPROVED");
                    $pedido->setEstadoEnvio($ingresado);
                    $pedido->setEstadoRetiro($ingresado);
                    $pedido->setFechaPago(new DateTime());

                    if ($carrito) {
                        $cartsToDelete[] = $carrito;
                    }

                    if ($cupon) {
                        $cuponesToUpdate[] = $cupon;
                    }
                } else {
                    $pedido->setEstado('PENDING');
                    $pedido->setEstadoEnvio($pendiente);
                    $pedido->setEstadoRetiro($pendiente);
                }
            } elseif ($json->status === 'COMPLETED') {
                $pedido->setEstado('APPROVED');
                $pedido->setEstadoEnvio($ingresado);
                $pedido->setEstadoRetiro($ingresado);
                $pedido->setFechaPago(new DateTime());

                if ($carrito) {
                    $cartsToDelete[] = $carrito;
                }

                if ($cupon) {
                    $cuponesToUpdate[] = $cupon;
                }
            } else {
                // Otros estados
                $pedido->setEstado($json->status ?? 'PENDING');
                $pedido->setEstadoEnvio($pendiente);
                $pedido->setEstadoRetiro($pendiente);
            }

            $entityManager->persist($pedido);
        }

        // Actualizar cupones usados
        foreach ($cuponesToUpdate as $cupon) {
            $uso = $cupon->getUsoCupon() ?? 0;
            $cupon->setUsoCupon($uso + 1);
            $entityManager->persist($cupon);
        }

        // Eliminar carritos
        foreach ($cartsToDelete as $cart) {
            $this->deleteCarrito($entityManager, $cart);
        }

        // 🔹 Un solo flush final
        $entityManager->flush();

        return true;
    }


    private function consultarSolicitud($pedido, EntityManagerInterface $entityManager)
    {
        $user = $this->getUser();
        $pedidos = $entityManager->getRepository(Pedidos::class)->findBy(['n_venta' => $pedido, 'login' => $user]);

        if (empty($pedidos)) {
            return $this->errorsInterface->error_message('No hay pedidos con este número de venta', Response::HTTP_NOT_FOUND);
        }

        $cartsToDelete = [];
        $cuponToUpdate = [];
        $total_Subtotal = $total_iva = $total_costo_envio = $total_final = 0;
        $data = null;

        foreach ($pedidos as $pedido) {
            $id = $pedido->getReferenciaPedido();
            $id_cupon = $pedido->getCupon()?->getId();
            $user = $pedido->getLogin();
            $carrito = $entityManager->getRepository(Carrito::class)->findOneBy(['login' => $user]);
            $cupon = $id_cupon ? $entityManager->getRepository(Cupon::class)->find($id_cupon) : null;

            // API consulta estado
            $generales = $entityManager->getRepository(GeneralesApp::class);
            $auth = $generales->getLoginPTP();
            $apiUrl = $generales->data_url() . "/session/" . $id;

            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($auth),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json']
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                return $this->errorsInterface->error_message('Error cURL: ' . curl_error($ch), $httpCode);
            }

            if ($httpCode != 200) {
                return $this->errorsInterface->error_message('Respuesta cURL ' . $httpCode . ' : ' . $response, $httpCode);
            }

            $json = json_decode($response, true);
            $estadoPago = $json['payment'][0]['status']['status'] ?? $json['status']['status'] ?? null;

            if (isset($json['payment'][0]['authorization'])) {
                $pedido->setAutorizacion($json['payment'][0]['authorization']);
                $pedido->setReferenciaInterna($json['payment'][0]['internalReference']);
                $pedido->setFechaPago(new DateTime($json['payment'][0]['status']['date'] ?? 'now'));
            }

            $pedido->setEstado($estadoPago);

            $ingresado = $entityManager->getRepository(Estados::class)->find(19);
            $pendiente = $entityManager->getRepository(Estados::class)->find(23);
            $cancelado = $entityManager->getRepository(Estados::class)->find(24);

            switch ($estadoPago) {
                case 'APPROVED':
                    $pedido->setEstadoEnvio($ingresado);
                    $pedido->setEstadoRetiro($ingresado);
                    $pedido->setFechaPago(new DateTime());

                    if ($carrito) {
                        $cartsToDelete[] = $carrito;
                    }

                    if ($cupon) {
                        $cuponToUpdate[] = $cupon;
                    }
                    break;

                case 'PENDING':
                    $pedido->setEstadoEnvio($pendiente);
                    $pedido->setEstadoRetiro($pendiente);
                    break;

                case 'REJECTED':
                    $pedido->setEstadoEnvio($cancelado);
                    $pedido->setEstadoRetiro($cancelado);
                    $pedido->setFechaRechazo(new DateTime());
                    break;

                default:
                    $pedido->setEstadoEnvio($cancelado);
                    $pedido->setFechaRechazo(new DateTime());
                    break;
            }

            $total_Subtotal += $pedido->getSubtotal();
            $total_iva += $pedido->getIva();
            $total_costo_envio += $pedido->getCostoEnvio();
            $total_final += $pedido->getTotalFinal();

            $data = $this->emailPedidoService->sendemail_pedido($pedido, $estadoPago);
        }

        // Actualizar cupones
        foreach ($cuponToUpdate as $cupon) {
            $uso_cupon = $cupon->getUsoCupon() ?? 0;
            $cupon->setUsoCupon($uso_cupon + 1);
            $entityManager->persist($cupon);
        }

        // Eliminar carritos
        foreach ($cartsToDelete as $cartToDelete) {
            $this->deleteCarrito($entityManager, $cartToDelete);
        }

        // ⚠️ Un solo flush final
        $entityManager->flush();

        return $estadoPago;
    }


    private function deletecarrito(EntityManagerInterface $entityManager, Carrito $carrito): void
    {
        $entityManager->remove($carrito);
        $entityManager->flush();
    }


    private function cencel_order($pedido, EntityManagerInterface $entityManager)
    {
        $cancelado = $entityManager->getRepository(Estados::class)->findOneBy(['id' => 24]);

        $pedidos = $entityManager->getRepository(Pedidos::class)->findBy(['n_venta' => $pedido, 'estado' => 'PENDING']);


        if (!$pedidos) {
            return $this->errorsInterface->error_message('Pedido no encontrado', Response::HTTP_NOT_FOUND);
        }

        foreach ($pedidos as $pedido) {

            $pedido->setEstado('REJECTED');
            $pedido->setEstadoEnvio($cancelado);
            $pedido->setEstadoRetiro($cancelado);
            $pedido->setFechaRechazo(new DateTime());
            $entityManager->flush();
        }

    }


    #[Route('/api/ver_compra/{pedido}', name: 'ver_compra', methods: ['GET'])]
    #[OA\Tag(name: 'Pedidos')]
    #[OA\Response(
        response: 200,
        description: 'Ver una venta por numero de venta'
    )]
    #[OA\Parameter(
        name: "origen",
        in: "query",
        description: "al poner cancel en el query origen cancelas un pedido hecho con paypal"
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function my_pedido(
        Request $request,
        $pedido,
        EntityManagerInterface $entityManager,
        GeneralesAppRepository $generalesAppRepository
    ): Response {
        $allowedParams = ['origen'];

        // Validar parámetros
        $queryParams = array_keys($request->query->all());
        $invalidParams = array_diff($queryParams, $allowedParams);

        if (!empty($invalidParams)) {
            return $this->errorsInterface->error_message(
                'Parámetros no permitidos: ' . implode(', ', $invalidParams),
                Response::HTTP_BAD_REQUEST
            );
        }

        $ref = $request->query->get('origen');
        $user = $this->getUser();
        $pedidos = $entityManager->getRepository(Pedidos::class)->findBy([
            'n_venta' => $pedido,
            'login' => $user
        ]);

        if (empty($pedidos)) {
            return $this->errorsInterface->error_message(
                'No hay pedidos con este número de venta',
                Response::HTTP_NOT_FOUND
            );
        }

        foreach ($pedidos as $p) {
            $n_venta = $p->getNVenta();
            $estado_pago = $p->getEstado();
            $metodo_pago = $p->getMetodoPago()->getId();

            // ✅ Solo actualizar si el pedido sigue PENDING
            if ($estado_pago === 'PENDING') {
                if ($metodo_pago == 2) {
                    // Pasarela 2 → tu función consultarSolicitud()
                    $this->consultarSolicitud($n_venta, $entityManager);
                } elseif ($metodo_pago == 3 && $ref === null) {
                    // Pasarela 3 (PayPal)
                    $this->autorizar_pago($n_venta, $entityManager, $generalesAppRepository);
                } elseif ($metodo_pago == 3 && $ref === 'cancel') {
                    $this->cencel_order($n_venta, $entityManager);
                }
            }
        }

        return $this->pedidosInterface->vista_venta($pedidos);
    }

    #[Route('/api/ver_pedido_vendedor/{pedido}', name: 'ver_pedido_vendedor', methods: ['GET'])]
    #[OA\Tag(name: 'Pedidos')]
    #[OA\Response(
        response: 200,
        description: 'Ver pedido por numero de pedido para el vendedor'
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function show_pedido($pedido, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $tienda_user = $entityManager->getRepository(Tiendas::class)->findOneBy(['login' => $user]);
        $pedido = $entityManager->getRepository(Pedidos::class)->findOneBy(['numero_pedido' => $pedido, 'tienda' => $tienda_user]);

        if (!$pedido) {
            return $this->errorsInterface->error_message('Pedido no encontrado', Response::HTTP_NOT_FOUND);
        }

        return $this->pedidosInterface->vista_pedido_vendedor($pedido);

    }


    #[Route('/api/ver_pedido/{pedido}', name: 'ver_pedido_cliente', methods: ['GET'])]
    #[OA\Tag(name: 'Pedidos')]
    #[OA\Response(
        response: 200,
        description: 'Ver pedido por numero de pedido para el cliente'
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function ver_pedido($pedido, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $pedido = $entityManager->getRepository(Pedidos::class)->findOneBy(['numero_pedido' => $pedido, 'login' => $user]);

        if (!$pedido) {
            return $this->errorsInterface->error_message('Pedido no encontrado', Response::HTTP_NOT_FOUND);
        }

        return $this->pedidosInterface->vista_pedido_clinete($pedido);
    }


    #[Route('/api/cliente_pedidos', name: 'cliente_pedido', methods: ['GET'])]
    #[OA\Tag(name: 'Pedidos')]
    #[OA\Response(
        response: 200,
        description: 'Lista de pedidos del cliente'
    )]
    #[OA\Parameter(
        name: "estado",
        in: "query",
        description: "Filtra las ordenes por estado de pago(APPROVED,PENDING,REJECTED,CANCELLED)"
    )]
    #[OA\Parameter(
        name: "orderBy",
        in: "query",
        description: "Ordena los pedidos por fecha (fecha_desc)"
    )]
    #[OA\Parameter(
        name: "searchTerm",
        in: "query",
        description: "Filtra los pedidos por n_venta y n_pedido"
    )]
    #[OA\Parameter(
        name: "venta_concretada",
        in: "query",
        description: "Filtra los pedidos concretados"
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function cliente_pedidos(PedidosRepository $pedidosRepository, Request $request): Response
    {
        $user = $this->getUser();

        $allowedParams = [
            'estado',
            'searchTerm',
            'orderBy',
            'venta_concretada'
        ];

        // Obtener todos los parámetros enviados
        $queryParams = array_keys($request->query->all());

        // Detectar parámetros no permitidos
        $invalidParams = array_diff($queryParams, $allowedParams);

        if (!empty($invalidParams)) {
            return $this->errorsInterface->error_message(
                'Parámetros no permitidos: ' . implode(', ', $invalidParams),
                Response::HTTP_BAD_REQUEST
            );
        }
        

        // Obtener parámetros de filtro
        $estado = $request->query->get('estado');
        $searchTerm = $request->query->get('searchTerm');
        $orderBy = $request->query->get('orderBy');
        $ventaConcretada = $request->query->get('venta_concretada');

        // Obtener pedidos (sin filtrar venta_concretada aquí)
        $pedidos = $pedidosRepository->pedidos_filter_cliente($user, $estado, $searchTerm, ['orderBy' => $orderBy]);

        $pedidosArray = [];
        foreach ($pedidos as $pedido) {
            $pedidoData = $this->pedidosInterface->lista_pedidos_cliente($pedido); // Quita $ventaConcretada
            $pedidosArray[] = $pedidoData;
        }

        // Filtrar por venta_concretada si existe
        if ($ventaConcretada !== null) {
            $filterValue = filter_var($ventaConcretada, FILTER_VALIDATE_BOOLEAN);
            $pedidosArray = array_filter($pedidosArray, function ($pedido) use ($filterValue) {
                return $pedido['venta_concretada'] === $filterValue;
            });
        }

        return $this->json(array_values($pedidosArray)); // Reindexa el array
    }

    #[Route('/api/vendedor_pedidos', name: 'vendedor_pedidos', methods: ['GET'])]
    #[OA\Tag(name: 'Pedidos')]
    #[OA\Response(
        response: 200,
        description: 'Lista de pedidos del vendedor'
    )]
    #[OA\Parameter(
        name: "estado",
        in: "query",
        description: "Filtra las ordenes por estado de pago(APPROVED,PENDING,REJECTED,CANCELLED)"
    )]
    #[OA\Parameter(
        name: "orderBy",
        in: "query",
        description: "Ordena los pedidos por fecha (fecha_desc)"
    )]
    #[OA\Parameter(
        name: "searchTerm",
        in: "query",
        description: "Filtra los pedidos por nombre del cliete, n_venta y n_pedido"
    )]
    #[OA\Parameter(
        name: "venta_concretada",
        in: "query",
        description: "Filtra los pedidos concretados"
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function vendedor_pedidos(PedidosRepository $pedidosRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        $user = $this->getUser();
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login' => $user]);

        $user = $this->getUser();

        $allowedParams = [
            'estado',
            'searchTerm',
            'orderBy',
            'venta_concretada'
        ];

        // Obtener todos los parámetros enviados
        $queryParams = array_keys($request->query->all());

        // Detectar parámetros no permitidos
        $invalidParams = array_diff($queryParams, $allowedParams);

        if (!empty($invalidParams)) {
            return $this->errorsInterface->error_message(
                'Parámetros no permitidos: ' . implode(', ', $invalidParams),
                Response::HTTP_BAD_REQUEST
            );
        }
        // Parámetros
        $estado = $request->query->get('estado');
        $searchTerm = $request->query->get('searchTerm');
        $orderBy = $request->query->get('orderBy');
        $ventaConcretada = $request->query->get('venta_concretada');

        // Obtener todos los pedidos (sin filtrar venta_concretada aquí)
        $pedidos = $pedidosRepository->pedidos_filter_vendedor($tienda, $estado, $searchTerm, ['orderBy' => $orderBy]);

        $pedidosArray = [];
        foreach ($pedidos as $pedido) {
            $pedidoData = $this->pedidosInterface->lista_pedidos_vendedor($pedido);
            $pedidosArray[] = $pedidoData; // Siempre agregamos el pedido
        }

        // Filtrar por venta_concretada SI el parámetro existe
        if ($ventaConcretada !== null) {
            $filterValue = filter_var($ventaConcretada, FILTER_VALIDATE_BOOLEAN);
            $pedidosArray = array_filter($pedidosArray, function ($pedido) use ($filterValue) {
                return $pedido['venta_concretada'] === $filterValue;
            });
        }

        return $this->json(array_values($pedidosArray)); // array_values para reindexar
    }


    #[Route('/api/transacciones', name: 'transacciones', methods: ['GET'])]
    #[OA\Tag(name: 'Transacciones')]
    #[OA\Response(
        response: 200,
        description: 'Transacciones de la tienda con pedidos concretados'
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function transacciones(
        Request $request,
        DetallePedidoRepository $detallePedidoRepository,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $router
    ): Response {
        $host = $router->getContext()->getBaseUrl();
        $domain = $request->getSchemeAndHttpHost();

        $user = $this->getUser();
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login' => $user]);
        if (!$tienda) {
            return $this->errorsInterface->error_message('Tienda no encontrada', Response::HTTP_NOT_FOUND);
        }
        $comision = $tienda->getComision();

        $pedidos = $detallePedidoRepository->filter_transactions($tienda);

        $pedidosArray = [];
        $gananciasVendedor = $entityManager->getRepository(Ganancia::class)->findOneBy(['login' => $user]);
        if (!$gananciasVendedor) {
            $gananciasVendedor = new Ganancia();
            $gananciasVendedor->setLogin($user);
            $entityManager->persist($gananciasVendedor);
        }

        $numero_ventas = 0;
        $total_general = 0;

        foreach ($pedidos as $pedido) {
            $cantidad_total = 0;
            foreach ($pedido->getDetallePedidos() as $detalle) {
                $cantidad_total += $detalle->getCantidad();
            }
            $pedidosArray[] = [
                'numero_orden' => $pedido->getNumeroPedido(),
                'tipo_envio' => $pedido->getTipoEnvio(),
                'estado' => $pedido->getEstado(),
                'fecha' => $pedido->getFechaPedido(),
                'total' => $pedido->getTotal(),
                'items' => $cantidad_total,
                'cliente' => [
                    'avatar' => $pedido->getLogin()->getUsuarios()
                        ? $domain . $host . '/public/user/selfie/' . $pedido->getLogin()->getUsuarios()->getAvatar()
                        : '',
                    'nombres' => $pedido->getLogin()->getUsuarios()
                        ? $pedido->getLogin()->getUsuarios()->getNombre() . ' ' . $pedido->getLogin()->getUsuarios()->getApellido()
                        : '',
                    'dni' => $pedido->getDniCustomer(),
                    'celular' => $pedido->getCelularCustomer(),
                ],
            ];
            $numero_ventas++;
            $total_general += $pedido->getTotal();
        }


        // Retiros
        
        $retiros_aprobados = $entityManager->getRepository(Retiros::class)->findBy(['ganancia' => $gananciasVendedor, 'estado' => ['APPROVED']]);
        $movido_saldo = $entityManager->getRepository(Retiros::class)->findBy(['ganancia' => $gananciasVendedor, 'estado' => ['MOVIDO_SALDO']]);
        $retiros_pendientes = $entityManager->getRepository(Retiros::class)->findBy(['ganancia' => $gananciasVendedor, 'estado' => 'PENDING']);
        $retiros_rechazados = $entityManager->getRepository(Retiros::class)->findBy(['ganancia' => $gananciasVendedor, 'estado' => 'REJECTED']);


        // Totales de retiros aprobados
        $total_retiros_aprobados = 0;
        $total_retiros_aprobados_final = 0;
        $total_comision_aprobados = 0;
        foreach ($retiros_aprobados as $retiro) {
            $total_retiros_aprobados += $retiro->getRetiro();
            $total_comision_aprobados += $retiro->getComisionShopby();
            $total_retiros_aprobados_final += $retiro->getRetiroFinal();
        }

        // total movidos a saldo
        $total_movidos_saldo = 0;
        $total_movidos_saldo_final = 0;
        $total_comision_movidos_saldo = 0;
        foreach ($movido_saldo as $saldo) {
            $total_movidos_saldo += $saldo->getRetiro();
            $total_movidos_saldo_final += $saldo->getComisionShopby();
            $total_comision_movidos_saldo += $saldo->getRetiroFinal();
        }

        // Totales de retiros pendientes
        $total_retiros_pendientes = 0;
        $total_retiros_pendientes_final = 0;
        $total_comision_pendientes = 0;
        foreach ($retiros_pendientes as $retiro2) {
            $total_retiros_pendientes += $retiro2->getRetiro();
            $total_retiros_pendientes_final += $retiro2->getRetiroFinal();
            $total_comision_pendientes += $retiro2->getComisionShopby();
        }

      
        // Cálculos de saldo disponible y ganancia
        $calculo_comision = round(($gananciasVendedor->getDisponible() * $comision) / 100, 2, PHP_ROUND_HALF_UP);
        $total_recibir_disponible = round($gananciasVendedor->getDisponible() - $calculo_comision, 2, PHP_ROUND_HALF_UP);
        $saldo_disponible = [
            'subtotal' => $gananciasVendedor->getDisponible(),
            'comision_shopby' => $calculo_comision,
            'total' => $total_recibir_disponible
        ];

        $valores_pendientes = [
            'subtotal' => $total_retiros_pendientes,
            'comision_shopby' => $total_comision_pendientes,
            'total' => $total_retiros_pendientes_final
        ];

        $valores_aprobados = [
            'subtotal' => $total_retiros_aprobados,
            'comision_shopby' => $total_comision_aprobados,
            'total' => $total_retiros_aprobados_final
        ];

        $movido_saldo_data=[
            'subtotal' => $total_movidos_saldo,
            'comision_shopby' => $total_comision_movidos_saldo,
            'total' => $total_movidos_saldo_final
        ];

        $calculo_comision_ganancia = round($gananciasVendedor->getGanacia() * $comision / 100, 2, PHP_ROUND_HALF_UP);
        $total_recibir_ganancia = round($gananciasVendedor->getGanacia() - $calculo_comision_ganancia, 2, PHP_ROUND_HALF_UP);
        $ganancia_vendedor_data = [
            'subtotal' => $gananciasVendedor->getGanacia(),
            'comision_shopby' => $calculo_comision_ganancia,
            'total' => $total_recibir_ganancia
        ];

        //pasar esta logica a nestjs los aprovados y los rechazados

        if (!empty($retiros_rechazados)) {
            $gananciasVendedor->setDisponible($total_general);
        }
      
        if ($total_retiros_aprobados > 0) {
            $gananciasVendedor->setDisponible(round($gananciasVendedor->getGanacia() - $total_retiros_aprobados, 2, PHP_ROUND_HALF_UP));
            $gananciasVendedor->setTotalRetiros($total_retiros_aprobados);
            $gananciasVendedor->setTotalComision($total_comision_aprobados);
            $gananciasVendedor->setTotalRecibir($total_retiros_aprobados_final);
        }

        $entityManager->flush();

        return $this->json([
            'porcentaje_comision' => $comision,
            'last_sells' => $pedidosArray,
            'total_general' => $ganancia_vendedor_data,
            'saldo_disponible' => $saldo_disponible,
            'numero_ventas' => $numero_ventas,
            'total_retirado' => $valores_aprobados,
            'pendiente_por_acreditar' => $valores_pendientes,
            'movidos_saldo'=>$movido_saldo_data
        ]);
    }


    #[Route('/api/retirar_saldo', name: 'retirar_saldo', methods: ['POST'])]
    #[OA\Tag(name: 'Transacciones')]
    #[OA\RequestBody(
        description: 'Añadir una solicitud de transaccion',
        content: new Model(type: RetiroType::class)
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function retiros(Request $request, EntityManagerInterface $entityManager): Response
    {
        $email_depositos = $entityManager->getRepository(FuncionesEspeciales::class)->find(2);
        $user = $this->getUser();
        if (!$user instanceof Login) {
            return $this->errorsInterface->error_message('No se encuentra un usuario logueado', Response::HTTP_UNAUTHORIZED);
        }
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login' => $user]);

        $comision = $tienda->getComision();

        $gananciasVendedor = $entityManager->getRepository(Ganancia::class)->findOneBy(['login' => $user]);

        $valor_retiro = $gananciasVendedor->getDisponible() ? $gananciasVendedor->getDisponible() : 0;

        $retiro = new Retiros();

        $form = $this->createForm(RetiroType::class, $retiro);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $retiros_pendinetes = $entityManager->getRepository(Retiros::class)->findBy(['estado' => 'PENDING', 'ganancia' => $gananciasVendedor]);


            if (!empty($retiros_pendinetes)) {
                return $this->errorsInterface->error_message('Hay retiros pendientes', Response::HTTP_BAD_REQUEST);
            }

            $banco = $form->get('banco')->getData();

            $b = $entityManager->getRepository(Banco::class)->findOneBy(['id' => $banco, 'login' => $user]);

            if (!$b) {
                return $this->errorsInterface->error_message('La informacion del banco no existe', Response::HTTP_BAD_REQUEST);
            }


            if ($gananciasVendedor->getDisponible() <= 0) {

                return $this->errorsInterface->error_message('No tiene saldo suficiente para retirar', Response::HTTP_BAD_REQUEST);
            }


            $valor_comision = ($valor_retiro * $comision) / 100;

            $v_retiro_final2 = $valor_retiro - $valor_comision;
            $retiro->setGanancia(ganancia: $gananciasVendedor);
            $retiro->setFecha(new DateTime());
            $retiro->setRetiro(round($valor_retiro,2,PHP_ROUND_HALF_UP));
            $retiro->setComisionShopby(round($valor_comision,2,PHP_ROUND_HALF_UP));
            $retiro->setRetiroFinal(round($v_retiro_final2,2,PHP_ROUND_HALF_UP));
            $retiro->setEstado('PENDING');
            $entityManager->persist($retiro);
            $entityManager->flush();

            $this->actualizar_disponibilidad($entityManager, $user,round($valor_retiro,2,PHP_ROUND_HALF_UP));

            $eml = (new TemplatedEmail())
                ->to($email_depositos->getDescripcion())
                ->subject('El usuario' . ' ' . $user->getUsuarios()->getNombre() . ' ' . $user->getUsuarios()->getApellido() . ' ' . 'ha solicitado un retiro')
                ->htmlTemplate('retiros/solicitud_deposito.html.twig')
                ->context([
                    'user' => $user,
                    'banco' => $b,
                    'valor_retirado' => $valor_retiro,
                    'valor_comission' => $valor_comision,
                    'valor_recibir' => $v_retiro_final2
                ]);

            $this->mailer->send($eml);


            return $this->errorsInterface->succes_message('Solicitud de retiro creada correctamente, se enviará un correo al administrador para su aprobación');
        }


        return $this->errorsInterface->form_errors($form);
    }

    private function actualizar_disponibilidad(EntityManagerInterface $entityManager, $user,$valor_total )
    {
        $gananciasVendedor = $entityManager->getRepository(Ganancia::class)->findOneBy(['login' => $user]);
        $gananciasVendedor->setDisponible($gananciasVendedor->getDisponible() - $valor_total);
        $entityManager->flush();
    }


    #[Route('/api/historial_retiros', name: 'historial_retiros', methods: ['GET'])]
    #[OA\Tag(name: 'Transacciones')]
    #[OA\Response(
        response: 200,
        description: 'Lista de solicitudes de retiro'
    )]
    #[OA\Parameter(
        name: "estado",
        in: "query",
        description: "Filtrar historial de retiros por estado."
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function historial_retiros(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $allowedParams = [
            'estado'
        ];
        // Obtener todos los parámetros enviados
        $queryParams = array_keys($request->query->all());
        // Detectar parámetros no permitidos
        $invalidParams = array_diff($queryParams, $allowedParams);

         if (!empty($invalidParams)) {
            return $this->errorsInterface->error_message(
                'Parámetros no permitidos',
                Response::HTTP_BAD_REQUEST
            );
        }

        $query_estados = $request->query->get('estado');
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login' => $user]);
        $comision = $tienda->getComision();

        $data_url= $entityManager->getRepository(GeneralesApp::class)->findOneBy(['nombre'=>'admin','atributoGeneral'=>'Url']);
        $gananciasVendedor = $entityManager->getRepository(Ganancia::class)->findOneBy(['login' => $user]);

        $retiros = $entityManager->getRepository(Retiros::class)->retiros_filter($gananciasVendedor, $query_estados);
        $retirosArray = [];
        $retiros_pendientes = $entityManager->getRepository(Retiros::class)->findBy(['ganancia' => $gananciasVendedor, 'estado' => 'PENDING']);
        $total_retiros_pendientes = 0;
        $total_recibir_pendientes_final = 0;
        $total_comision_pendientes = 0;
        $calculo_comision = 0;
        $tatal_recibir_d = 0;

        $retiros_aprobados = [];
        $pendientes = [];
        $disponibles = [];

        foreach ($retiros_pendientes as $retiro) {
            $total_retiros_pendientes += $retiro->getRetiro();
            $total_recibir_pendientes_final += $retiro->getRetiroFinal();
            $total_comision_pendientes += $retiro->getComisionShopby();
        }

        $retiros_aprovados = $entityManager->getRepository(Retiros::class)->findBy(['ganancia' => $gananciasVendedor, 'estado' => 'APPROVED']);
        $total_retiros = 0;
        $total_comision = 0;
        $total_recibir = 0;
        foreach ($retiros_aprovados as $retiro) {

            $total_retiros += $retiro->getRetiro();
            $total_comision += $retiro->getComisionShopby();
            $total_recibir += $retiro->getRetiroFinal();

        }


        $retiros_aprobados = [
            'subtotal' => $total_retiros,
            'comision_shopby' => $total_comision,
            'total' => $total_recibir
        ];

        $pendientes = [
            'subtotal' => $total_retiros_pendientes,
            'comision_shopby' => $total_comision_pendientes,
            'total' => $total_recibir_pendientes_final
        ];

        $calculo_comision = ($gananciasVendedor->getDisponible() * $comision) / 100;
        $tatal_recibir_d = $gananciasVendedor->getDisponible() - $calculo_comision;

        $disponibles = [
            'subtotal' => $gananciasVendedor->getDisponible(),
            'comision_shopby' => $calculo_comision,
            'total' => $tatal_recibir_d
        ];

        foreach ($retiros as $retiro) {
            $banco = null;
            if ($retiro->getBanco()) {
                $banco = [
                    'id' => $retiro->getBanco() ? $retiro->getBanco()->getId() : '',
                    'nombre_cuenta' => $retiro->getBanco() ? $retiro->getBanco()->getNombreCuenta() : '',
                    'numero_cuenta' => $retiro->getBanco() ? $retiro->getBanco()->getNumeroCuenta() : '',
                    'tipo_cuenta' => $retiro->getBanco() ? $retiro->getBanco()->getTipoCuenta() : '',
                    'banco' => $retiro->getBanco() ? $retiro->getBanco()->getBanco() : ''
                ];
            }
            $retirosArray[] = [
                'id' => $retiro->getId(),
                'fecha' => $retiro->getFecha(),
                'subtotal' => $retiro->getRetiro(),
                'comision_shopby' => $retiro->getComisionShopby(),
                'total' => $retiro->getRetiroFinal(),
                'estado_retiro' => $retiro->getEstado(),
                'cuenta' => $banco,
                'comentario' => $retiro->getComentario(),
                'comprobante' => $retiro->getComprobante() ? $data_url->getValorGeneral() . '/' . $retiro->getComprobante() : ''

            ];

        }
        
        return $this->json(['historial_retiros' => $retirosArray, 'total_retirado' => $retiros_aprobados, 'saldo_disponible' => $disponibles, 'pendiente_por_acreditar' => $pendientes]);
    }



    #[Route('/api/actualizar_pedido/{pedido}', name: 'update_pedido', methods: ['PUT'])]
    #[OA\Tag(name: 'Pedidos')]
    #[OA\RequestBody(
        description: 'Actualizar estado de entrega de un pedido por  numero de pedido',
        content: new Model(type: PedidosType::class)
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function update_pedido(Request $request, $pedido, EntityManagerInterface $entityManager): Response
    {

        $user = $this->getUser();
        if (!$user instanceof Login) {
            return $this->errorsInterface->error_message('No inicio session.', Response::HTTP_UNAUTHORIZED);
        }

        $pedido_obj = $entityManager->getRepository(Pedidos::class)->findOneBy(['numero_pedido' => $pedido, 'estado' => 'APPROVED']);

        if (!$pedido_obj) {
            return $this->errorsInterface->error_message('Pedido no encontrado', Response::HTTP_NOT_FOUND);
        }

        $email_vendedor = $pedido_obj->getTienda()->getLogin()->getEmail();
        $nombre = $pedido_obj->getTienda()->getLogin()->getUsuarios()->getNombre();
        $apellido = $pedido_obj->getTienda()->getLogin()->getUsuarios()->getApellido();
        $user_pedido = $pedido_obj->getTienda()->getLogin();
        $pedido_cliente = $pedido_obj->getLogin()->getId();
        $tienda_pedido = $pedido_obj->getTienda()->getId();
        $tipo_envio = $pedido_obj->getTipoEnvio();


        $email_user = $pedido_obj->getLogin()->getEmail();
        $nombre_user = $pedido_obj->getLogin()->getUsuarios()->getNombre();
        $apellido_user = $pedido_obj->getLogin()->getUsuarios()->getApellido();
        $tienda_user = $user->getTiendas()->getId();
        $estadoActualEnvio = $pedido_obj->getEstadoEnvio()->getId();
        $estadoActualRetiro = $pedido_obj->getEstadoRetiro()->getId();

        $guia = null;

        $datos = [];
        $detalles = $pedido_obj->getDetallePedidos();
        foreach ($detalles as $detalle) {


            $s = $detalle->getIdVariacion() ? $detalle->getIdVariacion()->getId() : null;

            $imagenesArray = [];
            $terminsoArray = [];


            if ($s != null) {

                $variacion = $detalle->getIdVariacion();

                if ($variacion->getVariacionesGalerias()->isEmpty()) {

                    foreach ($detalle->getIdProductos()->getProductosGalerias() as $galeria) {
                        $imagenesArray[] = [

                            'url' => $galeria->getUrlProductoGaleria(),
                        ];
                    }

                } else {

                    foreach ($variacion->getVariacionesGalerias() as $galeria) {
                        $imagenesArray[] = [
                            'url' => $galeria->getUrlVariacion(),
                        ];
                    }

                }

                foreach ($detalle->getIdVariacion()->getTerminos() as $termino) {

                    $terminsoArray[] = [
                        'nombre' => $termino->getNombre()
                    ];
                }

            } else {

                foreach ($detalle->getIdProductos()->getProductosGalerias() as $galeria) {
                    $imagenesArray[] = [
                        'url' => $galeria->getUrlProductoGaleria()
                    ];
                }

            }

            $datos[] = [
                'nombre_producto' => $detalle->getNombreProducto(),
                'cantidad' => $detalle->getCantidad(),
                'subtotal' => $detalle->getSubtotal(),
                'iva' => $detalle->getImpuesto(),
                'direccion' => $detalle->getDireccionRemite(),
                'ciudad' => $detalle->getCiudadRemite(),
                'tipo_entrega' => $detalle->getIdProductos()->getEntrgasTipo()->getTipo(),
                'tipo_producto' => $detalle->getIdProductos()->getProductosTipo() ? $detalle->getIdProductos()->getProductosTipo()->getTipo() : '',
                'terminos' => $terminsoArray,
                'imagenes' => $imagenesArray

            ];
        }

        $ordenEstadosEnvio = [
            19 => 1,
            26 => 2, // listo para retirar
            20 => 3,// retirado
            21 => 4,// en camino
            22 => 5//entregado
        ];

        $ordenEstadosRetiro = [
            19 => 1,
            26 => 2, // listo para retirar 
            22 => 3  // entregado 
        ];

        $form = $this->createForm(PedidosType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $estadoEnvioNuevo = $form->get('estado_envio')->getData();
            $estadoRetiroNuevo = $form->get('estado_retiro')->getData();

            $estadoEnvioNuevo = (int) $estadoEnvioNuevo;
            $estadoActualEnvio = (int) $estadoActualEnvio;


            // Validar permisos de tienda según el tipo de estado
            if ($tienda_pedido !== $tienda_user) {
                if (!empty($estadoEnvioNuevo) && in_array($estadoEnvioNuevo, [20, 26], true)) {
                    return $this->errorsInterface->error_message('No se puede actualizar el estado de envío de un pedido que no pertenece a tu tienda.', Response::HTTP_BAD_REQUEST);
                }

                if (!empty($estadoRetiroNuevo) && in_array($estadoRetiroNuevo, [26], true)) {
                    return $this->errorsInterface->error_message('No se puede actualizar el estado de retiro en tienda física de un pedido que no pertenece a tu tienda.', Response::HTTP_BAD_REQUEST);
                }
            }

            //verificar si pedido es de cliente antes de marcar como entregado.

            if ($pedido_cliente !== $user->getId()) {
                if (!empty($estadoEnvioNuevo) && in_array($estadoEnvioNuevo, [22], true)) {
                    return $this->errorsInterface->error_message('No se puede marcar como entregado un pedido que no te pertenece.', Response::HTTP_BAD_REQUEST);
                }

                if (!empty($estadoRetiroNuevo) && in_array($estadoRetiroNuevo, [22], true)) {
                    return $this->errorsInterface->error_message('No se puede marcar como entregado un pedido que no te pertenece.', Response::HTTP_BAD_REQUEST);
                }
            }


            // Procesar estado_envio
            if (!empty($estadoEnvioNuevo)) {
                $estadoEnvioObj = $entityManager->getRepository(Estados::class)->findOneBy(['id' => $estadoEnvioNuevo]);
                if ($estadoEnvioObj !== null) {
                    if ($ordenEstadosEnvio[$estadoEnvioNuevo] >= $ordenEstadosEnvio[$estadoActualEnvio]) {
                        $pedido_obj->setEstadoEnvio($estadoEnvioObj);
                    } else {
                        return $this->errorsInterface->error_message('No se puede actualizar el estado de envío hacia un estado anterior.', Response::HTTP_BAD_REQUEST);
                    }

                } else {
                    return $this->errorsInterface->error_message('El estado de envío seleccionado no existe.', Response::HTTP_BAD_REQUEST);
                }
            }

            // Procesar estado_retiro
            if (!empty($estadoRetiroNuevo)) {
                $estadoRetiroObj = $entityManager->getRepository(Estados::class)->findOneBy(['id' => $estadoRetiroNuevo]);
                if ($estadoRetiroObj !== null) {
                    if ($ordenEstadosRetiro[$estadoRetiroNuevo] >= $ordenEstadosRetiro[$estadoActualRetiro]) {
                        $pedido_obj->setEstadoRetiro($estadoRetiroObj);
                    } else {
                        return $this->errorsInterface->error_message('No se puede actualizar el estado de retiro hacia un estado anterior.', Response::HTTP_BAD_REQUEST);
                    }
                } else {
                    return $this->errorsInterface->error_message('El estado de retiro seleccionado no existe.', Response::HTTP_BAD_REQUEST);
                }
            }

            //listo_pararetirar
            if (!empty($estadoEnvioNuevo) && $estadoEnvioNuevo == 26) {



                if ($pedido_obj->getMetodoEnvio()->getId() == 3) {

                    try {
                        $guia = $this->DelivereoService->createBooking_delivereo($pedido_obj->getNumeroPedido());
                        $pedido_obj->setFechaRetirarAdomicilio(new DateTime());
                        $entityManager->flush();
                    } catch (Exception $e) {
                        return $this->errorsInterface->error_message('Error al crear la guía de Delivereo.', $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
                    }

                } elseif ($pedido_obj->getMetodoEnvio()->getId() == 1) {

                    try {
                        $guia = $this->servientregaService->booking_servientrega($pedido_obj->getNumeroPedido());
                        $pedido_obj->setFechaRetirarAdomicilio(new DateTime());
                        $entityManager->flush();
                    } catch (Exception $e) {
                        return $this->errorsInterface->error_message('Error al crear la guía de Servientrega.', $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
                    }
                }
            }

            //retirado por servicios de entrega.
            if (!empty($estadoEnvioNuevo) && $estadoEnvioNuevo == 20) {
                $pedido_obj->setFechaRetiroAdomicilio(new DateTime());
                $entityManager->flush();
            }
            // fecha en camino 
            if (!empty($estadoEnvioNuevo) && $estadoEnvioNuevo == 21) {
                $pedido_obj->setFechaEnCamino(new DateTime());
                $entityManager->flush();
            }
            //entrgado adomicilio
            if (!empty($estadoEnvioNuevo) && $estadoEnvioNuevo == 22) {
                $pedido_obj->setFechaEntregaAdomicilio(new DateTime());
                $entityManager->flush();
            }

            if (!empty($estadoRetiroNuevo) && $estadoRetiroNuevo == 22) {
                $pedido_obj->setFechaEntregoFisico(new DateTime());
                $entityManager->flush();
            }

            //listo para retirar tienda fisica 
            if (!empty($estadoRetiroNuevo) && $estadoRetiroNuevo == 26) {
                $pedido_obj->setFechaRetirarFisico(new DateTime());
                $eml = (new TemplatedEmail())
                    ->to($email_user)
                    ->subject('Ya puedes retirar tu pedido' . ' ' . $pedido_obj->getNumeroPedido() . 'de la tienda.')
                    ->htmlTemplate('pedidos/retiro_pedido_notificacion.html.twig')
                    ->context([
                        'nombre' => $nombre_user . ' ' . $apellido_user,
                        'n_pedido' => $pedido_obj->getNumeroPedido(),
                        'estado' => $pedido_obj->getEstadoEnvio()->getNobreEstado(),
                        'detalle' => $datos,
                        'costo_envio' => $pedido_obj->getCostoEnvio(),
                        'metodo_pago' => $pedido_obj->getMetodoPago()->getNombre()
                    ]);
                $this->mailer->send($eml);

            }

           // $this->gestionarTransacciones->calcularTransacciones($user_pedido);

            return $this->errorsInterface->succes_message('Pedido actualizado correctamente');

        }


        return $this->errorsInterface->form_errors($form);
    }


    #[Route('/api/cancelar_pedido/{pedido}', name: 'cancelar_pedidio', methods: ['POST'])]
    #[OA\Tag(name: 'Pedidos')]
    #[OA\Response(
        response: 200,
        description: 'Cancelar pedido como cliente'
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function cancelar_pedido_cliente($pedido, EntityManagerInterface $entityManager): Response
    {

        $pedido_obj = $entityManager->getRepository(Pedidos::class)->pediddos_pendientes($pedido);

        if (!$pedido_obj) {
            return $this->errorsInterface->error_message('Pedido no encontrado', Response::HTTP_NOT_FOUND);
        }



        $email_vendedor = $pedido_obj->getTienda()->getLogin()->getEmail();
        $nombre = $pedido_obj->getTienda()->getLogin()->getUsuarios()->getNombre();
        $apellido = $pedido_obj->getTienda()->getLogin()->getUsuarios()->getApellido();

        if ($pedido_obj->getEstado() === 'PENDING') {

            $cancelado = $entityManager->getRepository(Estados::class)->findOneBy(['id' => 24]);

            $pedido_obj->setEstado('CANCELLED');

            $pedido_obj->setEstadoEnvio($cancelado);

            $pedido_obj->setEstadoRetiro($cancelado);

            $pedido_obj->setFechaRechazo(new DateTime());

            $entityManager->flush();

            $eml = (new TemplatedEmail())
                ->to($email_vendedor)
                ->subject('El pedido ha sido cancelado.')
                ->htmlTemplate('pedidos/cancelar_pedido_cliente.html.twig') // Especifica la plantilla Twig para el cuerpo HTML
                ->context([
                    'nombre' => $nombre . ' ' . $apellido,
                    'n_pedido' => $pedido_obj->getNumeroPedido(),
                    'estado' => $pedido_obj->getEstado()
                ]);

            $this->mailer->send($eml);

            return $this->errorsInterface->succes_message('Pedido cancelado', ['id_pedido' => $pedido_obj->getId()]);

        } else {

            return $this->errorsInterface->error_message('Solo se puede cancelar pedidos en estado pendinete', Response::HTTP_BAD_REQUEST);
        }

    }


    #[Route('/api/rechazar_venta/{pedido}', name: 'rechazar_venta', methods: ['POST'])]
    #[OA\Tag(name: 'Pedidos')]
    #[OA\Response(
        response: 200,
        description: 'Rechazar venta como vendedor'
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function cancelar_pedido_vendedor($pedido, EntityManagerInterface $entityManager): Response
    {

        $pedido_obj = $entityManager->getRepository(Pedidos::class)->pediddos_pendientes($pedido);

        if (!$pedido_obj) {
            return $this->errorsInterface->error_message('Pedido no encontrado', Response::HTTP_NOT_FOUND);
        }

        $email_vendedor = $pedido_obj->getTienda()->getLogin()->getEmail();
        $nombre = $pedido_obj->getTienda()->getLogin()->getUsuarios()->getNombre();
        $apellido = $pedido_obj->getTienda()->getLogin()->getUsuarios()->getApellido();


        $email_user = $pedido_obj->getLogin()->getEmail();
        $nombre = $pedido_obj->getLogin()->getUsuarios()->getNombre();
        $apellido = $pedido_obj->getLogin()->getUsuarios()->getApellido();

        if ($pedido_obj->getEstado() === 'PENDING') {

            $cancelado = $entityManager->getRepository(Estados::class)->findOneBy(['id' => 24]);

            $pedido_obj->setEstado('REJECTED');

            $pedido_obj->setEstadoEnvio($cancelado);


            $pedido_obj->setEstadoRetiro($cancelado);

            $pedido_obj->setFechaRechazo(new DateTime());

            $entityManager->flush();


            $eml = (new TemplatedEmail())
                ->to($email_user)
                ->subject('El pedido ha sido rechazado.')
                ->htmlTemplate('pedidos/cancelar_pedido_vendedor.html.twig') // Especifica la plantilla Twig para el cuerpo HTML
                ->context([
                    'nombre' => $nombre . ' ' . $apellido,
                    'n_pedido' => $pedido_obj->getNumeroPedido(),
                    'estado' => $pedido_obj->getEstado()
                ]);

            $this->mailer->send($eml);

            return $this->errorsInterface->succes_message('Pedido cancelado', ['id_pedido' => $pedido_obj->getId()]);

        } else {

            return $this->errorsInterface->error_message('Solo se puede cancelar pedidos en estado pendinete', Response::HTTP_BAD_REQUEST);

        }

    }


    #[Route('/api/guias_web/{pedido}', name: 'guias_web', methods: ['POST'])]
    #[OA\Tag(name: 'Pedidos')]
    #[OA\Response(
        response: 200,
        description: 'Crea guia para entrega de pedido por servientrega'
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function servientrega($pedido, EntityManagerInterface $entityManager): Response
    {
        $impusto2 = $entityManager->getRepository(Impuestos::class)->findOneBy(['id' => 2]);
        $seguro_envio = $impusto2->getIva();
        $user = $this->getUser();
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login' => $user]);
        $pedidos = $entityManager->getRepository(DetallePedido::class)->servi_guias($pedido, $tienda);


        if (!$pedidos) {
            return $this->errorsInterface->error_message('No se encontro el pedido', Response::HTTP_NOT_FOUND);
        }


        foreach ($pedidos as $pedido) {
            if ($pedido->getPedido()->getGuiaContador() == 1) {
                return $this->errorsInterface->error_message('Las guías ya fueron generadas.', Response::HTTP_CONFLICT);
            }
        }

        $pedidosArray = [];
        $camposVacios = [];
        foreach ($pedidos as $pedido) {


            $terminosString = '';

            $direccion = $pedido->getIdDireccion();


            $variaciones = $pedido->getIdVariacion() ? $pedido->getIdVariacion() : null;

            if ($variaciones !== null) {
                foreach ($variaciones->getTerminos() as $termino) {

                    // Concatenar los términos en un solo string
                    $terminosString .= $termino->getAtributos()->getNombre() . ': ' . $termino->getNombre() . ', ';
                }

                // Eliminar la coma y el espacio extra al final
                $terminosString = rtrim($terminosString, ', ');
            }

            // Agrupar por direccion el producto
            if (!isset($pedidosArray[$direccion])) {
                $id_ciudad = $pedido->getPedido()->getIdDireccion();
                $ciudad = $entityManager->getRepository(Ciudades::class)->find($id_ciudad);
                $fullName = $pedido->getPedido()->getCustomer();

                $parts = preg_split('/[ -]/', $fullName, 2); // Divide por espacio o guion

                $nombre = $parts[0];
                $apellido = isset($parts[1]) ? $parts[1] : '';
                $pedidosArray[$direccion] = [

                    'id_pedido' => $pedido->getPedido()->getId(),
                    'n_pedido' => $pedido->getPedido()->getNumeroPedido(),
                    'fecha_pedido' => $pedido->getPedido()->getFechaPedido(),
                    'observacion' => $pedido->getIdProductos()->getDirecciones() ? $pedido->getIdProductos()->getDirecciones()->getObservacion() : '',
                    'cliente' => [
                        'id_ciudad_envio' => $ciudad->getIdServientrega(),
                        'ciudad_envio' => $pedido->getPedido()->getCustomerCity(),
                        'direccion_principal' => $pedido->getPedido()->getDireccionPrincipal(),
                        'direccion_secundaria' => $pedido->getPedido()->getDireccionSecundaria(),
                        'codigo_postal' => $pedido->getPedido()->getCodigoPostalCustomer(),
                        'ubicacion_referencia' => $pedido->getPedido()->getUbicacionReferencia(),
                        'nombre' => $nombre,
                        'apellido' => $apellido,
                        'dni' => $pedido->getPedido()->getDniCustomer(),
                        'celular' => $pedido->getPedido()->getCelularCustomer()
                    ],

                    'vendedor' => [
                        'id_ciudad_remite' => $pedido->getIdDireccion(),
                        'ciudad_remite' => $pedido->getCiudadRemite(),
                        'direccion_remite' => $pedido->getDireccionRemite() . ',' . $pedido->getReferencia(),
                        'nombre' => $pedido->getIdProductos()->getTienda()->getLogin()->getUsuarios()->getNombre(),
                        'apellido' => $pedido->getIdProductos()->getTienda()->getLogin()->getUsuarios()->getApellido(),
                        'dni' => $pedido->getIdProductos()->getTienda()->getLogin()->getUsuarios()->getDni(),
                        'celular' => $pedido->getIdProductos()->getTienda()->getLogin()->getUsuarios()->getCelular(),
                        'codigo_postal' => $pedido->getIdProductos()->getDirecciones() ? $pedido->getIdProductos()->getDirecciones()->getCodigoPostal() : '',
                        'n_casa' => $pedido->getIdProductos()->getDirecciones() ? $pedido->getIdProductos()->getDirecciones()->getNCasa() : ''
                    ],

                    'productos' => '',
                    'peso_total' => null,
                    'cantidad_total' => null,
                    'valor_total' => null,
                    'valor_asegurado' => null
                ];


            }
            $peso = $pedido->getIdProductos()->getPeso() * $pedido->getCantidad();
            $productosString = $pedido->getIdProductos()->getNombreProducto() . '' . $terminosString . ',';
            $pedidosArray[$direccion]['peso_total'] += $peso;
            $pedidosArray[$direccion]['cantidad_total'] += $pedido->getCantidad();
            $pedidosArray[$direccion]['valor_total'] += $pedido->getSubtotal();
            $pedidosArray[$direccion]['valor_asegurado'] += $pedido->getSubtotal() * $seguro_envio;
            $pedidosArray[$direccion]['productos'] .= $productosString;


            // Validar campos nulos en cliente
            foreach ($pedidosArray[$direccion]['cliente'] as $key => $value) {
                // Excluir los campos 'ubicacion_referencia' y 'codigo_postal' de la validación
                if ($key !== 'ubicacion_referencia' && $key !== 'codigo_postal' && $value === null) {
                    $camposVacios[] = "El campo '$key' del cliente está vacío.";
                }
            }

            // Validar campos nulos en vendedor
            foreach ($pedidosArray[$direccion]['vendedor'] as $key => $value) {
                if ($value === null) {
                    $camposVacios[] = "El campo '$key' del vendedor está vacío.";
                }
            }

        }


        if (!empty($camposVacios)) {

            return $this->errorsInterface->error_message('Campos vacíos en la información de la guía.', Response::HTTP_BAD_REQUEST, null, $camposVacios);
        }



        $serviGuiasResponse = $this->servientregaService->servi_guias($pedidosArray, $tienda);

        if (isset($serviGuiasResponse['success']) && $serviGuiasResponse['success'] === true) {
            // Si la función se ejecutó correctamente, retornar el mensaje
            return $this->errorsInterface->succes_message(
                $serviGuiasResponse['message'],
                null,
                ['contenido' => $pedidosArray]
            );
        } else {
            // Si hubo un error, retornar el mensaje de error
            return $this->errorsInterface->error_message(
                $serviGuiasResponse['message'],
                $serviGuiasResponse['status'],
                null,
                $pedidosArray
            );
        }

    }



    #[Route('/api/generar_guia/{servi}', name: 'generar_gia_servientrega', methods: ['GET'])]
    #[OA\Tag(name: 'Pedidos')]
    #[OA\Response(
        response: 200,
        description: 'Genera vista en pdf de la guia servientrega creada mediante el codigo de guia de servientrega'
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function generarguia(
        $servi,
        ServientregaRepository $servientregaRepository,
        EntityManagerInterface $entityManager
    ): Response {

        if (empty($servi)) {

            return $this->errorsInterface->error_message(
                'Parámetro "servi" no proporcionado',
                Response::HTTP_BAD_REQUEST
            );
        }

        $orden = $servientregaRepository->findOneBy(['codigo_servientrega' => $servi, 'metodo_envio' => 1]);

        if (!$orden) {
            return $this->errorsInterface->error_message(
                'Guía no encontrada en la base de datos',
                Response::HTTP_NOT_FOUND
            );
        }

        // 1. Mantener formato original de la URL
        $codigo_orden = $orden->getCodigoServientrega();
        $url =$this->servientregaService->pdf_servientrega($codigo_orden);
    

        // 2. Configuración cURL idéntica al original
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_URL, value: $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // 3. Añadir solo mejoras críticas sin cambiar estructura
        curl_setopt($ch, CURLOPT_FAILONERROR, false); // Para manejar códigos 400-500
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $result = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Siempre cerramos el handle
        curl_close($ch);

        try {
            // 1) Si hubo un error en cURL, lo devolvemos
            if ($curlErr) {
                return $this->errorsInterface->error_message(
                    'cURL error: ' . $curlErr,
                    Response::HTTP_BAD_REQUEST
                );
            }

            // 2) Si ya tenía el PDF en local, no llamamos a la API
            if (!empty($orden->getArchivo())) {
                return $this->json([
                    'message' => 'PDF obtenido en local.',
                    'guia' => $orden->getCodigoServientrega(),
                    'archivoEncriptado' => $orden->getArchivo(),
                ], Response::HTTP_OK);
            }

            // 3) La API devolvió 201 → procesamos JSON
            if ($httpCode === 201) {
                $decoded = json_decode($result, true);

                // 3.b) Extraemos valores
                $guia = $decoded['guia'] ?? null;
                $mensajeApi = $decoded['mensaje'] ?? '';
                $archivoEncriptado = $decoded['archivoEncriptado'] ?? null;

                // 3.c) Guardamos el archivo en BD
                $orden->setArchivo($archivoEncriptado);
                $entityManager->flush();

                return $this->json([
                    'message' => $mensajeApi,
                    'guia' => $guia,
                    'archivoEncriptado' => $archivoEncriptado,
                ], Response::HTTP_CREATED);
            }

            // 4) Cualquier otro código HTTP → sin contenido

            return $this->errorsInterface->error_message(
                'No se encontro PDF.',
                Response::HTTP_NO_CONTENT
            );

        } catch (\Throwable $e) {
            // Capturamos cualquier excepción y devolvemos detalle
            return $this->errorsInterface->error_message(
                $e->getMessage(),
                $httpCode >= 400 && $httpCode < 600 ? $httpCode : Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

    }


    #[Route('/retry/email_pedido/{pedido}', name: 'retry_email_pedido', methods: ['GET'])]
    #[OA\Tag(name: 'Pedidos')]
    #[OA\Response(
        response: 200,
        description: 'Vuelve a enviar correo de pedido al vendedor '
    )]
    public function retry_pedido_email(EntityManagerInterface $entityManager, $pedido = null): Response
    {

        if ($pedido === null) {

            return $this->errorsInterface->error_message('Parámetro no proporcionado', Response::HTTP_BAD_REQUEST);
        }

        $pedido_obj = $entityManager->getRepository(Pedidos::class)->findOneBy(['numero_pedido' => $pedido]);

        if (!$pedido_obj) {
            return $this->errorsInterface->error_message('Pedido no encontrado', Response::HTTP_NOT_FOUND);
        }


        $email_vendedor = $pedido_obj->getTienda()->getLogin()->getEmail();
        $nombre = $pedido_obj->getTienda()->getLogin()->getUsuarios()->getNombre();
        $apellido = $pedido_obj->getTienda()->getLogin()->getUsuarios()->getApellido();
        $user = $pedido_obj->getTienda()->getLogin()->getId();


        $email_user = $pedido_obj->getLogin()->getEmail();
        $nombre_user = $pedido_obj->getLogin()->getUsuarios()->getNombre();
        $apellido_user = $pedido_obj->getLogin()->getUsuarios()->getApellido();

        $datos = [];
        $detalles = $pedido_obj->getDetallePedidos();
        foreach ($detalles as $detalle) {


            $s = $detalle->getIdVariacion() ? $detalle->getIdVariacion()->getId() : null;

            $imagenesArray = [];
            $terminsoArray = [];


            if ($s != null) {

                $variacion = $detalle->getIdVariacion();

                if ($variacion->getVariacionesGalerias()->isEmpty()) {

                    foreach ($detalle->getIdProductos()->getProductosGalerias() as $galeria) {
                        $imagenesArray[] = [

                            'url' => $galeria->getUrlProductoGaleria(),
                        ];
                    }

                } else {

                    foreach ($variacion->getVariacionesGalerias() as $galeria) {
                        $imagenesArray[] = [
                            'url' => $galeria->getUrlVariacion(),
                        ];
                    }

                }

                foreach ($detalle->getIdVariacion()->getTerminos() as $termino) {

                    $terminsoArray[] = [
                        'nombre' => $termino->getNombre()
                    ];
                }

            } else {

                foreach ($detalle->getIdProductos()->getProductosGalerias() as $galeria) {
                    $imagenesArray[] = [
                        'url' => $galeria->getUrlProductoGaleria()
                    ];
                }

            }

            $datos[] = [
                'nombre_producto' => $detalle->getNombreProducto(),
                'cantidad' => $detalle->getCantidad(),
                'subtotal' => $detalle->getSubtotal(),
                'iva' => $detalle->getImpuesto(),
                'direccion' => $detalle->getDireccionRemite(),
                'ciudad' => $detalle->getCiudadRemite(),
                'tipo_entrega' => $detalle->getIdProductos()->getEntrgasTipo()->getTipo(),
                'terminos' => $terminsoArray,
                'imagenes' => $imagenesArray

            ];
        }



        $eml = (new TemplatedEmail())
            ->to($email_vendedor, 'crhis117@hotmail.com')
            ->subject('Tienes un pedido: ' . ' ' . $pedido_obj->getNumeroPedido())
            ->htmlTemplate('pedidos/retry_email_customer.html.twig')
            ->context([
                'nombre_customer' => $nombre . ' ' . $apellido,
                'nombre' => $nombre_user . ' ' . $apellido_user,
                'n_pedido' => $pedido_obj->getNumeroPedido(),
                'estado' => $pedido_obj->getEstadoEnvio()->getNobreEstado(),
                'detalle' => $datos,
                'costo_envio' => $pedido_obj->getCostoEnvio(),
                'direccion_cliente' => $pedido_obj->getDireccionPrincipal() . ' ' . $pedido_obj->getDireccionSecundaria(),
                'metodo_pago' => $pedido_obj->getMetodoPago()->getNombre()
            ]);
        $this->mailer->send($eml);

        return $this->errorsInterface->succes_message('Email de Pedido enviado al vendedor');
    }



    #[Route('/ver/servientrega/{id}', name: 'app_ver__guia_servientrega', methods: ['GET'])]
    #[OA\Tag(name: 'Pedidos')]
    #[OA\Response(
        response: 200,
        description: 'Retorna detalle de guía Servientrega'
    )]
    public function servientrega_show(EntityManagerInterface $entityManager, $id = null): Response
    {
        if (!$id) {
            return $this->errorsInterface->error_message('Parámetro no proporcionado', Response::HTTP_BAD_REQUEST);
        }

        $guia = $entityManager->getRepository(Servientrega::class)->findOneBy(['codigo_servientrega' => $id, 'metodo_envio' => 1]);
        if (!$guia) {
            return $this->errorsInterface->error_message('Guía no encontrada', Response::HTTP_NOT_FOUND);
        }

        $codigo = $guia->getCodigoServientrega();
        $respuesta = $this->servientregaService->soap();
        $valor_wsl = $respuesta['wsl'];
        $valor_soap = $respuesta['soap'];

    
        // URL del servicio SOAP
      
        $client = new \SoapClient(null, [
            'location' => $valor_wsl,
            'uri' =>  $valor_soap,
            'trace' => true,
            'exceptions' => true,
        ]);

        // Construye el XML manualmente según la documentación
        $requestXml = <<<XML
       <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                         xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
                         xmlns:ws="https://servientrega-ecuador.appsiscore.com/app/ws/">
           <soapenv:Header/>
           <soapenv:Body>
               <ws:ConsultarGuiaImagen soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                   <guia xsi:type="xsd:string">$codigo</guia>
               </ws:ConsultarGuiaImagen>
           </soapenv:Body>
       </soapenv:Envelope>
       XML;

        try {
            // Realiza la solicitud al servicio SOAP
            $response = $client->__doRequest($requestXml, $valor_wsl, 'ConsultarGuiaImagen', SOAP_1_1);

            // Detectar y convertir la codificación de la respuesta a UTF-8 si es necesario
            if (!mb_detect_encoding($response, 'UTF-8', true)) {
                $response = mb_convert_encoding($response, 'UTF-8', 'ISO-8859-1');
            }

            // Procesa la respuesta como XML
            $xmlResponse = simplexml_load_string($response);

            if ($xmlResponse === false) {
                return $this->errorsInterface->error_message('Error al procesar la respuesta del servicio SOAP', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Extraer el contenido de la etiqueta <Result>
            $resultXml = (string) $xmlResponse->xpath('//Result')[0];
            if (!$resultXml) {
                return $this->errorsInterface->error_message('No se encontró la etiqueta <Result> en la respuesta SOAP', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Decodificar las entidades HTML para obtener un XML válido
            $decodedXml = html_entity_decode($resultXml);

            // Cargar el XML resultante y convertirlo a JSON
            $resultParsed = simplexml_load_string($decodedXml, "SimpleXMLElement", LIBXML_NOCDATA);
            if ($resultParsed === false) {
                return $this->errorsInterface->error_message('Error al procesar el contenido del <Result>', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Convertir el XML a un array JSON
            $resultJson = json_decode(json_encode($resultParsed), true);

            return $this->json($resultJson);
        } catch (\SoapFault $e) {
            // Manejo de errores al comunicarse con el servicio SOAP

            return $this->errorsInterface->error_message('Error al comunicarse con el servicio SOAP', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    #[Route('/lista_datos_guias_servientrega', name: 'listar_guias_servientrega', methods: ['GET'])]
    #[OA\Tag(name: 'Pedidos')]
    #[OA\Response(
        response: 200,
        description: 'Retorna lista de guías Servientrega'
    )]
    public function list_guias(EntityManagerInterface $entityManager): Response
    {
        $guias = $entityManager->getRepository(Servientrega::class)->findBy(['metodo_envio' => 1], ['fecha_registro' => 'DESC']);
        $results = [];

        foreach ($guias as $guia) {
            $codigo = $guia->getCodigoServientrega();
            $respuesta = $this->servientregaService->soap();
            $valor_wsl = $respuesta['wsl'];
            $valor_soap = $respuesta['soap'];
            // URL del servicio SOAP
            

            $client = new \SoapClient(null, [
                'location' => $valor_wsl,
                'uri' => $valor_soap,
                'trace' => true,
                'exceptions' => true,
            ]);

            // Construye el XML manualmente según la documentación
            $requestXml = <<<XML
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                          xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
                          xmlns:ws="https://servientrega-ecuador.appsiscore.com/app/ws/">
            <soapenv:Header/>
            <soapenv:Body>
                <ws:ConsultarGuiaImagen soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                    <guia xsi:type="xsd:string">$codigo</guia>
                </ws:ConsultarGuiaImagen>
            </soapenv:Body>
        </soapenv:Envelope>
        XML;

            try {
                // Realiza la solicitud al servicio SOAP
                $response = $client->__doRequest($requestXml, $valor_wsl, 'ConsultarGuiaImagen', SOAP_1_1);

                // Detectar y convertir la codificación de la respuesta a UTF-8 si es necesario
                if (!mb_detect_encoding($response, 'UTF-8', true)) {
                    $response = mb_convert_encoding($response, 'UTF-8', 'ISO-8859-1');
                }

                // Procesa la respuesta como XML
                $xmlResponse = simplexml_load_string($response);

                if ($xmlResponse === false) {
                    $results[] = [
                        'codigo' => $codigo,
                        'error' => 'Error al procesar la respuesta del servicio SOAP',
                    ];
                    continue; // Salta a la siguiente guía
                }

                // Extraer el contenido de la etiqueta <Result>
                $resultXml = (string) $xmlResponse->xpath('//Result')[0] ?? null;

                if (!$resultXml) {
                    $results[] = [
                        'codigo' => $codigo,
                        'error' => 'No se encontró la etiqueta <Result> en la respuesta SOAP',
                    ];
                    continue;
                }

                // Decodificar las entidades HTML para obtener un XML válido
                $decodedXml = html_entity_decode($resultXml);

                // Cargar el XML resultante y convertirlo a JSON
                $resultParsed = simplexml_load_string($decodedXml, "SimpleXMLElement", LIBXML_NOCDATA);
                if ($resultParsed === false) {
                    $results[] = [
                        'codigo' => $guia->getNPedido(),
                        'error' => 'Error al procesar el contenido del <Result>',
                        'decodedXml' => $decodedXml,
                    ];
                    continue;
                }

                // Convertir el XML a un array JSON
                $resultJson = json_decode(json_encode($resultParsed), true);

                // Extraer los campos específicos
                /*
                $filteredResult = [
                    'IdEstAct' => $resultJson['IdEstAct'] ?? null,
                    'EstAct' => $resultJson['EstAct'] ?? null,
                    'FecEst' => $resultJson['FecEst'] ?? null,
                ];*/



                $results[] = [
                    'codigo' => $guia->getNPedido(),
                    'data' => $resultJson,
                ];
            } catch (\SoapFault $e) {
                // Manejo de errores al comunicarse con el servicio SOAP
                $results[] = [
                    'codigo' => $guia->getNPedido(),
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Retornar todas las respuestas como JSON
        return $this->json($results);
    }


    #[Route('/servientrega/crear-guia', name: 'crear_guia_servientrega_personalizada', methods: ['POST'])]
    #[OA\Tag(name: 'Servientrega')]
    #[OA\Response(
        response: 200,
        description: 'Retorna guia servientrega creada.'
    )]
    public function crearGuia(Request $request, ServientregaService $servientregaService): Response
    {
        $pedidoData = json_decode($request->getContent(), true);

        if (!$pedidoData) {
            return $this->errorsInterface->error_message('Datos de pedido no proporcionados o inválidos.', Response::HTTP_BAD_REQUEST);
        }

        return $servientregaService->custom_serviguias($pedidoData);
    }


}





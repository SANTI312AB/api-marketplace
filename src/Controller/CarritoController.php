<?php

namespace App\Controller;

use App\Entity\Carrito;
use App\Entity\Ciudades;
use App\Entity\Cupon;
use App\Entity\DetalleCarrito;
use App\Entity\Factura;
use App\Entity\FuncionesEspeciales;
use App\Entity\Impuestos;
use App\Entity\Login;
use App\Entity\MetodosEnvio;
use App\Entity\MetodosLogeo;
use App\Entity\Pedidos;
use App\Entity\Usuarios;
use App\Entity\UsuariosDirecciones;
use App\Form\DetalleCarritoType;
use App\Form\FacturaType;
use App\Interfaces\ErrorsInterface;
use App\Repository\CarritoRepository;
use App\Repository\CuponRepository;
use App\Repository\DetalleCarritoRepository;
use App\Repository\FacturaRepository;
use App\Repository\MetodosPagoRepository;
use App\Repository\UsuariosDireccionesRepository;
use App\Service\CarritoService;
use App\Service\DelivereoService;
use App\Service\ServientregaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;

class CarritoController extends AbstractController
{

    private $errorsInterface;
    private $delivereoService;

    private $servientregaService;
    private $carritoService;
    public function __construct(ErrorsInterface $errorsInterface,DelivereoService $delivereoService, ServientregaService $servientregaService, CarritoService $carritoService){
        $this->errorsInterface= $errorsInterface;
        $this->delivereoService= $delivereoService;
        $this->servientregaService= $servientregaService;
        $this->carritoService= $carritoService;

    }
    #[Route('/metodos_pago', name: 'lista_metodos_pago',methods:['GET'])]
    #[OA\Tag(name: 'Metodos')]
    #[OA\Response(
        response: 200,
        description: 'Lista de metodos de pagos'
    )]
    public function pagos_index(MetodosPagoRepository $metodosPagoRepository): Response
    {
        $pagos= $metodosPagoRepository->findBy(['activo'=>true]);
        $pagosArray =[];
        foreach ($pagos as $pago) {
            $pagosArray[]=[
                'nombre'=>$pago->getNombre(),
                'descripcion'=>$pago->getDescripcion(),
                'id'=>$pago->getId(),
            ];
        }
        return $this->json($pagosArray);
    }


    // metodos de inicio de session
    #[Route('/metodos_login', name: 'lista_metodos_login', methods:['GET'])]
    #[OA\Tag(name: 'Metodos')]
    #[OA\Response(
        response: 200,
        description: 'Lista de metodos de inicio de session.'
    )]
    public function metodos_login(EntityManagerInterface $entityManager): Response
    {
        $metodos= $entityManager->getRepository(MetodosLogeo::class)->findBy(['enable'=>true]);

        return $this->json($metodos);
    }

    #[Route('/metodos_envio', name: 'lista_metodos_de_envio', methods: ['GET'])]
    #[OA\Tag(name: 'Metodos')]
    #[OA\Response(
        response: 200,
        description: 'Lista de entrega de producto'
    )]
    #[OA\Parameter(
        name: "direccion_id",
        in: "query",
        description: "Seleccione el id de una dirección de usuario"
    )]
    public function metodos_envio(Request $request, EntityManagerInterface $entityManager): Response
    {
        $allowedParams = [
            'direccion_id'
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
        $direccionId = $request->query->get('direccion_id');

        $user = $this->getUser();
        if (!$user instanceof Login) {
            // Si no hay usuario logueado, no se puede determinar la ciudad.
            // Se puede devolver un error o una lista de métodos con la opción local deshabilitada.
            $user = null;
        }

        $ciudad_user = null;

        if ($user) {
            $usuario = $entityManager->getRepository(Usuarios::class)->findOneBy(['login' => $user]);

            // Determina la dirección del usuario (la seleccionada o la predeterminada)
            $direccionUsuario =  $entityManager->getRepository(UsuariosDirecciones::class)->findOneBy(['id' => $direccionId, 'usuario' => $usuario]);

            if ($direccionUsuario && $direccionUsuario->getCiudad()) {
                $ciudad_user = $direccionUsuario->getCiudad()->getCiudad();
            }
        }

        $allCitiesMatch = false;
        // Solo se puede verificar la coincidencia si tenemos la ciudad del usuario y un carrito
        if ($user && $ciudad_user !== null) {
            $carrito = $entityManager->getRepository(Carrito::class)->findOneBy(['login' => $user]);

            if ($carrito && !$carrito->getDetalleCarritos()->isEmpty()) {
                $allCitiesMatch = true; // Asumimos que coinciden hasta encontrar una discrepancia

                // Normalizamos la ciudad del usuario una sola vez
                $ciudadUsuarioNormalizada = strtolower(trim($ciudad_user));

                foreach ($carrito->getDetalleCarritos() as $detalle) {
                    $producto = $detalle->getIdProducto();
                    $direccionProducto = $producto->getDirecciones();

                    // Si un producto no tiene dirección o ciudad, no hay coincidencia.
                    if (!$direccionProducto || !$direccionProducto->getCiudad()) {
                        $allCitiesMatch = false;
                        break;
                    }

                    $ciudadProducto = $direccionProducto->getCiudad()->getCiudad();
                    $ciudadProductoNormalizada = strtolower(trim($ciudadProducto));

                    // Si las ciudades normalizadas no son iguales, se rompe el bucle.
                    if ($ciudadProductoNormalizada !== $ciudadUsuarioNormalizada) {
                        $allCitiesMatch = false;
                        break;
                    }
                }
            }
        }

        $metodos_envio = $entityManager->getRepository(MetodosEnvio::class)->findBy(['activo' => true]);

        $metodosArray = [];
        foreach ($metodos_envio as $metodo) {
            // La lógica clave:
            // Si el método es el ID 3, su estado "enabled" depende de si todas las ciudades coincidieron ($allCitiesMatch).
            // Para cualquier otro método, "enabled" es siempre true.
            $enabled = $metodo->getId() == 3
                ? $allCitiesMatch
                : true;

            $metodosArray[] = [
                'nombre' => $metodo->getNombre(),
                'descripcion' => $metodo->getDescripcion(),
                'id' => $metodo->getId(),
                'enabled' => $enabled, // Esta es la bandera que solicitaste
                'ciudad_user' => $ciudad_user // Incluimos la ciudad del usuario si está disponible
            ];
        }

        return $this->json($metodosArray);
    }


    #[Route('/api/carrito/new', name: 'nuevo_detalle_carrito', methods: ['POST'])]
    #[OA\Tag(name: 'Carrito')]
    #[OA\RequestBody(
        description: 'Añadir producto al carrito',
        content: new Model(type: DetalleCarritoType::class)
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        try {
            $entityManager->beginTransaction();
            $user = $this->getUser();
            if(!$user instanceof Login){
            return $this->errorsInterface->error_message('Usuario no autenticado',Response::HTTP_UNAUTHORIZED);
            }
            
            $tienda = $user->getTiendas()->getId();
            $carrito = $entityManager->getRepository(Carrito::class)->findOneBy(['login' => $user]);
        
            if (!$carrito) {
            $carrito = new Carrito();
            $carrito->setLogin($user);
            $carrito->setFecha(new \DateTime());
            $entityManager->persist($carrito);
            $entityManager->flush();
            }
        
            $detalleCarrito = new DetalleCarrito();
            $form = $this->createForm(DetalleCarritoType::class, $detalleCarrito, [
            'current_tienda_id' => $tienda,
            ]);
            $form->handleRequest($request);
        
            if (!$form->isValid()) {
            // Capturar errores detallados del formulario
            return $this->errorsInterface->form_error($form, 'Error en la validación de los datos del carrito.');
            }
        
            $IdProducto = $form->get('IdProducto')->getData();
            $cantidad = $form->get('cantidad')->getData();
            $IdVariacion = $form->get('IdVariacion')->getData();
        
            $existingProduct = $entityManager->getRepository(DetalleCarrito::class)
            ->findOneBy(['IdProducto' => $IdProducto, 'carrito' => $carrito]);
        
            if ($existingProduct){
            $existingProduct->setCantidad($cantidad);
            $existingProduct->setIdVariacion($IdVariacion);
            $detalleId = $existingProduct->getId();
            } else {
            $detalleCarrito->setCarrito($carrito);
            $entityManager->persist($detalleCarrito);
            $detalleId = $detalleCarrito->getId();
            }
        
            $entityManager->flush();
            $entityManager->commit();

            return $this->errorsInterface->succes_message(
            $existingProduct ? 'Cantidad actualizada en el carrito.' : 'Añadido al carrito'
            );
        } catch (\Exception $e) {
            $entityManager->rollback();
            return $this->errorsInterface->error_message(
                'Error al añadir producto al carrito: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

    }


    #[Route('/api/carrito', name: 'lista_carrito', methods: ['GET'])]
    #[OA\Tag(name: 'Carrito')]
    #[OA\Response(
        response: 200,
        description: 'Lista de productos del carrito',

    )]
    #[OA\Parameter(
        name: "metodo_envio",
        in: "query",
        description: "Selecciones el id de un metodo de envio"
    )]
    #[OA\Parameter(
        name: "direccion_id",
        in: "query",
        description: "Selecciones el id de una direccion de usario"
    )]
    #[OA\Parameter(
        name: "codigo_cupon",
        in: "query",
        description: "codigo de cupon para descuento de carriot o un producto del carrito"
    )]
    #[OA\Parameter(
        name: "metodo_pago",
        in: "query",
        description: "si establece metodo de pago 3 (paypal) se mostrara costo adicional de paypal"
    )]
    #[OA\Parameter(
        name: "pago_mixto",
        in: "query",
        description: "si se establece pago mixto se completara el total del carrito con el saldo de la cuenta.(boolean)"
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function carrito(Request $request, DetalleCarritoRepository $detalleCarrito, CarritoRepository $carritoRepository, UrlGeneratorInterface $router, EntityManagerInterface $entityManager, UsuariosDireccionesRepository $usuariosDireccionesRepository, CuponRepository $cuponRepository): Response
    {
        $allowedParams = [
            'metodo_envio',
            'direccion_id',
            'codigo_cupon',
            'metodo_pago',
            'pago_mixto'
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
        $direccionId = $request->query->get('direccion_id');

        $domain = $request->getSchemeAndHttpHost();
        $host = $router->getContext()->getBaseUrl();

        $primera_compra = $entityManager->getRepository(FuncionesEspeciales::class)->findOneBy(['id' => 1]);

        $impusto = $entityManager->getRepository(Impuestos::class)->findOneBy(['id' => 1]);
        $iva = $impusto->getIva();
        $impusto2 = $entityManager->getRepository(Impuestos::class)->findOneBy(['id' => 2]);
        $seguro_envio = $impusto2->getIva();

        $free_cities = $entityManager->getRepository(Ciudades::class)->findBy(['free' => true]);
        $envio_gratis = false;

        $metodo_envio = $request->query->get('metodo_envio');

        $m_envio = $entityManager->getRepository(MetodosEnvio::class)->findOneBy(['id' => $metodo_envio, 'activo' => true]);

        if ($m_envio) {
            $id_envio = $m_envio->getId();
        } else {
            $id_envio = '';
        }


        $costo_envio = null;

        $user = $this->getUser();
        $tienda_user = $user->getTiendas();
        $saldo= $user->getSaldo() ? $user->getSaldo()->getSaldo():0;


        $pedidos_aprobados = $entityManager->getRepository(Pedidos::class)->findBy(['login' => $user, 'estado' => 'APPROVED']);


        $carrito = $carritoRepository->findOneBy(['login' => $user]);



        $tipo_pago = $request->query->get('metodo_pago');


        $cr = $request->query->get('codigo_cupon');

        $usuario = $entityManager->getRepository(Usuarios::class)->findOneBy(['login' => $user]);
        $direccionId = $request->query->get('direccion_id');



        $direcciones = $direccionId ?
            $usuariosDireccionesRepository->findOneBy(['id' => $direccionId, 'usuario' => $usuario]) :
            $usuariosDireccionesRepository->findOneBy(['usuario' => $usuario], ['fecha_creacion' => 'DESC']);


        if ($tipo_pago == 3) {
            $costo_paypal = $entityManager->getRepository(Impuestos::class)->findOneBy(['id' => 3]);
            $ipy = $costo_paypal->getIva();
        } else {
            $ipy = 0;
        }

        $ciudad_usuario = NULL;
        $provincia_usuario = NULL;
        $region_usuario = NULL;
        $latitud_usuario = NULL;
        $longitud_usuario = NULL;
        $direcion_principal_usuario = NULL;
        $direcion_secundaria_usuario = NULL;


        if ($direcciones !== null) {

            $ciudad_usuario = $direcciones->getCiudad() ? $direcciones->getCiudad()->getCiudad() : null;
            $provincia_usuario = $direcciones->getCiudad() ? $direcciones->getCiudad()->getProvincia()->getProvincia() : null;
            $region_usuario = $direcciones->getCiudad() ? $direcciones->getCiudad()->getProvincia()->getRegion() : null;
            $latitud_usuario = $direcciones->getLatitud() ? $direcciones->getLatitud() : '';
            $longitud_usuario = $direcciones->getLongitud() ? $direcciones->getLongitud() : '';
            $direcion_principal_usuario = $direcciones->getDireccionP() ? $direcciones->getDireccionP() : '';
            $direcion_secundaria_usuario = $direcciones->getDireccionS() ? $direcciones->getDireccionS() : '';
        }
        $detalleCarritoArray = [];
        $t = 0;
        $t2 = 0;
        $i = 0;
        $e = 0;
        $t3 = 0;
        $t4 = 0;
        $t_p = 0;
        $t_g = 0;
        $calculo_paypal = 0;


        $cupones_usuario = $cuponRepository->cupon_usuario($cr, $user, $tienda_user);

        $cupon_productos = $cuponRepository->cupon_productos($cr, $tienda_user);


        if (empty($cupones_usuario)) {
            // Si no se encuentra un cupón activo para el usuario específico, buscar un cupón activo para todos los usuarios
            $cupones_para_todos = $entityManager->getRepository(Cupon::class)->createQueryBuilder('c')
                ->where('c.codigo_cupon = :codigo_cupon')
                ->andWhere('c.activo = true')
                ->andWhere('(c.tienda IS NULL OR c.tienda <> :tienda_user)')
                ->setParameter('codigo_cupon', $cr)
                ->setParameter('tienda_user', $tienda_user)
                ->getQuery()
                ->getResult();

            $cupones = [];
            // Verificar si alguno de los cupones para todos los usuarios está asignado a otro usuario
            foreach ($cupones_para_todos as $cupon_para_todos) {
                $usuarios_asociados = $cupon_para_todos->getLogin();
                if ($usuarios_asociados->isEmpty()) {
                    // Si el cupón para todos los usuarios no está asignado a ningún usuario, lo agregamos a la lista de cupones disponibles
                    $cupones[] = $cupon_para_todos;
                }
            }
        } else {

            $cupones = $cupones_usuario;
        }

        $immpuestos = 0;
        $subtotal = 0;
        $tipo_descuento = null;
        $descuento = 0;
        $activo = null;
        $gasto_minimo = null;
        $descuento_promocional = null;
        $codigo_cupon = null;
        $descuento_promocional_producto = null;
        $suma_s = 0;
        $suma_s2 = 0;
        $comparacion_cupon = null;
        $productosConCupon = null;
        $con_envio = null;
        $costo_envio_final = 0;
        $costo_envio_total = 0;
        $totalPrecio_total = 0; // Inicializar el total de precio
        $totalPeso_total = 0;
        $valor_asegurado = 0;
        $cn = 0;
        $iva_envio = 0;
        $d_aplicado = null;
        $respuesta = null;
        $subtotal_original_general = 0;


        if (empty($cupon_productos)) {

            foreach ($cupones as $cupon) {
                $tipo_descuento = $cupon->getTipoDescuento();
                $descuento = $cupon->getValorDescuento();
                $activo = $cupon->isActivo();
                $gasto_minimo = $cupon->getGastoMinimo();
                $codigo_cupon = $cupon->getCodigoCupon();
                $con_envio = $cupon->isConEnvio();
            }

        }

        $ciudad_actual = ($direcciones && $direcciones->getCiudad()) ? $direcciones->getCiudad() : null;

        // El envío será gratis si CUALQUIERA de las siguientes condiciones es verdadera.
        $ciudad_actual = ($direcciones && $direcciones->getCiudad()) ? $direcciones->getCiudad() : null;

        // El envío será gratis si CUALQUIERA de las siguientes condiciones es verdadera.
        if (
                // Condición 1: Promoción de primera compra
            (count($pedidos_aprobados) === 0 && $primera_compra->isActivo() && $id_envio == 1) ||

                // Condición 2: No hay una ciudad de destino para verificar
                // (Se mantiene, pues no se puede cobrar si no se sabe el destino)
            ($ciudad_actual === null) ||

                // Condición 3: La ciudad del usuario está explícitamente marcada como gratis
                // Esta condición ahora es la única que depende de la ciudad.
            ($ciudad_actual !== null && $ciudad_actual->isFree())
        ) {
            $envio_gratis = true;
        }

        if ($con_envio = true) {
            $envio_gratis = true;
        }

        // --- PASO 2: Asignar el costo final basado en la decisión ---

        if ($envio_gratis) {
            // Si se cumplió al menos UNA de las condiciones de arriba, el costo es CERO.
            $costo_envio_final = 0;
        } else {
            // Si el envío NO es gratis, procedemos a calcular el costo según el $id_envio.
            if ($id_envio == '' || $id_envio == 1) {
                $costo_envio_final = $this->servientregaService->calculo_envio_global(
                    $carrito,
                    $iva,
                    $seguro_envio,
                    $region_usuario,
                    $provincia_usuario,
                    $ciudad_usuario
                );

            } elseif ($id_envio == 3) {
                $ciudad_usuario = preg_replace('/\s*\(.*?\)\s*/', '', $ciudad_usuario);
                $datos = $entityManager->getRepository(DetalleCarrito::class)->carrito_delivereo($carrito);
                $costo_envio_final = 0; // Reiniciamos para sumar los costos parciales.

                foreach ($datos as $dato) {
                    $costo_parcial = $this->delivereoService->calculate_booking(
                        $direcion_principal_usuario,
                        $direcion_secundaria_usuario,
                        $ciudad_usuario,
                        $dato->getIdProducto()->getDirecciones()->getLatitud(),
                        $dato->getIdProducto()->getDirecciones()->getLongitud(),
                        $latitud_usuario,
                        $longitud_usuario
                    );

                    // Acumulamos el costo solo si es un valor numérico válido.
                    if (is_numeric($costo_parcial)) {
                        $costo_envio_final += $costo_parcial;
                    }
                }
            } else {
                // Opcional: ¿Qué pasa si $id_envio no es ni 1 ni 3? 
                // Es buena práctica definir un comportamiento por defecto.
                $costo_envio_final = 0; // O un costo de envío estándar.
            }
        }

        if ($carrito) {

            $detalles = $carrito->getDetalleCarritos()->toArray();
            usort($detalles, function ($a, $b) {
                return $b->getId() - $a->getId(); // Orden ascendente
            });

            foreach ($detalles as $detalleCarrito) {

                if ($detalleCarrito->getIdProducto()->isDisponibilidadProducto()) {

                    $IdProducto = $detalleCarrito->getIdProducto()->getId();

                    $cupon_p_serch = $request->query->get('codigo_cupon');

                    $c_producto = $cuponRepository->cupon_producto($cupon_p_serch, $IdProducto, $tienda_user);

                    if ($c_producto) {

                        $tipo_descuento_producto = $c_producto->getTipoDescuento();
                        $descuento_producto = $c_producto->getValorDescuento();
                        $activo_producto = $c_producto->isActivo();
                        $codigo_cupon_producto = $c_producto->getCodigoCupon();
                        $gasto_minimo_producto= $c_producto->getGastoMinimo();

                    } else {

                        $tipo_descuento_producto = null;
                        $descuento_producto = null;
                        $activo_producto = null;
                        $codigo_cupon_producto = null;
                        $gasto_minimo_producto=null;
                    }

                    $s = $detalleCarrito->getIdVariacion() ? $detalleCarrito->getIdVariacion()->getId() : null;
                    $tiene_iva = $detalleCarrito->getIdProducto()->isTieneIva();
                    $incluye_impuesos = $detalleCarrito->getIdProducto()->isImpuestosIncluidos();

                    $imagenesArray = [];
                    $terminsoArray = [];
                    if ($s != null) {

                        $nombre_producto = $detalleCarrito->getIdProducto()->getNombreProducto();
                        $precio = $detalleCarrito->getIdVariacion()->getPrecio();
                        $precio_rebajado = $detalleCarrito->getIdVariacion()->getPrecioRebajado();


                        $variacion = $detalleCarrito->getIdVariacion();

                        if ($variacion->getVariacionesGalerias()->isEmpty()) {

                            foreach ($detalleCarrito->getIdProducto()->getProductosGalerias() as $galeria) {
                                $imagenesArray[] = [
                                    'id' => $galeria->getId(),
                                    'url' => $domain . $host . '/public/productos/' . $galeria->getUrlProductoGaleria(),
                                ];
                            }

                        } else {

                            foreach ($variacion->getVariacionesGalerias() as $galeria) {
                                $imagenesArray[] = [
                                    'id' => $galeria->getId(),
                                    'url' => $domain . $host . '/public/productos/' . $galeria->getUrlVariacion(),
                                ];
                            }

                        }

                        foreach ($detalleCarrito->getIdVariacion()->getTerminos() as $termino) {

                            $terminsoArray[] = [
                                'nombre' => $termino->getNombre()
                            ];
                        }

                    } else {

                        $nombre_producto = $detalleCarrito->getIdProducto()->getNombreProducto();
                        $precio = $detalleCarrito->getIdProducto()->getPrecioNormalProducto();
                        $precio_rebajado = $detalleCarrito->getIdProducto()->getPrecioRebajadoProducto();

                        foreach ($detalleCarrito->getIdProducto()->getProductosGalerias() as $galeria) {
                            $imagenesArray[] = [
                                'id' => $galeria->getId(),
                                'url' => $domain . $host . '/public/productos/' . $galeria->getUrlProductoGaleria()
                            ];
                        }

                    }


                    $precioAUsar = ($precio_rebajado !== null && $precio_rebajado !== 0) ? $precio_rebajado : $precio;
                    $precio_unitario = $precioAUsar;
                    $precio_original = $precioAUsar;

                    // Calcular el subtotal original

                    $subtotal_original = $precio_original;


                    $precio_con_descuento = 0;
                    $calculo_descuento = 0;

                    if ($descuento_producto !== null && $detalleCarrito->getCantidad()) {
                        if ($tipo_descuento_producto === 'PORCENTAJE' && $activo_producto) {
                            $calculo_descuento = ($subtotal_original * $descuento_producto) / 100;
                            $precio_con_descuento = $subtotal_original - $calculo_descuento;

                            if ($precio_con_descuento < 0)
                                $precio_con_descuento = 0;

                            $descuento_promocional_producto = [
                                'subtotal' => $subtotal_original,
                                'total' => $precio_con_descuento,
                                'codigo_cupon' => $codigo_cupon_producto,
                                'gasto_minimo'=>$gasto_minimo_producto,
                                'descuento' => $descuento_producto,
                                'tipo_descuento' => $tipo_descuento_producto
                            ];
                        } elseif ($tipo_descuento_producto === 'VALOR' && $activo_producto) {

                            // --- INICIO LÓGICA VALOR FIJO ---

                            // Calculamos el descuento total (Valor del descuento * Cantidad de items)
                            $calculo_descuento = $descuento_producto * $detalleCarrito->getCantidad();

                            // Restamos el descuento al subtotal original
                            $precio_con_descuento = $subtotal_original - $calculo_descuento;

                            // Validación: Evitar que el precio sea negativo
                            if ($precio_con_descuento < 0) {
                                $precio_con_descuento = 0;
                            }

                            // Construimos el array de datos
                            $descuento_promocional_producto = [
                                'subtotal' => $subtotal_original,
                                'total' => $precio_con_descuento,
                                'codigo_cupon' => $codigo_cupon_producto,
                                'gasto_minimo'=>$gasto_minimo_producto,
                                'descuento' => $descuento_producto,
                                'tipo_descuento' => $tipo_descuento_producto
                            ];

                            // --- FIN LÓGICA VALOR FIJO ---

                        } else {
                            $precio_con_descuento = $subtotal_original;
                            $descuento_promocional_producto = null;
                        }
                    } else {
                        $precio_con_descuento = $subtotal_original;
                        $descuento_promocional_producto = null;
                    }

                    $tipo_cobro = $detalleCarrito->getIdProducto() ? $detalleCarrito->getIdProducto()->getCobroServicio() : null;
        

                    $resultadoImpuestos = $this->carritoService->calcularImpuestosProducto(
                    $precio_con_descuento,
                    $precio_unitario,
                    $tiene_iva,
                    $incluye_impuesos,
                    $iva,
                    $detalleCarrito->getCantidad()
                );

                //var_dump($resultadoImpuestos);

                // Extraer resultados
                $subtotal = $resultadoImpuestos['subtotal'];
                $ivaProducto = $resultadoImpuestos['iva'];
                $total = $subtotal + $ivaProducto;
                $subtotal_unitario = $resultadoImpuestos['subtotal_unitario'];
                $iva_unitario = $resultadoImpuestos['iva_unitario'];
                $total_unitario = $resultadoImpuestos['total_unitario'];
                $impuestos = $ivaProducto; // Impuestos calculados
                    $avatarUrl = '';
                    if ($detalleCarrito->getIdProducto()->getTienda()->getLogin()->getUsuarios() && $detalleCarrito->getIdProducto()->getTienda()->getLogin()->getUsuarios()->getAvatar() !== null) {
                        $avatarUrl = $domain . $host . '/public/user/selfie/' . $detalleCarrito->getIdProducto()->getTienda()->getLogin()->getUsuarios()->getAvatar();
                    }

                    $producto_tipo = $detalleCarrito->getIdProducto()->getProductosTipo()->getId();

                    $detalleCarritoArray[] = [
                        'id_producto' => $detalleCarrito->getIdProducto()->getId(),
                        'id_variacion' => $s,
                        'slug' => $detalleCarrito->getIdProducto()->getSlugProducto(),
                        'precio_unitario' => round($subtotal_unitario, 4, PHP_ROUND_HALF_UP),
                        'iva_unitario' => round($iva_unitario, 2, PHP_ROUND_HALF_UP),
                        'total_unitario' => round($total_unitario, 2, PHP_ROUND_HALF_UP),
                        'precio_incluye_iva' => round($incluye_impuesos, 2, PHP_ROUND_HALF_UP),
                        'productos_tipo' => $detalleCarrito->getIdProducto()->getProductosTipo() ? $detalleCarrito->getIdProducto()->getProductosTipo()->getTipo() : '',
                        'nombre_producto' => $nombre_producto,
                        'terminos' => $terminsoArray,
                        'descuento_promocional' => $descuento_promocional_producto,
                        'precio' => round($subtotal, 2, PHP_ROUND_HALF_UP),
                        'iva' => round($impuestos, 2, PHP_ROUND_HALF_UP),
                        'precio_mas_iva' => round($subtotal + $impuestos, 2, PHP_ROUND_HALF_UP),
                        'cantidad' => $detalleCarrito->getCantidad(),
                        'imagenes' => $imagenesArray,
                        'variable' => $detalleCarrito->getIdProducto()->isVariable(),
                        'tipo_entrega' => $detalleCarrito->getIdProducto()->getEntrgasTipo()->getTipo(),
                        'vendedor' => [
                            'avatar' => $avatarUrl,
                            'username' => $detalleCarrito->getIdProducto()->getTienda()->getLogin()->getUsername(),
                            'nombre' => $detalleCarrito->getIdProducto()->getTienda()->getLogin()->getUsuarios()->getNombre(),
                            'apellido' => $detalleCarrito->getIdProducto()->getTienda()->getLogin()->getUsuarios()->getApellido(),
                        ],
                        'producto' => $producto_tipo !== 4 ? [
                            'stock' => $detalleCarrito->getIdProducto()->getCantidadProducto(),
                            'ciudad' => [
                                'id' => $detalleCarrito->getIdProducto()->getDirecciones() ? $detalleCarrito->getIdProducto()->getDirecciones()->getCiudad()->getId() : '',
                                'nombre' => $detalleCarrito->getIdProducto()->getDirecciones() ? $detalleCarrito->getIdProducto()->getDirecciones()->getCiudad()->getCiudad() : '',
                            ],
                            'dimensiones' => [
                                'alto' => $detalleCarrito->getIdProducto()->getAlto(),
                                'ancho' => $detalleCarrito->getIdProducto()->getAncho(),
                                'largo' => $detalleCarrito->getIdProducto()->getLargo(),
                                'peso' => $detalleCarrito->getIdProducto()->getPeso() * $detalleCarrito->getCantidad()
                            ],

                        ] : null
                    ];

                    $suma_s += $subtotal_original;   // sin redondeo, valor bruto
                    $suma_s2 += $precio_con_descuento;
                    $t += $subtotal;                 // acumula sin redondear
                    $i += $impuestos;                // acumula sin redondear
                    $d_aplicado += $calculo_descuento;
                    $subtotal_original_general = $suma_s - $i;

                }

            }

            

        }

        $svd = array_filter($detalleCarritoArray, function ($producto) {
            return !empty($producto['descuento_promocional']);
        });
        $svd = array_values(array: $svd);


        if (!empty($svd)) {
            // Hay al menos 1 producto con cupón
            $respuesta = [
                'codigo_cupon' => $svd[0]['descuento_promocional']['codigo_cupon'],
                'gasto_minimo'=>$svd[0]['descuento_promocional']['gasto_minimo'],
                'descuento' => $svd[0]['descuento_promocional']['descuento'],
                'subtotal' => round($subtotal_original_general, 2, PHP_ROUND_HALF_UP),
                'tipo_descuento' => $svd[0]['descuento_promocional']['tipo_descuento'],
                'productos' => $svd,
            ];
        } elseif (!empty($codigo_cupon)) {
            // Cupón global
            $respuesta = [
                'codigo_cupon' => $codigo_cupon,
                'gasto_minimo'=>$gasto_minimo,
                'descuento' => $descuento,
                'subtotal' => round($subtotal_original_general, 2, PHP_ROUND_HALF_UP),
                'tipo_descuento' => $tipo_descuento,
                'productos' => null,
            ];
        } else {
            $respuesta = null;
        }

        if ($t === 0) {
            $costo_envio_final = 0;
            $i = 0;
            $t2 = 0;
            $t_v = 0;
        } else {
            // 2) Aplicar descuento global (si existe) sobre el subtotal original
            if ($descuento !== null && !empty($codigo_cupon) ) {
                if( $tipo_descuento === 'VALOR' && $activo == true &&( $subtotal_original_general > $gasto_minimo) ) {
                    $t = max($subtotal_original_general - $descuento, 0);
                    $i = ($t * $iva) / 100;
                } elseif ($tipo_descuento === 'PORCENTAJE' && $activo == true &&(  $subtotal_original_general > $gasto_minimo)  ) {
                    $t = max($subtotal_original_general - ($subtotal_original_general * $descuento) / 100, 0);
                    $i = ($t * $iva) / 100;
                } else {
                    // Si el descuento no es válido, no se aplica
                    $t = $subtotal_original_general;
                    $i = ($t * $iva) / 100;
                }
            }
          // 4) Totales intermedios
            $t2 = $t + $i + $costo_envio_final;
            $t_v = $t + $i;
        }

        // Totales generales (redondeo solo aquí)
        $subtotal_mas_iva = round($t + $i, 2, PHP_ROUND_HALF_UP);
        $t2 = round($subtotal_mas_iva + $costo_envio_final, 2, PHP_ROUND_HALF_UP);

        // Comisión PayPal (si aplica)
        if ($ipy > 0) {
            $calculo_paypal = round(($t2 * $ipy) / 100, 2, PHP_ROUND_HALF_UP);
        } else {
            $calculo_paypal = 0;
        }

        $t4 = round($t2 + $calculo_paypal, 2, PHP_ROUND_HALF_UP);

        $pago_mixto = filter_var($request->query->get('pago_mixto'), FILTER_VALIDATE_BOOLEAN);
        $saldoDisponible = $saldo; // ya lo tienes en la variable $saldo
        $totalFinalCarrito = $t4; // total final calculado

        $saldo_usado = 0;
        $monto_pasarela = $totalFinalCarrito;

        // Si el usuario pidió pago mixto
        if ($pago_mixto) {

            // 1. Si no tiene saldo, no puede aplicar mixto
            if ($saldoDisponible <= 0) {
                $saldo_usado = 0;
                $monto_pasarela = $totalFinalCarrito;

            } else {

                // 2. Si tiene saldo, usarlo
                if ($saldoDisponible >= $totalFinalCarrito) {
                    // Todo cubierto por saldo
                    $saldo_usado = $totalFinalCarrito;
                    $monto_pasarela = 0;

                } else {
                    // Parte saldo, parte pasarela
                    $saldo_usado = $saldoDisponible;
                    $monto_pasarela = $totalFinalCarrito - $saldoDisponible;
                }
            }
        } else {
            // Si no es mixto, todo va a pasarela
            $saldo_usado = 0;
            $monto_pasarela = $totalFinalCarrito;
        }

        // Redondeos finales
        $saldo_usado = round($saldo_usado, 2, PHP_ROUND_HALF_UP);
        $monto_pasarela = round($monto_pasarela, 2, PHP_ROUND_HALF_UP);

        return $this->json([
            'productos' => $detalleCarritoArray,
            'subtotal' => round($t, 2, PHP_ROUND_HALF_UP),
            'impuestos' => round($i, 2, PHP_ROUND_HALF_UP),
            'subtotal_mas_iva' => $subtotal_mas_iva,
            'costo_envio' =>round($costo_envio_final,2,PHP_ROUND_HALF_UP ),
            'total' => $t4,
            'descuento_promocional' => $respuesta,
            'comision_paypal' => $calculo_paypal,
            //pago mixto
            'saldo_disponible' => round($saldoDisponible, 2, PHP_ROUND_HALF_UP),
            'saldo_usado' => $saldo_usado,
            'monto_pasarela' => $monto_pasarela,
            'pago_mixto' => $pago_mixto,
        ]);
    }



    #[Route('/api/calcular_costo_envio', name: 'calcular_costo_envio',methods:['GET'])]
    #[OA\Tag(name: 'Carrito')]
    #[OA\Response(
        response: 200,
        description: 'Calcula costo de envio de todo el corrito',
        
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function action(Request $request, EntityManagerInterface $entityManager, UsuariosDireccionesRepository $usuariosDireccionesRepository): Response
    {
    $user = $this->getUser();
    $usuario= $entityManager->getRepository(Usuarios::class)->findOneBy(['login'=>$user]);
    $carrito = $entityManager->getRepository(Carrito::class)->findOneBy(['login' => $user]);

    if (!$carrito) {
        return $this->json(['error' => 'No se encontró un carrito para este usuario'], 404);
    }


    $direccion = $usuariosDireccionesRepository->findOneBy(['usuario' => $usuario], ['fecha_creacion' => 'DESC']);


    $direccion_p= $direccion->getDireccionP() ? $direccion->getDireccionP():null;
    $direccion_s= $direccion->getDireccionS() ? $direccion->getDireccionS():null;
    $ciudad= $direccion->getCiudad() ?$direccion->getCiudad()->getCiudad():null;
    $latitud_usuario=$direccion->getLatitud() ? $direccion->getLatitud():null;
    $longitud_usuario=$direccion->getLongitud() ? $direccion->getLongitud():null;


    if ($ciudad !==null ){
        $ciudad = preg_replace('/\s*\(.*?\)\s*/', '', $ciudad);

        $datos = $entityManager->getRepository(DetalleCarrito::class)->carrito_delivereo($carrito);
        $costo_envio_final=0;
        $data=[];
        foreach ($datos as $dato) {
    
            $costo_envio= $this->delivereoService->calculate_booking($direccion_p,$direccion_s,$ciudad, $dato->getIdProducto()->getDirecciones()->getLatitud(),$dato->getIdProducto()->getDirecciones()->getLongitud(),$latitud_usuario,$longitud_usuario);
    
            $data[]=[
             'tienda_id' => $dato->getIdProducto()->getTienda()->getId(),
             'direccion_p'=>$dato->getIdProducto()->getDirecciones()->getDireccionP(),
             'direccion_s'=>$dato->getIdProducto()->getDirecciones()->getDireccionS(),
             'longitud'=>$dato->getIdProducto()->getDirecciones()->getLongitud(),
             'latitud'=>$dato->getIdProducto()->getDirecciones()->getLatitud(),
             'costo_envio'=>$costo_envio
            ]; 
    
            if (is_numeric($costo_envio)) {
                $costo_envio_final += $costo_envio;
            }else{
                $costo_envio_final=0;
            }
    
           
        }
    }else{
        $data= null;
        $costo_envio_final=0;
    }

    return $this->json(['data'=>$data,'costo_envio_final'=>$costo_envio_final]);
}

  

    #[Route('/api/add_factura', name: 'nueva_factura', methods:['POST'])]
    #[OA\Tag(name: 'Factura')]
    #[OA\RequestBody(
        description: 'Añadir una factura',
        content: new  Model(type: FacturaType::class)
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function add_facturas(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user= $this->getUser();
        $login = $entityManager->getRepository(Login::class)->find($user);

        $factura= new Factura();
        $form= $this->createForm(FacturaType::class,$factura);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() ){
            
            $factura->setLogin($login);
            $entityManager->persist($factura);
            $entityManager->flush();


                return $this->errorsInterface->succes_message('Datos guardados.','data',$factura->getId());

        }

        return $this->errorsInterface->form_errors($form);  
    }


    #[Route('/api/update_factura/{id}', name: 'editar_factura', methods:['PUT'])]
    #[OA\Tag(name: 'Factura')]
    #[OA\RequestBody(
        description: 'Editar una factura',
        content: new  Model(type: FacturaType::class)
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function editar_factura(Request $request,$id, EntityManagerInterface $entityManager): Response
    {
        $user= $this->getUser();
        $factura= $entityManager->getRepository(Factura::class)->findOneBy(['id'=>$id,'login'=>$user]);

        if(!$factura){

           return $this->errorsInterface->error_message('No se encontró la factura.',Response::HTTP_NOT_FOUND);
        }
        
        $form= $this->createForm(FacturaType::class,$factura);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() ){
            
            $entityManager->flush();


            return $this->errorsInterface->succes_message('Datos actualizados.','factura_id',$factura->getId());

        }

        return $this->errorsInterface->form_errors($form);  


    }

    #[Route('/api/lista_facturas', name: 'all_facturas',methods:['GET'])]
    #[OA\Tag(name: 'Factura')]
    #[OA\Response(
        response: 200,
        description: 'Lista de Facturas',
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index_facturas(FacturaRepository $facturaRepository): Response
    {
        $user= $this->getUser();
        $facturas= $facturaRepository->findBy(['login'=>$user,'consumidor_final' => false]);
        $facturasArray=[];

        foreach ($facturas as  $factura) {
            $facturasArray[]=[
                'id'=>$factura->getId(),
                'nombre'=>$factura->getNombre() ? $factura->getNombre():'' ,
                'apellido'=>$factura->getApellido() ? $factura->getApellido():'' ,
                'telefono'=>$factura->getTelefono() ? $factura->getTelefono():'' ,
                'dni'=>$factura->getDni() ? $factura->getDni():'' ,
                'email'=>$factura->getEmail() ? $factura->getEmail():''

            ];
        }
        return $this->json($facturasArray);
       
    }

    #[Route('/api/delete_factura/{id}', name: 'delete_factura',methods:['DELETE'])]
    #[OA\Tag(name: 'Factura')]
    #[OA\Response(
        response: 200,
        description: 'Elimina una factura por su id',
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete_f($id,EntityManagerInterface $entityManager): Response
    { 
        $user = $this->getUser();
        $factura= $entityManager->getRepository(Factura::class)->findOneBy(['id'=>$id,'login'=>$user]);
        if(!$factura){
       

            return $this->errorsInterface->error_message('La factura seleccionado no existe.',Response::HTTP_NOT_FOUND);
        }
        
        $pedidos= $entityManager->getRepository(Pedidos::class)->findBy(['factura'=>$id]);
        
        if($pedidos){

           return $this->errorsInterface->error_message('No se puede eliminar la factura, tiene pedidos asociados',Response::HTTP_CONFLICT);
        }

       

        $entityManager->remove($factura);
        $entityManager->flush();

        return $this->errorsInterface->succes_message('Factura eliminada.');
    }


    #[Route('/api/delete_carrito/{id}', name: 'delete_carrido', methods: ['DELETE'])]
    #[OA\Tag(name: 'Carrito')]
    #[OA\Response(
        response: 200,
        description: 'Elimia un producto del carrito',
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete($id, DetalleCarritoRepository $detalleCarritoRepository, EntityManagerInterface $entityManager): Response
    {

    $user = $this->getUser();
    $carrito = $entityManager->getRepository(Carrito::class)->findOneBy(['login' => $user]);
   
    $detalleCarrito = $detalleCarritoRepository->findOneBy(['IdProducto' => $id, 'carrito' => $carrito]);

    
    if (!$detalleCarrito) {

        return $this->errorsInterface->error_message('No se encontró el producto en el carrito.', Response::HTTP_NOT_FOUND);
    }

    // Elimina el detalle del carrito
    $entityManager->remove($detalleCarrito);
    $entityManager->flush();

    return $this->errorsInterface->succes_message('Producto eliminado del carrito.');

  }

}

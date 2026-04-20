<?php

namespace App\Service;

use App\Entity\Carrito;
use App\Entity\Ciudades;
use App\Entity\Cupon;
use App\Entity\DetalleCarrito;
use App\Entity\FuncionesEspeciales;
use App\Entity\Impuestos;
use App\Entity\Login;
use App\Entity\MetodosEnvio;
use App\Entity\MetodosPago;
use App\Entity\Pedidos;
use App\Entity\Regateos;
use App\Entity\ShopbyInfo;
use App\Entity\TarifasServientrega;
use App\Entity\Usuarios;
use App\Entity\UsuariosDirecciones;
use App\Interfaces\ErrorsInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\ControlStock;
use App\Service\DelivereoService;
use App\Service\ServientregaService;


class CarritoService{

    private $em;

    private $jwtToken;
    private $tokenExpiry;

    private $parameters;

    private $request;

    private $router;

    private $errorsInterface;

    private $controlStock;

    private $delivereoService;

    private $servientregaService;

    public function __construct(EntityManagerInterface $em, ParameterBagInterface $parameters,RequestStack $request,UrlGeneratorInterface $router, ErrorsInterface $errorsInterface,
    ControlStock $controlStock, DelivereoService $delivereoService,ServientregaService $servientregaService
    ){
        $this->em = $em;  // Injecting this->em into the controller.
        $this->parameters = $parameters;  // Injecting this->parameters into the controller.
        $this->request = $request->getCurrentRequest();  // Injecting RequestStack into the controller.
        $this->router = $router;  // Injecting UrlGeneratorInterface into the controller.
        $this->errorsInterface= $errorsInterface;
        $this->controlStock= $controlStock;
        $this->delivereoService= $delivereoService;
        $this->servientregaService= $servientregaService;
    }

    public function calcularImpuestosProducto(
        float $precioTotal,
        float $precioUnitario,
        bool $tieneIva,
        bool $incluyeImpuestos,
        float $porcentajeIva,
        int $cantidad
    ): array {
        // Validaciones iniciales
        if ($precioTotal <= 0 || $cantidad <= 0) {
            return $this->valoresCero();
        }

        $precision = 2;
        $factorIva = 1 + ($porcentajeIva / 100);

        if ($tieneIva && !$incluyeImpuestos) {
            return $this->calcularIvaNoIncluido($precioUnitario, $porcentajeIva, $cantidad, $precision);
        } elseif ($tieneIva && $incluyeImpuestos) {
            return $this->calcularIvaIncluido($precioTotal, $precioUnitario, $porcentajeIva, $cantidad, $precision, $factorIva);
        } else {
            return $this->calcularSinIva($precioTotal, $precioUnitario, $cantidad, $precision);
        }
    }

    private function calcularIvaNoIncluido(float $precioUnitario, float $porcentajeIva, int $cantidad, int $precision): array
    {
        // Calcular unitarios primero
        $subtotalUnitario = $precioUnitario;
        $ivaUnitario = ($subtotalUnitario * $porcentajeIva) / 100;
        $totalUnitario = $subtotalUnitario + $ivaUnitario;

        // Calcular totales
        $subtotal = $subtotalUnitario * $cantidad;
        $ivaProducto = $ivaUnitario * $cantidad;
        $total = $subtotal + $ivaProducto;

        return [
            'subtotal' => round($subtotal, $precision),
            'iva' => round($ivaProducto, $precision),
            'total' => round($total, $precision),
            'subtotal_unitario' => round($subtotalUnitario, $precision),
            'iva_unitario' => round($ivaUnitario, $precision),
            'total_unitario' => round($totalUnitario, $precision)
        ];
    }

    private function calcularIvaIncluido(float $precioTotal, float $precioUnitario, float $porcentajeIva, int $cantidad, int $precision, float $factorIva): array
    {
        // VERIFICACIÓN: Asegurar que el precioTotal sea consistente
        // Si el precioTotal es menor que precioUnitario * cantidad, corregirlo
        $precioTotalEsperado = $precioUnitario * $cantidad;
        if (abs($precioTotal - $precioTotalEsperado) > 0.01) {
            $precioTotal = $precioTotalEsperado;
        }

        $total = $precioTotal;
        $totalUnitario = $precioUnitario;

        // Calcular subtotal exacto (sin IVA)
        $subtotalExacto = $total / $factorIva;
        $ivaExacto = $total - $subtotalExacto;

        // Calcular unitarios
        $subtotalUnitarioExacto = $totalUnitario / $factorIva;
        $ivaUnitarioExacto = $totalUnitario - $subtotalUnitarioExacto;

        return [
            'subtotal' => round($subtotalExacto, $precision),
            'iva' => round($ivaExacto, $precision),
            'total' => round($total, $precision),
            'subtotal_unitario' => round($subtotalUnitarioExacto, $precision),
            'iva_unitario' => round($ivaUnitarioExacto, $precision),
            'total_unitario' => round($totalUnitario, $precision)
        ];
    }

    private function calcularSinIva(float $precioTotal, float $precioUnitario, int $cantidad, int $precision): array
    {
        $subtotalUnitario = $precioUnitario;
        $subtotal = $precioTotal;

        return [
            'subtotal' => round($subtotal, $precision),
            'iva' => 0.00,
            'total' => round($subtotal, $precision),
            'subtotal_unitario' => round($subtotalUnitario, $precision),
            'iva_unitario' => 0.00,
            'total_unitario' => round($subtotalUnitario, $precision)
        ];
    }

    private function valoresCero(): array
    {
        return [
            'subtotal' => 0.00,
            'iva' => 0.00,
            'total' => 0.00,
            'subtotal_unitario' => 0.00,
            'iva_unitario' => 0.00,
            'total_unitario' => 0.00
        ];
    }

    

    public function carito(Login $user, $metodo_pogo = null, $direccionId = null, $metodo_envio = null, $codigo_cupon = null): JsonResponse
    {

        $domain = $this->request->getSchemeAndHttpHost();
        $host = $this->router->getContext()->getBaseUrl();


        $tienda_user = $user->getTiendas();
        $carrito = $this->em->getRepository(Carrito::class)->findOneBy(['login' => $user]);

        if (!$carrito) {

            return $this->errorsInterface->error_message('Error en el carrito.', 404, 'description', 'El carrito no existe.');
        }

        // Check if the cart has items
        if (!$carrito->getDetalleCarritos() || count($carrito->getDetalleCarritos()) === 0) {
            return $this->errorsInterface->error_message(
                'Error en el carrito.',
                400,
                'description',
                'El carrito está vacío.'
            );
        }

        $usuario = $this->em->getRepository(Usuarios::class)->findOneBy(['login' => $user]);

        $direcciones = $direccionId ?
            $this->em->getRepository(UsuariosDirecciones::class)->findOneBy(['id' => $direccionId, 'usuario' => $usuario]) :
            $this->em->getRepository(UsuariosDirecciones::class)->findOneBy(['usuario' => $usuario], ['fecha_creacion' => 'DESC']);

        if ($direcciones !== null) {
            $ciudad_usuario = $direcciones->getCiudad() ? $direcciones->getCiudad()->getCiudad() : null;
            $provincia_usuario = $direcciones->getCiudad() ? $direcciones->getCiudad()->getProvincia()->getProvincia() : null;
            $region_usuario = $direcciones->getCiudad() ? $direcciones->getCiudad()->getProvincia()->getRegion() : null;
            $latitud_usuario = $direcciones->getLatitud() ? $direcciones->getLatitud() : null;
            $longitud_usuario = $direcciones->getLongitud() ? $direcciones->getLongitud() : null;
            $direcion_principal_usuario = $direcciones->getDireccionP() ? $direcciones->getDireccionP() : '';
            $direcion_secundaria_usuario = $direcciones->getDireccionS() ? $direcciones->getDireccionS() : '';
        }

        $primera_compra = $this->em->getRepository(FuncionesEspeciales::class)->findOneBy(['id' => 1]);

        $pedidos_aprobados = $this->em->getRepository(Pedidos::class)->findBy(['login' => $user, 'estado' => 'APPROVED']);


        $m_pago = $this->em->getRepository(MetodosPago::class)->findOneBy(['id' => $metodo_pogo]);


        $impusto = $this->em->getRepository(Impuestos::class)->findOneBy(['id' => 1]);
        $iva = $impusto->getIva();

        $impusto2 = $this->em->getRepository(Impuestos::class)->findOneBy(['id' => 2]);
        $seguro_envio = $impusto2->getIva();

        $free_cities = $this->em->getRepository(Ciudades::class)->findBy(['free' => true]);


        if ($m_pago && $m_pago->getId() == 3) {
            $costo_paypal = $this->em->getRepository(Impuestos::class)->findOneBy(['id' => 3]);
            $ipy = $costo_paypal->getIva();
        } else {
            $ipy = 0;
        }

        $shopby_info= $this->em->getRepository(ShopbyInfo::class)->findOneBy(['id'=>5]);
        $codigo_factuaracion_global= $shopby_info->getDescripcion();


        $ciudad_actual = $direcciones->getCiudad();
        $envio_gratis = false;

        $m_envio = $this->em->getRepository(MetodosEnvio::class)->findOneBy(['id' => $metodo_envio, 'activo' => true]);

        if ($m_envio) {
            $id_envio = $m_envio->getId();
        } else {
            $id_envio = '';
        }

        $i = 0;
        $t2 = 0;
        $t = 0;
        $s_descuento = 0;
        $e = 0;
        $t3 = 0;
        $t_p = 0;
        $calculo_paypal = 0;
        $c_paypal = 0;


        $cupones_usuario = $this->em->getRepository(Cupon::class)->cupon_usuario($codigo_cupon, $user, $tienda_user);

        $cupon_productos = $this->em->getRepository(Cupon::class)->cupon_productos($codigo_cupon, $tienda_user);

        if (empty($cupones_usuario)) {
            // Si no se encuentra un cupón activo para el usuario específico, buscar un cupón activo para todos los usuarios

            $cupones_para_todos = $this->em->getRepository(Cupon::class)->createQueryBuilder('c')
                ->where('c.codigo_cupon = :codigo_cupon')
                ->andWhere('c.activo = true')
                ->andWhere('(c.tienda IS NULL OR c.tienda <> :tienda_user)')
                ->setParameter('codigo_cupon', $codigo_cupon)
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
            // Si se encuentra un cupón activo para el usuario específico, usar ese cupón
            $cupones = $cupones_usuario;
        }

        $tipo_descuento = '';
        $descuento = null;
        $id_cupon = null;
        $gasto_minimo = null;
        $activo = null;
        $con_envio = null;
        $precio_final = 0;
        $suma_s = 0;
        $suma_s2 = 0;
        $descuento_promocional_producto = null;
        $productosConCupon = null;
        $costo_envio_pedido = 0;
        $cn_pedido = 0;
        $iva_envio_pedido = 0;
        $subtotal_unitario = 0;
        $iva_unitario = 0;
        $total_unitario = 0;
        $ivaProducto = 0;
        $calculo_descuento_tienda = 0;
        $subtotal_original_tienda = 0;
        $subtotal_original_general = 0;
        $costo_envio_final = 0;
        $codigo_producto=null;
        $saldo = $user->getSaldo() ? $user->getSaldo()->getSaldo() : 0;


        if (empty($cupon_productos)) {

            foreach ($cupones as $cupon) {
                $id_cupon = $cupon->getId();
                $tipo_descuento = $cupon->getTipoDescuento();
                $descuento = $cupon->getValorDescuento();
                $gasto_minimo = $cupon->getGastoMinimo();
                $activo = $cupon->isActivo();
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

        $productosPorVendedor = [];
        $detalleCarritoArray = [];
        $errors = [];

        $totalGeneral = [
            'subtotal' => 0,
            'iva' => 0,
            'subtotal_mas_iva' => 0,
            'costo_envio_tienda' => 0,
            'comision_paypal' => 0,
            'total' => 0,
            'descuento_cupon' => 0,
            'subtotal_original' => 0,
        ];


        foreach ($carrito->getDetalleCarritos() as $detalleCarrito) {

            if ($detalleCarrito->getIdProducto()->isDisponibilidadProducto()) {

                $producto = $detalleCarrito->getIdProducto();
                $variacion = $detalleCarrito->getIdVariacion();

                $IdProducto = $detalleCarrito->getIdProducto()->getId();


                $codigo_producto = $detalleCarrito->getIdVariacion()?->getCodigoVariante()
                    ?? $detalleCarrito->getIdProducto()?->getCodigoProducto()
                    ?? $codigo_factuaracion_global;

                $nombreCompletoProducto = $producto->getNombreProducto(); // siempre el nombre base

                if ($variacion) {
                    $terminosNombres = [];

                    // Recopilar los nombres de los términos de la variante
                    foreach ($variacion->getTerminos() as $termino) {
                        $terminosNombres[] = $termino->getNombre();
                    }

                    // Si hay términos, los concatenamos
                    if (!empty($terminosNombres)) {
                        $nombreCompletoProducto .= ' (' . implode(', ', $terminosNombres) . ')';
                    }
                }
            
                $cupon_p_serch = $codigo_cupon;

                $c_producto = $this->em->getRepository(Cupon::class)->cupon_producto($cupon_p_serch, $IdProducto, $tienda_user);

                if ($c_producto) {
                    $tipo_descuento_producto = $c_producto->getTipoDescuento();
                    $descuento_producto = $c_producto->getValorDescuento();
                    $activo_producto = $c_producto->isActivo();
                    $codigo_cupon_producto = $c_producto->getCodigoCupon();
                } else {
                    $tipo_descuento_producto = null;
                    $descuento_producto = null;
                    $activo_producto = null;
                    $codigo_cupon_producto = null;
                }

                $cantidad_producto = $detalleCarrito->getIdProducto()->getCantidadProducto();
                $cantidad = $detalleCarrito->getCantidad();

                $errors = $this->controlStock->control_stock($detalleCarrito->getIdProducto(), $detalleCarrito->getIdVariacion(), $detalleCarrito->getCantidad());

                // 4. Fuera del foreach: Verifica y retorna TODOS los errores juntos

                $nombre_producto = $detalleCarrito->getIdProducto()->getNombreProducto();

                $s = $detalleCarrito->getIdVariacion() ? $detalleCarrito->getIdVariacion()->getId() : null;
                $p = $detalleCarrito->getIdProducto() ? $detalleCarrito->getIdProducto()->getId() : null;
                $x = $detalleCarrito->getIdProducto()->getTienda()->getId();

                if ($detalleCarrito->getIdProducto()->getDirecciones() !== null) {
                    // Obtén la ciudad, provincia y región del producto
                    $id_direccion_producto = $detalleCarrito->getIdProducto()->getDirecciones()->getCiudad()->getIdServientrega();
                    $ciudad_producto = $detalleCarrito->getIdProducto()->getDirecciones()->getCiudad() ? $detalleCarrito->getIdProducto()->getDirecciones()->getCiudad()->getCiudad() : null;
                    $provincia_producto = $detalleCarrito->getIdProducto()->getDirecciones()->getCiudad() ? $detalleCarrito->getIdProducto()->getDirecciones()->getCiudad()->getProvincia()->getProvincia() : null;
                    $region_producto = $detalleCarrito->getIdProducto()->getDirecciones()->getCiudad() ? $detalleCarrito->getIdProducto()->getDirecciones()->getCiudad()->getProvincia()->getRegion() : null;
                    $direccion_producto = $detalleCarrito->getIdProducto()->getDirecciones()->getDireccionP() . '-' . $detalleCarrito->getIdProducto()->getDirecciones()->getDireccionS();
                    $tipo_envio = $detalleCarrito->getIdProducto()->getEntrgasTipo()->getId();
                    $referencia_producto = $detalleCarrito->getIdProducto()->getDirecciones() ? $detalleCarrito->getIdProducto()->getDirecciones()->getReferenciaDireccion() : '';
                    $usuario_producto = $detalleCarrito->getIdProducto()->getTienda()->getLogin()->getUsuarios()->getNombre() . ' ' . $detalleCarrito->getIdProducto()->getTienda()->getLogin()->getUsuarios()->getApellido();
                    $celular_producto = $detalleCarrito->getIdProducto()->getTienda()->getLogin()->getUsuarios()->getCelular();
                } else {
                    $id_direccion_producto = null;
                    // Manejo de caso donde $detalleCarrito->getIdProducto()->getDirecciones() es null
                    $ciudad_producto = null;
                    $provincia_producto = null;
                    $region_producto = null;
                    $direccion_producto = null;
                    $tipo_envio = null;
                    $referencia_producto = null;
                    $usuario_producto = null;
                    $celular_producto = null;
                }

                if ($s != null) {

                    $precio = $detalleCarrito->getIdVariacion()->getPrecio();
                    $precio_rebajado = $detalleCarrito->getIdVariacion()->getPrecioRebajado();

                } else {


                    $precio = $detalleCarrito->getIdProducto()->getPrecioNormalProducto();
                    $precio_rebajado = $detalleCarrito->getIdProducto()->getPrecioRebajadoProducto();

                }

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

                $avatarUrl = '';
                if ($detalleCarrito->getIdProducto()->getTienda()->getLogin()->getUsuarios() && $detalleCarrito->getIdProducto()->getTienda()->getLogin()->getUsuarios()->getAvatar() !== null) {
                    $avatarUrl = $domain . $host . '/public/user/selfie/' . $detalleCarrito->getIdProducto()->getTienda()->getLogin()->getUsuarios()->getAvatar();
                }

                $peso_producto = $detalleCarrito->getIdProducto() ? $detalleCarrito->getIdProducto()->getPeso() : 0;

                $tiene_iva = $detalleCarrito->getIdProducto()->isTieneIva();
                $incluye_impuesos = $detalleCarrito->getIdProducto()->isImpuestosIncluidos();

                $tipo_cobro = $detalleCarrito->getIdProducto() ? $detalleCarrito->getIdProducto()->getCobroServicio() : null;
    

                $precioAUsar = ($precio_rebajado !== null && $precio_rebajado !== 0) ? $precio_rebajado : $precio;
                $precio_unitario = $precioAUsar;
                $precio_original = $precioAUsar;

                // Calcular el subtotal original

                $subtotal_original = $precio_original;

                $precio_con_descuento = 0;
                $calculo_descuento = 0;
                $mensaje_error = null;

                if ($descuento_producto !== null) {
                    if ($detalleCarrito->getCantidad() == 1) {
                        if ($tipo_descuento_producto === 'PORCENTAJE' && $activo_producto) {
                            // Lógica Porcentaje
                            $calculo_descuento = ($subtotal_original * $descuento_producto) / 100;
                            $precio_con_descuento = $subtotal_original - $calculo_descuento;

                            if ($precio_con_descuento < 0)
                                $precio_con_descuento = 0;

                            $descuento_promocional_producto = [
                                'subtotal' => $subtotal_original,
                                'total' => $precio_con_descuento,
                                'codigo_cupon' => $codigo_cupon_producto,
                                'descuento' => $descuento_producto,
                                'tipo_descuento' => $tipo_descuento_producto
                            ];

                        } elseif ($tipo_descuento_producto === 'VALOR' && $activo_producto) {
                            // --- INICIO LÓGICA VALOR FIJO ---

                            // Como la cantidad es 1, restamos directamente el valor del descuento
                            $calculo_descuento = $descuento_producto;
                            $precio_con_descuento = $subtotal_original - $calculo_descuento;

                            // Validación para no tener totales negativos
                            if ($precio_con_descuento < 0) {
                                $precio_con_descuento = 0;
                            }

                            $descuento_promocional_producto = [
                                'subtotal' => $subtotal_original,
                                'total' => $precio_con_descuento,
                                'codigo_cupon' => $codigo_cupon_producto,
                                'descuento' => $descuento_producto,
                                'tipo_descuento' => $tipo_descuento_producto
                            ];
                            // --- FIN LÓGICA VALOR FIJO ---

                        } else {
                            // Si no es PORCENTAJE ni VALOR, o el producto no está activo
                            $mensaje_error = 'El tipo de descuento del producto no es válido o está inactivo';
                        }
                    } else {
                        $mensaje_error = 'El cupón solo se aplica a un solo producto';
                    }

                    if (isset($mensaje_error) && $mensaje_error) {
                        return $this->errorsInterface->error_message('Error al aplicar cupon en un solo producto.', 413, 'description', $mensaje_error);
                    }

                } else {
                    $precio_con_descuento = $subtotal_original;
                    $descuento_promocional_producto = null;
                }
                $resultadoImpuestos = $this->calcularImpuestosProducto(
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

                $d_principal = $detalleCarrito->getIdProducto()->getDirecciones() ? $detalleCarrito->getIdProducto()->getDirecciones()->getDireccionP() : '';
                $d_secundaria = $detalleCarrito->getIdProducto()->getDirecciones() ? $detalleCarrito->getIdProducto()->getDirecciones()->getDireccionS() : '';

                $full_direccion = $d_principal . ' ' . $d_secundaria;

               
                if ($con_envio == true && $id_envio == 1) {
                    $envio_gratis = true;
                }

                $detalleCarritoArray[] = [
                    'id' => $detalleCarrito->getId(),
                    'carrito' => $detalleCarrito->getCarrito()->getId(),
                    'id_producto' => $detalleCarrito->getIdProducto()->getId(),
                    'id_variacion' => $s,
                    'slug' => $detalleCarrito->getIdProducto()->getSlugProducto(),
                    'productos_tipo' => $detalleCarrito->getIdProducto()->getProductosTipo() ? $detalleCarrito->getIdProducto()->getProductosTipo()->getTipo() : '',
                    'nombre_producto' => $nombre_producto,
                    'stock' => $detalleCarrito->getIdProducto() ? $detalleCarrito->getIdProducto()->getCantidadProducto() : '',
                    'terminos' => $terminsoArray,
                    'descuento_promocional' => $descuento_promocional_producto,
                    'ciudad' => [
                        'id' => $detalleCarrito->getIdProducto()->getDirecciones() ? $detalleCarrito->getIdProducto()->getDirecciones()->getCiudad()->getId() : '',
                        'nombre' => $detalleCarrito->getIdProducto()->getDirecciones() ? $detalleCarrito->getIdProducto()->getDirecciones()->getCiudad()->getCiudad() : '',
                    ],
                    'direccion' => $full_direccion,
                    'precio' =>  $subtotal,
                    'iva' =>  $impuestos,
                    'precio_mas_iva' => $total,
                    'dimensiones' => [
                        'alto' => $detalleCarrito->getIdProducto()->getAlto(),
                        'ancho' => $detalleCarrito->getIdProducto()->getAncho(),
                        'largo' => $detalleCarrito->getIdProducto()->getLargo(),
                        'peso' => $detalleCarrito->getIdProducto()->getPeso() * $detalleCarrito->getCantidad()
                    ],
                    'tipo_entrega' => $detalleCarrito->getIdProducto()->getEntrgasTipo()->getTipo(),
                    'tipo_producto' => $detalleCarrito->getIdProducto()->getProductosTipo() ? $detalleCarrito->getIdProducto()->getProductosTipo()->getTipo() : '',
                    'cantidad' => $detalleCarrito->getCantidad(),
                    'imagenes' => $imagenesArray,
                    'variable' => $detalleCarrito->getIdProducto()->isVariable(),
                    'vendedor' => [
                        'avatar' => $avatarUrl,
                        'username' => $detalleCarrito->getIdProducto()->getTienda()->getLogin()->getUsername(),
                        'nombre' => $detalleCarrito->getIdProducto()->getTienda()->getLogin()->getUsuarios()->getNombre(),
                        'apellido' => $detalleCarrito->getIdProducto()->getTienda()->getLogin()->getUsuarios()->getApellido(),
                    ],
                ];

                $s_tienda = $subtotal;
                $i_tienda = $impuestos;
                $s_impuestos_tienda = $s_tienda + $i_tienda;
                $subtotal_original_tienda = $subtotal;

                $idTienda = $detalleCarrito->getIdProducto()->getTienda()->getId();

                // Buscar si la tienda ya existe en el arreglo
                $tiendaKey = array_search($idTienda, array_column($productosPorVendedor, 'id_tienda'));
                if ($tiendaKey === false) {
                    // Si no existe, agregar una nueva entrada para la tienda
                    $productosPorVendedor[] = [
                        'id_tienda' => $idTienda,
                        'nombre_tienda' => $detalleCarrito->getIdProducto()->getTienda() ? $detalleCarrito->getIdProducto()->getTienda()->getSlug() : '',
                        'subtotal' => 0,
                        'iva' => 0,
                        'subtotal_mas_iva' => 0,
                        'subtotal_envio' => 0,
                        'iva_envio' => 0,
                        'costo_envio_tienda' => 0,
                        'comision_paypal' => 0,
                        'total' => 0,
                        'descuento_cupon' => 0, // <-- CORREGIDO: de 'descunto' a 'descuento'
                        'subtotal_original' => 0

                    ];
                    $tiendaKey = count($productosPorVendedor) - 1;
                }

                // Calcular valores unitarios
               

                if ($envio_gratis) {
                    // Si se cumplió al menos UNA de las condiciones de arriba, el costo es CERO.
                    $costo_envio_pedido = 0;
                } else {

                    if ($id_envio == '' || $id_envio == 1) {

                        $data = $this->servientregaService->calculo_envio_tienda($carrito, $iva, $idTienda, $seguro_envio, $region_usuario, $provincia_usuario, $ciudad_usuario);
                        $cn_pedido = $data['subtotal_envio'];
                        $iva_envio_pedido = $data['iva_envio'];
                        $costo_envio_pedido = $data['costo_envio'];


                    } elseif ($id_envio == 3) {

                        $ciudad_usuario = preg_replace('/\s*\(.*?\)\s*/', '', $ciudad_usuario);

                        $datos2 = $this->em->getRepository(DetalleCarrito::class)->carrito_delivereo_tienda($carrito, $idTienda);
                        $costo_envio_pedido = 0;
                        $data = [];
                        foreach ($datos2 as $dato2) {
                            try {
                                $calculo_envio = $this->calculate_booking(
                                    $direcion_principal_usuario,
                                    $direcion_secundaria_usuario,
                                    $ciudad_usuario,
                                    $dato2->getIdProducto()->getDirecciones() ? $dato2->getIdProducto()->getDirecciones()->getLatitud() : null,
                                    $dato2->getIdProducto()->getDirecciones() ? $dato2->getIdProducto()->getDirecciones()->getLongitud() : null,
                                    $latitud_usuario,
                                    $longitud_usuario
                                );

                                if ($calculo_envio->code !== 200) {

                                    if ($calculo_envio->code == 400) {
                                        return $this->errorsInterface->error_message(
                                            'Error en el cálculo de costo de envío.',
                                            $calculo_envio->code,
                                            'description',
                                            'La ciudad seleccionada no está disponible para envíos'
                                        );
                                    }

                                    return $this->errorsInterface->error_message('Error en el cálculo de costo de envío.', $calculo_envio->code, 'description', $calculo_envio->message);
                                } elseif ($calculo_envio->code == 200) {
                                    $cn_pedido = $calculo_envio->farePrice;
                                    $iva_envio_pedido = $calculo_envio->iva;
                                    $c_envio = $calculo_envio->totalAmount;
                                } else {
                                    $c_envio = 0;
                                }

                                $data[] = [
                                    'costo_envio' => $c_envio
                                ];

                                $costo_envio_pedido = $c_envio;

                            } catch (Exception $e) {
                                return $this->errorsInterface->error_message('Error interno del servidor al calcular envío.', 500, 'description', $e->getMessage());
                            }

                        }

                    }

                }

                if ($s_tienda === 0) {
                    $costo_envio_pedido = 0;
                    $i_tienda = 0;
                    $s_impuestos_tienda = 0;
                    $t_mas_envio = 0;
                    $calculo_descuento_tienda = 0;
                } else {
                    // Ya no recalculamos i_tienda. Usamos el valor preciso de $impuestos.
                    $s_impuestos_tienda = $s_tienda + $i_tienda;
                    $t_mas_envio = $s_impuestos_tienda + $costo_envio_pedido;
                }

                // 6) Comisión PayPal si aplica
                $c_paypal = ($ipy > 0) ? ($t_mas_envio * $ipy) / 100 : 0;

                // 8) Acumular valores en la tienda correspondiente
                $productosPorVendedor[$tiendaKey]['subtotal'] = 
                    ($productosPorVendedor[$tiendaKey]['subtotal'] ?? 0) + $s_tienda;

                $productosPorVendedor[$tiendaKey]['iva'] = 
                    ($productosPorVendedor[$tiendaKey]['iva'] ?? 0) + $i_tienda;

                $productosPorVendedor[$tiendaKey]['subtotal_mas_iva'] = 
                    $productosPorVendedor[$tiendaKey]['subtotal'] + $productosPorVendedor[$tiendaKey]['iva'];

                // 🚚 el envío no se acumula, se asigna por tienda
                
                $productosPorVendedor[$tiendaKey]['subtotal_envio'] = $cn_pedido;
                $productosPorVendedor[$tiendaKey]['iva_envio'] = $iva_envio_pedido;
                $productosPorVendedor[$tiendaKey]['costo_envio_tienda'] = $costo_envio_pedido;

                $productosPorVendedor[$tiendaKey]['comision_paypal'] = 
                    ($productosPorVendedor[$tiendaKey]['comision_paypal'] ?? 0) + $c_paypal;

                $productosPorVendedor[$tiendaKey]['subtotal_original'] = 
                    ($productosPorVendedor[$tiendaKey]['subtotal_original'] ?? 0) + $subtotal_original_tienda;

                // 🧮 total bien calculado y redondeado
                $productosPorVendedor[$tiendaKey]['total'] = 
                    $productosPorVendedor[$tiendaKey]['subtotal_mas_iva'] +
                    $productosPorVendedor[$tiendaKey]['costo_envio_tienda'] +
                 $productosPorVendedor[$tiendaKey]['comision_paypal'];

                // Agregar los detalles del producto
                $productosPorVendedor[$tiendaKey]['productos'][] = [

                    'id_producto' => $detalleCarrito->getIdProducto() ? $detalleCarrito->getIdProducto()->getId() : '',
                    'id_variacion' => $detalleCarrito->getIdVariacion() ? $detalleCarrito->getIdVariacion()->getId() : '',
                    'nombre_producto' =>$nombreCompletoProducto ,
                    'subtotal_unitario' => $subtotal_unitario,
                    'iva_unitario' => $iva_unitario,
                    'total_unitario' => $total_unitario,
                    'subtotal_original' => $subtotal_original,
                    'subtotal' => $subtotal,
                    'descuento' => $calculo_descuento,
                    'iva' => $impuestos,
                    'peso' => $peso_producto * $detalleCarrito->getCantidad(),
                    'total' => $total,
                    'cantidad' => $detalleCarrito->getCantidad(),
                    'id_direccion' => $id_direccion_producto,
                    'ciudad' => $ciudad_producto,
                    'provincia' => $provincia_producto,
                    'region' => $region_producto,
                    'direccion' => $direccion_producto,
                    'referencia' => $referencia_producto ? $referencia_producto : null,
                    'usario_producto' => $usuario_producto,
                    'celular_producto' => $celular_producto,
                    'tienda' => $idTienda,
                    'tipo_entrega' => $detalleCarrito->getIdProducto()->getEntrgasTipo()->getTipo(),
                    'tipo_producto' => $detalleCarrito->getIdProducto()->getProductosTipo() ? $detalleCarrito->getIdProducto()->getProductosTipo()->getId() : '',
                    'imagenes' => $imagenesArray,
                    'terminos' => $terminsoArray,
                    'latitud' => $detalleCarrito->getIdProducto()->getDirecciones() ? $detalleCarrito->getIdProducto()->getDirecciones()->getLatitud() : null,
                    'longitud' => $detalleCarrito->getIdProducto()->getDirecciones() ? $detalleCarrito->getIdProducto()->getDirecciones()->getLongitud() : null,
                    'codigo_producto'=>$codigo_producto
                ];
            }

        }/*fin de bucle carrito.*/

        if (!empty($errors)) {
            return new JsonResponse([
                'message' => 'Control de Stock',
                'errors' => array_map(function ($error) {
                    return ['description' => $error]; // Cada error como objeto separado
                }, $errors)
            ], 400);
        }

        // 3. APLICAR EL CUPÓN DE DESCUENTO A LOS SUBTOTALES AGRUPADOS POR TIENDA
        // Este bucle se ejecuta DESPUÉS de haber procesado todos los productos.
        if ($descuento !== null && !empty($codigo_cupon) && $activo == true) {

            // --- PASO 1: Calcular el subtotal global ANTES de aplicar descuentos ---
            $subtotal_global_para_descuento = 0;
            foreach ($productosPorVendedor as $tienda) {
                $subtotal_global_para_descuento += $tienda['subtotal_original'];
            }

            // --- PASO 2: Recorrer las tiendas de nuevo para distribuir el descuento ---
            // Asegurarse de no dividir por cero si el carrito está vacío
            if ($subtotal_global_para_descuento > 0) {
                foreach ($productosPorVendedor as $tiendaKey => &$tienda) {
                    $subtotal_original_tienda = $tienda['subtotal_original'];
                    $calculo_descuento_tienda = 0;

                    if ($tipo_descuento === 'VALOR' && $descuento > $subtotal_global_para_descuento) {
                        return $this->errorsInterface->error_message(
                            'Cupón no aplicable.',
                            400, // Código de error HTTP para una mala solicitud
                            'description',
                            'El monto del descuento es mayor al subtotal de tu compra.'
                        );
                    }

                    if ($tipo_descuento === 'PORCENTAJE') {
                        // El descuento por porcentaje se aplica directamente a cada tienda
                        $calculo_descuento_tienda = ($subtotal_original_tienda * $descuento) / 100;

                    } elseif ($tipo_descuento === 'VALOR') {
                        // Prorratear el descuento de VALOR usando el total global ya calculado
                        $proporcion = $subtotal_original_tienda / $subtotal_global_para_descuento;
                        $calculo_descuento_tienda = $descuento * $proporcion;
                    }

                    // El resto de tu lógica es correcto
                    $calculo_descuento_tienda = min($subtotal_original_tienda, $calculo_descuento_tienda);
                    $tienda['descuento_cupon'] = $calculo_descuento_tienda;

                    $nuevo_subtotal = $subtotal_original_tienda - $calculo_descuento_tienda;
                    $nuevo_iva = ($nuevo_subtotal * $iva) / 100;

                    $tienda['subtotal'] = $nuevo_subtotal;
                    $tienda['iva'] = $nuevo_iva;
                    $tienda['subtotal_mas_iva'] = $tienda['subtotal'] + $tienda['iva'];

                    $tienda['total'] =
                        $tienda['subtotal_mas_iva'] +
                        $tienda['costo_envio_tienda'] +
                        $tienda['comision_paypal'];
                }
                unset($tienda);
            }
        }

        $totalGeneral = array_fill_keys(array_keys($totalGeneral), 0);

        foreach ($productosPorVendedor as $tienda) {
            // Sumar subtotales e IVAs de los productos ajustados
            if (isset($tienda['productos']) && is_array($tienda['productos'])) {
                foreach ($tienda['productos'] as $producto) {
                    $totalGeneral['subtotal'] += $producto['subtotal'];
                    $totalGeneral['iva'] += $producto['iva'];
                    $totalGeneral['descuento_cupon'] += $producto['descuento'];
                }
            }
            // ... resto del código
        }

        // Por esta versión que usa los totales ya calculados por tienda:
        $totalGeneral = [
            'subtotal' => 0,
            'iva' => 0,
            'costo_envio_tienda' => 0,
            'comision_paypal' => 0,
            'descuento_cupon' => 0,
            'subtotal_original' => 0
        ];

        foreach ($productosPorVendedor as $tienda) {
            $totalGeneral['subtotal'] += $tienda['subtotal'];
            $totalGeneral['iva'] += $tienda['iva'];
            $totalGeneral['costo_envio_tienda'] += $tienda['costo_envio_tienda'];
            $totalGeneral['comision_paypal'] += $tienda['comision_paypal'];
            $totalGeneral['descuento_cupon'] += $tienda['descuento_cupon'];
            $totalGeneral['subtotal_original'] += $tienda['subtotal_original'];
        }

        $totalGeneral['subtotal_mas_iva'] = 
            $totalGeneral['subtotal'] + 
            $totalGeneral['iva'];

        $totalGeneral['total'] = 
            $totalGeneral['subtotal_mas_iva'] +
            $totalGeneral['costo_envio_tienda'] +
            $totalGeneral['comision_paypal'];

        return new JsonResponse([
            'subtotal' => $totalGeneral['subtotal'], 
            'iva' => $totalGeneral['iva'], 
            'subtotal_mas_iva' => $totalGeneral['subtotal_mas_iva'],
            'costo_envio' => $totalGeneral['costo_envio_tienda'], 
            'calculo_paypal' => $totalGeneral['comision_paypal'], 
            'total' => $totalGeneral['total'], 
            'descuento_cupon' => $totalGeneral['descuento_cupon'], 
            'iva_aplicado' => $iva, 
            'productos_sin_agrupar' => $detalleCarritoArray, 
            'productos_por_vendedor' => $productosPorVendedor, 
            'subtotal_original' => $totalGeneral['subtotal_original']
        ]);

    }




    public function retiros_aprovados(Login $user,Regateos $regateo,$metodo_pogo=null,$direccionId=null,$metodo_envio=null):JsonResponse{

        $m_envio= $this->em->getRepository(MetodosEnvio::class)->findOneBy(['id'=>$metodo_envio,'activo'=>true]);
    
        if($m_envio){
            $id_envio= $m_envio->getId();
        }else{
            $id_envio='';
        }
       
        $m_pago= $this->em->getRepository(MetodosPago::class)->findOneBy(['id'=>$metodo_pogo]); 
        
        if($m_pago &&  $m_pago->getId() == 3){
            $costo_paypal= $this->em->getRepository(Impuestos::class)->findOneBy(['id'=>3]);
            $ipy= $costo_paypal->getIva();
        }else{
            $ipy=0;
            $calculo_paypal=0;
        }
        
        $domain = $this->request->getSchemeAndHttpHost(); 
        $host = $this->router->getContext()->getBaseUrl();
        $peso_producto=$regateo->getProducto()? $regateo->getProducto() ->getPeso():0;
        $impusto= $this->em->getRepository(Impuestos::class)->findOneBy(['id'=>1]);
        $iva= $impusto->getIva();

        $impusto2= $this->em->getRepository(Impuestos::class)->findOneBy(['id'=>2]);
        $seguro_envio= $impusto2->getIva();


        $usuario= $this->em->getRepository(Usuarios::class)->findOneBy(['login'=>$user]); 

        $direcciones = $direccionId ?
        $this->em->getRepository(UsuariosDirecciones::class)->findOneBy(['id' => $direccionId, 'usuario' => $usuario]) :
        $this->em->getRepository(UsuariosDirecciones::class)->findOneBy(['usuario' => $usuario], ['fecha_creacion' => 'DESC']);
    
        
        $ciudad_usuario = null;
        $provincia_usuario = null;
        $region_usuario = null;
        $latitud_usuario = null;
        $longitud_usuario = null;
        $direcion_principal_usuario = '';
        $direcion_secundaria_usuario = '';

        if ($direcciones !== null) {
            $ciudad = $direcciones->getCiudad();
            if ($ciudad !== null) {
                $ciudad_usuario = $ciudad->getCiudad();
                $provincia = $ciudad->getProvincia();
                $provincia_usuario = $provincia ? $provincia->getProvincia() : null;
                $region_usuario = $provincia && $provincia->getRegion() ? $provincia->getRegion() : null;
            } else {
                $ciudad_usuario = null;
                $provincia_usuario = null;
                $region_usuario = null;
            }
            $latitud_usuario = $direcciones->getLatitud() ? $direcciones->getLatitud() : null;
            $longitud_usuario = $direcciones->getLongitud() ? $direcciones->getLongitud() : null;
            $direcion_principal_usuario = $direcciones->getDireccionP() ? $direcciones->getDireccionP() : '';
            $direcion_secundaria_usuario = $direcciones->getDireccionS() ? $direcciones->getDireccionS() : '';
        }
    

        $v_regateo= $regateo->getRegateo();
            
        $nombre_producto= $regateo->getProducto()->getNombreProducto();

        if ($regateo->getProducto()->getDirecciones() !== null) {
            // Obtén la ciudad, provincia y región del producto
            $id_direccion_producto=$regateo->getProducto()->getDirecciones()->getCiudad()->getIdServientrega();
            $ciudad_producto = $regateo->getProducto()->getDirecciones()->getCiudad() ? $regateo->getProducto()->getDirecciones()->getCiudad()->getCiudad() : null;
            $provincia_producto = $regateo->getProducto()->getDirecciones()->getCiudad() ? $regateo->getProducto()->getDirecciones()->getCiudad()->getProvincia()->getProvincia() : null;
            $region_producto = $regateo->getProducto()->getDirecciones()->getCiudad() ? $regateo->getProducto()->getDirecciones()->getCiudad()->getProvincia()->getRegion() : null;
            $direccion_producto=$regateo->getProducto()->getDirecciones()->getDireccionP().'-'.$regateo->getProducto()->getDirecciones()->getDireccionS();
            $tipo_envio = $regateo->getProducto()->getEntrgasTipo()->getId();
            $referencia_producto= $regateo->getProducto()->getDirecciones() ? $regateo->getProducto()->getDirecciones()->getReferenciaDireccion():'';
            $usuario_producto=$regateo->getProducto()->getTienda()->getLogin()->getUsuarios()->getNombre().' '.$regateo->getProducto()->getTienda()->getLogin()->getUsuarios()->getApellido();
            $celular_producto= $regateo->getProducto()->getTienda()->getLogin()->getUsuarios()->getCelular();
        } else {
            $id_direccion_producto=null;
            // Manejo de caso donde $regateo->getIdProducto()->getDirecciones() es null
            $ciudad_producto = null;
            $provincia_producto = null;
            $region_producto = null;
            $direccion_producto=null;
            $tipo_envio = null;
            $referencia_producto=null;
            $usuario_producto=null;
            $celular_producto=null;
        }

        $idTienda = $regateo->getProducto()->getTienda()->getId();
        $s= $regateo->getVariacion() ? $regateo->getVariacion()->getId() : null;

        $terminsoArray=[];
        $imagenesArray=[];
        $cn_pedido=0;
        $iva_envio_pedido=0;
        $costo_envio_final=0;

        if($s != null){
          
            $nombre_producto= $regateo->getProducto()->getNombreProducto();
            $precio= $regateo->getVariacion()->getPrecio();
            $precio_rebajado= $regateo->getVariacion()->getPrecioRebajado();
         
           
            $variacion = $regateo->getVariacion();

           if ($variacion->getVariacionesGalerias()->isEmpty()) {

            foreach ($regateo->getProducto()->getProductosGalerias() as $galeria) {
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

            foreach($regateo->getVariacion()->getTerminos() as $termino){

                $terminsoArray[]=[
                  'nombre'=>$termino->getNombre()
                ];
            }
  
          }else{
      
            $nombre_producto= $regateo->getProducto()->getNombreProducto();
            $precio=$regateo->getProducto()->getPrecioNormalProducto();
            $precio_rebajado= $regateo->getProducto()->getPrecioRebajadoProducto();
            
            foreach($regateo->getProducto()->getProductosGalerias() as $galeria ){
              $imagenesArray[]=[
                 'id'=>$galeria->getId(),
                 'url'=> $domain.$host.'/public/productos/'.$galeria->getUrlProductoGaleria()            
              ];
            }
            
          }


          $precioAUsar = ($precio_rebajado !== null && $precio_rebajado !== 0) ? $precio_rebajado : $precio;
          $precio_unitario= $precioAUsar;
          $precio_original = $precioAUsar * 1;

          $iva_unitario= ($precio_original * $iva) / 100; // Calcular el IVA
          $subtotal_unitario = $precio_original; // Subtotal sin IVA
          $total_unitario= $precio_original + $iva_unitario;
 

          $ivaProducto = ($v_regateo * $iva) / 100; // Calcular el IVA
          $precio_final = $ivaProducto + $v_regateo; // Precio final con IVA
 
          $total = $precio_final; // Total incluye el IVA
          $subtotal = $v_regateo; // Subtotal sin IVA
        

         $detalle=[

               'id_producto' => $regateo->getProducto() ? $regateo->getProducto()->getId() : '',
               'id_variacion' => $regateo->getVariacion() ? $regateo->getVariacion()->getId() : '',
               'nombre_producto' => $nombre_producto,
               'subtotal_unitario' => $subtotal_unitario,
               'iva_unitario' => $iva_unitario,
               'total_unitario' => $total_unitario,
               'subtotal_original' => $subtotal_unitario,
               'subtotal' => $subtotal,
               'descuento' => 0,
               'iva' => $ivaProducto,
               'peso' => $peso_producto * 1,
               'total' => $total,
               'cantidad' => 1,
               'id_direccion' => $id_direccion_producto,
               'ciudad' => $ciudad_producto,
               'provincia' => $provincia_producto,
               'region' => $region_producto,
               'direccion' => $direccion_producto,
               'referencia' => $referencia_producto ? $referencia_producto : null,
               'usario_producto' => $usuario_producto,
               'celular_producto' => $celular_producto,
               'tienda' => $idTienda,
               'tipo_entrega' => $regateo->getProducto()->getEntrgasTipo()->getTipo(),
               'imagenes' => $imagenesArray,
               'terminos' => $terminsoArray,
               'latitud' => $regateo->getProducto()->getDirecciones() ? $regateo->getProducto()->getDirecciones()->getLatitud() : null,
               'longitud' => $regateo->getProducto()->getDirecciones() ? $regateo->getProducto()->getDirecciones()->getLongitud() : null,

         ];


         if($id_envio == 1){
    
            $tarifa_local= $this->em->getRepository(TarifasServientrega::class)->findOneBy(['id'=>1]);
            $tarifa_cantonal= $this->em->getRepository(TarifasServientrega::class)->findOneBy(['id'=>2]);
            $tarifa_provincial= $this->em->getRepository(TarifasServientrega::class)->findOneBy(['id'=>3]);
            $tarifa_regional= $this->em->getRepository(TarifasServientrega::class)->findOneBy(['id'=>4]);
            $tarifa_galapagos= $this->em->getRepository(TarifasServientrega::class)->findOneBy(['id'=>6]);
        
            $un_kilo_local=$tarifa_local->getTarifas();
            $un_kilo_cantonal=$tarifa_cantonal->getTarifas();
            $un_kilo_provincial=$tarifa_provincial->getTarifas();
            $un_kilo_regional= $tarifa_regional->getTarifas();
            $un_kilo_galapagos= $tarifa_galapagos->getTarifas();
            $dos_kilos_local=$tarifa_local->getDosKilos();
            $dos_kilos_cantonal=$tarifa_cantonal->getDosKilos();
            $dos_kilos_provincial=$tarifa_provincial->getDosKilos();
            $dos_kilos_regional= $tarifa_regional->getDosKilos();
            $dos_kilos_galapagos=$tarifa_galapagos->getDosKilos();
        
            $kilo_adicional_local=$tarifa_local->getKiloAdicional();
            $kilo_adicional_cantonal=$tarifa_cantonal->getKiloAdicional();
            $kilo_adicional_provincial=$tarifa_provincial->getKiloAdicional();
            $kilo_adicional_regional=$tarifa_regional->getKiloAdicional();
            $kilo_adicional_galapagos=$tarifa_galapagos->getKiloAdicional();
                
            }elseif($id_envio == ''){
        
                $tarifa_local= $this->em->getRepository(TarifasServientrega::class)->findOneBy(['id'=>1]);
                $tarifa_cantonal= $this->em->getRepository(TarifasServientrega::class)->findOneBy(['id'=>2]);
                $tarifa_provincial= $this->em->getRepository(TarifasServientrega::class)->findOneBy(['id'=>3]);
                $tarifa_regional= $this->em->getRepository(TarifasServientrega::class)->findOneBy(['id'=>4]);
                $tarifa_galapagos= $this->em->getRepository(TarifasServientrega::class)->findOneBy(['id'=>6]);
        
        
                $un_kilo_local=$tarifa_local->getTarifas();
                $un_kilo_cantonal=$tarifa_cantonal->getTarifas();
                $un_kilo_provincial=$tarifa_provincial->getTarifas();
                $un_kilo_regional= $tarifa_regional->getTarifas();
                $un_kilo_galapagos= $tarifa_galapagos->getTarifas();
                $dos_kilos_local=$tarifa_local->getDosKilos();
                $dos_kilos_cantonal=$tarifa_cantonal->getDosKilos();
                $dos_kilos_provincial=$tarifa_provincial->getDosKilos();
                $dos_kilos_regional= $tarifa_regional->getDosKilos();
                $dos_kilos_galapagos=$tarifa_galapagos->getDosKilos();
        
                $kilo_adicional_local=$tarifa_local->getKiloAdicional();
                $kilo_adicional_cantonal=$tarifa_cantonal->getKiloAdicional();
                $kilo_adicional_provincial=$tarifa_provincial->getKiloAdicional();
                $kilo_adicional_regional=$tarifa_regional->getKiloAdicional();
                $kilo_adicional_galapagos=$tarifa_galapagos->getKiloAdicional();
        
            }

            if ($id_envio == ''  || $id_envio ==1){
    
                $costo_envio=0;
                $costo_envio_total = 0;
                $totalPrecio_total = 0; // Inicializar el total de precio
                $totalPeso_total = 0;
            

                      if ($ciudad_usuario !== null && $provincia_usuario !== null && $region_usuario !== null ) {
                        switch (true) {
                            case $peso_producto >= 2 && $peso_producto < 3:
                                if ($ciudad_usuario == $ciudad_producto && $provincia_usuario == $provincia_producto) {
                                    $costo_envio = $dos_kilos_local;
                                } elseif ($provincia_usuario == $provincia_producto && $ciudad_usuario !== $ciudad_producto) {
                                    $costo_envio = $dos_kilos_cantonal;
                                } elseif ($region_usuario == $region_producto   && $provincia_usuario !== $provincia_producto) {
                                    $costo_envio = $dos_kilos_provincial;
                                }elseif($region_usuario !== $region_producto &&  $region_usuario !== 'INSULAR' && $region_producto !== 'INSULAR' && $provincia_usuario !== $provincia){
                                        $costo_envio = $dos_kilos_regional;
                                }else{
                                    
                                    $costo_envio = $dos_kilos_galapagos;
                                }
                                break;
                    
                            case $peso_producto >= 3:
                                $kilos_adicionales = round($peso_producto) - 2;
                                if ($ciudad_usuario == $ciudad_producto && $provincia_usuario == $provincia_producto) {
                                    $costo_envio = $dos_kilos_local + ($kilos_adicionales * $kilo_adicional_local);
                                } elseif ($provincia_usuario == $provincia_producto && $ciudad_usuario !== $ciudad_producto) {
                                    $costo_envio = $dos_kilos_cantonal + ($kilos_adicionales * $kilo_adicional_cantonal);
                                } elseif ($region_usuario == $region_producto && $provincia_usuario !== $provincia_producto) {
                                    $costo_envio = $dos_kilos_provincial + ($kilos_adicionales * $kilo_adicional_provincial);
                               }elseif($region_usuario !== $region_producto &&  $region_usuario !== 'INSULAR' && $region_producto !== 'INSULAR' && $provincia_usuario !== $provincia){
                                    $costo_envio = $dos_kilos_regional + ($kilos_adicionales * $kilo_adicional_regional);
                            }
                                else{
                                    $costo_envio = $dos_kilos_galapagos + ($kilos_adicionales * $kilo_adicional_galapagos);
                                }
                                break;
                    
                            case $peso_producto > 0 &&  $peso_producto < 2:
                                if ($ciudad_usuario == $ciudad_producto && $provincia_usuario == $provincia_producto) {
                                    $costo_envio = $un_kilo_local;
                                } elseif ($provincia_usuario == $provincia_producto && $ciudad_usuario !== $ciudad_producto) {
                                    $costo_envio = $un_kilo_cantonal;
                                } elseif ($region_usuario == $region_producto && $provincia_usuario !== $provincia_producto) {
                                    $costo_envio = $un_kilo_provincial;
                                } elseif($region_usuario !== $region_producto &&  $region_usuario !== 'INSULAR' && $region_producto !== 'INSULAR' && $provincia_usuario !== $provincia) {
                                    $costo_envio = $un_kilo_regional;
                                }else{
                                    $costo_envio = $un_kilo_galapagos;
                                }
                                break;
                    
                            default:
                                $costo_envio = null;
                        }
                    } else{
                        $costo_envio=null;
                    }
        
        
                    $va_seguro= $subtotal * $seguro_envio;
 
                    $cn_pedido=  $costo_envio + $va_seguro;
                    $iva_envio_pedido= ($cn_pedido * $iva)/100;
                    $costo_envio_final= $cn_pedido + $iva_envio_pedido; 
        
            }elseif($id_envio == 3){
        
                $city_user = preg_replace('/\s*\(.*?\)\s*/', '', $ciudad_usuario);
            
                $costo_envio_final=0;
                
                    
                         $calculo_envio= $this->calculate_booking($direcion_principal_usuario,$direcion_secundaria_usuario,$city_user, $regateo->getProducto()->getDirecciones() ? $regateo->getProducto()->getDirecciones()->getLatitud():null,$regateo->getProducto()->getDirecciones() ? $regateo->getProducto()->getDirecciones()->getLongitud():null,$latitud_usuario,$longitud_usuario);
                
                    if($calculo_envio->code !== 200){
                        if ($calculo_envio->code == 400) {
                            return $this->errorsInterface->error_message(
                                'Error en el cálculo de costo de envío.',
                                $calculo_envio->code,
                                'description',
                                'La ciudad seleccionada no está disponible para envíos'
                            );
                        }
                        
                        return $this->errorsInterface->error_message('Error en el cálculo de costo de envío.',$calculo_envio->code,'description',$calculo_envio->message);
                    
                    }elseif($calculo_envio->code == 200){
                        $cn_pedido= $calculo_envio->farePrice;
                        $iva_envio_pedido= $calculo_envio->iva;
                        $c_envio= $calculo_envio->totalAmount;
                    }else{
                        $c_envio=0;
                    }
            
                    $data[]=[
                     'costo_envio'=>$c_envio
                    ];  

                    $costo_envio_pedido =$c_envio;        
            }

  
        $t2= $subtotal +$ivaProducto + $costo_envio_final;
    
        if($ipy !== 0){
            $t4= ($t2 * $ipy)/100;
            $calculo_paypal=$t4;
            $t4= $t2 + $t4;
        }else{
            $t4=$t2;
        }


         return new JsonResponse(['subtotal_original'=>$subtotal_unitario,'subtotal'=>$subtotal,'iva' =>$ivaProducto,'subtotal_mas_iva'=> $subtotal+$ivaProducto,'subtotal_envio'=>$cn_pedido,'iva_envio'=>$iva_envio_pedido,'costo_envio' => $costo_envio_final,'calculo_paypal' => $calculo_paypal,'total' => $t4,'iva_aplicado'=>$iva,'detalle'=>$detalle]);
     }

    private function calculate_booking($direccion_p=null,$direccion_s=null,$ciudad=null,$latitud=null,$longitud=null,$latitud_user=null,$longitud_user=null)
    {
         $ciudad_usuario = preg_replace('/\s*\(.*?\)\s*/', '', $ciudad);


        $url = $this->delivereoService->url_delivereo() . '/api/private/business-bookings/calculate';

        $data = [
            "addresses" => [
                [
                    "addressCrossingStreet" => $direccion_p,//usuario
                    "addressMainStreet" => $direccion_s,//usuario
                    "addressOrder" => 1,
                    "countryCode" => "EC",
                    "fullAddress" => $direccion_p.','.$direccion_s//usuario
                ]
            ],
            "categoryType" => "MEDIUM",
            "cityType" => $ciudad_usuario,//usuario.
            "lang" => "es",
            "points" => [
                [
                    "pointLatitude" => $latitud,//producto
                    "pointLongitude" => $longitud,//producto
                    "pointOrder" => 1
                ],
                [
                    "pointLatitude" => $latitud_user,
                    "pointLongitude" => $longitud_user,
                    "pointOrder" => 2
                ]
                
            ]
        ];

        try {
            $token = $this->delivereoService->getJwtToken();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

      $curl = curl_init();

      curl_setopt_array($curl, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_SSL_VERIFYPEER =>true,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode(array_merge($data)),
      CURLOPT_HTTPHEADER => [
         "Accept: application/json",
         "Authorization: Bearer ".$token,
         "Content-Type: application/json"
       ],
     ]);

    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $err = curl_error($curl);

    
    curl_close($curl);
    
      if ($err) {

        throw new Exception('cURL Error: '. $err);
      } else {
        $mp= json_decode($response);
      }

    
    return $mp;
  }



 
}
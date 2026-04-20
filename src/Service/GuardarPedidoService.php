<?php   

namespace App\Service;

use App\Entity\Pedidos;
use App\Entity\DetallePedido;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;

class GuardarPedidoService
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function guardarPedido(
        $user, $n_venta, $factura, $numero_pedido, $tienda, $m_envio, $ingresado, $metodo_pago, $cupon,
        $direccion_principal = null, $direccion_secundaria = null, $referencia_direccion = null, $customer = null,
        $dni_customer = null, $celular_customer = null, $codigo_postal_customer = null, $customer_city = null,
        $id_direccion = null, $provincia = null, $region = null, $latitud_usuario = null, $longitud_usuario = null,
        $reques_id = null, $iva_aplicado = null, $subtotal = null, $impuestos = null, $suma_mas_iva = null,
        $subtotal_envio = null, $iva_envio = null, $costoEnvio = null, $comisionPaypal = null, $totalFinal = null,
        $descuentoCupon = null, $subtotal_original = null,$monto_saldo=null,$monto_pasarela=null
    ) {

        
        $pedido = new Pedidos();
        $pedido->setLogin($user);
        $pedido->setNVenta($n_venta);
        $pedido->setFactura($factura);
        if($numero_pedido == null){
            $conn = $this->em->getConnection();
            $conn->executeQuery('LOCK TABLES pedidos WRITE');
             // Obtener último ID
            $lastId = $conn->fetchOne('SELECT MAX(IDPEDIDO) FROM pedidos');
            $nextId = $lastId + 1;
            $numeroPedido = sprintf('PED-%04d', $nextId).'-'.rand(0000, 9999);
            $conn->executeQuery('UNLOCK TABLES'); // ← OBLIGATORIO
            $pedido->setNumeroPedido($numeroPedido);
        }
        $pedido->setDireccionPrincipal($direccion_principal);
        $pedido->setDireccionSecundaria($direccion_secundaria);
        $pedido->setUbicacionReferencia(ubicacion_referencia: $referencia_direccion);
        $pedido->setCustomer($customer);
        $pedido->setDniCustomer($dni_customer);
        $pedido->setCelularCustomer($celular_customer);
        $pedido->setCodigoPostalCustomer($codigo_postal_customer);
        $pedido->setCustomerCity($customer_city);
        $pedido->setIdDireccion((int) $id_direccion);
        $pedido->setEstadoEnvio($ingresado);
        $pedido->setEstadoRetiro($ingresado);
        $pedido->setMetodoPago($metodo_pago);

        if ($m_envio !== null) {
            $pedido->setMetodoEnvio($m_envio);
        }

        if ($cupon !== null) {
            $pedido->setCupon($cupon);
        }

        if ($latitud_usuario !== null) {
            $pedido->setLatitud($latitud_usuario);
        }

        if ($longitud_usuario !== null) {
            $pedido->setLongitud($longitud_usuario);
        }

        if ($reques_id !== null) {
            $pedido->setReferenciaPedido($reques_id);
        }

        $pedido->setEstado('PENDING');
        $pedido->setProvincia($provincia);
        $pedido->setRegion($region);
        $pedido->setTienda($tienda);
        $pedido->setIvaAplicado($iva_aplicado);
        $pedido->setSubtotal($subtotal);
        $pedido->setIva($impuestos);
        $pedido->setTotal($suma_mas_iva);
        $pedido->setSubtotalEnvio($subtotal_envio);
        $pedido->setIvaEnvio($iva_envio);
        $pedido->setCostoEnvio($costoEnvio);
        $pedido->setComisionPaypal($comisionPaypal);
        $pedido->setTotalFinal($totalFinal);
        $pedido->setMontoSaldo($monto_saldo);
        $pedido->setMontoPasarela($monto_pasarela);
        $pedido->setPagoMixto($monto_saldo > 0 && $monto_pasarela > 0);
        $pedido->setDescuentoCupon($descuentoCupon);
        $pedido->setSubtotalOriginal($subtotal_original);

        $this->em->persist($pedido);

        return $pedido;
    }

    public function guardarDetallePedido(
        Pedidos $pedido, $id_producto, $id_variacion = null, $nombre_producto = null, $cantidad = null, $tienda = null,
        $precioAUsar = null, $ivaProducto = null, $precio_final = null, $ciudad_remite = null, $direccion_remite = null,
        $id_direccion_producto = null, $provincia = null, $region = null, $peso = null, $latitud = null, $longitud = null,
        $celular = null, $referencia = null, $nombre = null, $precio_unitario = null, $subtotal_unitario = null,
        $iva_unitario = null,$codigo_producto =null
    ): void {
        $detalle_pedido = new DetallePedido();
        $detalle_pedido->setCantidad($cantidad);
        $detalle_pedido->setSubtotal($precioAUsar);
        $detalle_pedido->setImpuesto($ivaProducto);
        $detalle_pedido->setTotal($precio_final);
        $detalle_pedido->setNombreProducto($nombre_producto);
        $detalle_pedido->setIdProductos($id_producto);

        if ($id_variacion !== null) {
            $detalle_pedido->setIdVariacion($id_variacion);
        }

        if ($latitud !== null) {
            $detalle_pedido->setLatitud($latitud);
        }

        if ($longitud !== null) {
            $detalle_pedido->setLongitud($longitud);
        }

        if ($referencia !== null) {
            $detalle_pedido->setReferencia($referencia);
        }

        $detalle_pedido->setCelular($celular);
        $detalle_pedido->setNombre($nombre);
        $detalle_pedido->setPedido($pedido);
        $detalle_pedido->setTienda($tienda);
        $detalle_pedido->setDireccionRemite($direccion_remite);
        $detalle_pedido->setCiudadRemite($ciudad_remite);
        $detalle_pedido->setIdDireccion($id_direccion_producto);
        $detalle_pedido->setProvincia($provincia);
        $detalle_pedido->setRegion($region);
        $detalle_pedido->setPeso($peso);
        $detalle_pedido->setPrecioUnitario($precio_unitario);
        $detalle_pedido->setSubtotalUnitario($subtotal_unitario);
        $detalle_pedido->setIvaUnitario($iva_unitario);
        $detalle_pedido->setCodigoProducto($codigo_producto);

        $this->em->persist($detalle_pedido);
    }
}
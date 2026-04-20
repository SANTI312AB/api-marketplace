<?php

namespace App\Service;

use App\Entity\Cupon;
use App\Entity\Login;
use App\Entity\Pedidos;
use App\Entity\Productos;
use App\Entity\Recargas;
use App\Entity\Saldo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Service\DynamicMailerFactory;

class EmailPedidoService
{
    private $em;

    private $mailer;
    
    private $parameters;
    
    public function __construct(EntityManagerInterface $em,DynamicMailerFactory $mailer,ParameterBagInterface $parameters)
    {
        $this->em = $em;
        $this->mailer = $mailer;
        $this->parameters = $parameters;
    }


    public function sendemail_pedido(Pedidos $pedido,string $estadoPago)
    {
        $productosPorVendedor = [];
        $data=[];
        foreach($pedido->getDetallePedidos() as $detalle){

             $s= $detalle->getIdVariacion() ? $detalle->getIdVariacion()->getId() : null;

             $imagenesArray=[];
             $terminsoArray=[];


             if($s != null){
         
               $variacion = $detalle->getIdVariacion();
 
             if ($variacion->getVariacionesGalerias()->isEmpty()) {
 
             foreach ($detalle->getIdProductos()->getProductosGalerias() as $galeria) {
                 $imagenesArray[] = [

                     'url' =>$galeria->getUrlProductoGaleria(),
                 ];
             }
 
              } else {
        
             foreach ($variacion->getVariacionesGalerias() as $galeria) {
                 $imagenesArray[] = [
                     'url' =>$galeria->getUrlVariacion(),
                 ];
             }
             
            }
 
               foreach($detalle->getIdVariacion()->getTerminos() as $termino){
 
                   $terminsoArray[]=[
                     'nombre'=>$termino->getNombre()
                   ];
               }
     
             }else{
         
               foreach($detalle->getIdProductos()->getProductosGalerias() as $galeria ){
                 $imagenesArray[]=[
                    'url'=>$galeria->getUrlProductoGaleria()            
                 ];
               }
               
             }
         
         $data[]=[
           'nombre_producto'=>$detalle->getNombreProducto(),
           'cantidad'=>$detalle->getCantidad(),
           'subtotal'=>$detalle->getSubtotal(),
           'iva'=>$detalle->getImpuesto(),
           'direccion'=>$detalle->getDireccionRemite(),
           'ciudad'=>$detalle->getCiudadRemite(),
           'tipo_entrega'=>$detalle->getIdProductos()->getEntrgasTipo()->getTipo(),
           'tipo_producto'=>$detalle->getIdProductos()->getProductosTipo() ? $detalle->getIdProductos()->getProductosTipo()->getTipo():'',
           'terminos'=>$terminsoArray,
           'imagenes'=>$imagenesArray,
           'fecha_servicio' =>$detalle->getFechaServicio() ? $detalle->getFechaServicio()->format('Y-m-d'):null,

         ];


         $idVendedor = $detalle->getIdProductos()->getTienda()->getId(); // Asumiendo que el ID del vendedor está en la tienda
           
         if (!isset($productosPorVendedor[$idVendedor])) {
            $productosPorVendedor[$idVendedor] = [
                'id_tienda' => $detalle->getIdProductos()->getTienda()->getId(),
                'nombre_tienda'=>$detalle->getIdProductos()->getTienda() ? $detalle->getIdProductos()->getTienda()->getDescripcion():'',
                'productos' => [],
            ];
         }
         $productosPorVendedor[$idVendedor]['productos'][] = [
           'nombre_producto'=>$detalle->getNombreProducto(),
           'cantidad'=>$detalle->getCantidad(),
           'subtotal'=>$detalle->getSubtotal(),
           'iva'=>$detalle->getImpuesto(),
           'direccion'=>$detalle->getDireccionRemite(),
           'ciudad'=>$detalle->getCiudadRemite(),
           'tipo_entrega'=>$detalle->getIdProductos()->getEntrgasTipo()->getTipo(),
           'tipo_producto'=>$detalle->getIdProductos()->getProductosTipo() ? $detalle->getIdProductos()->getProductosTipo()->getTipo():'',
           'terminos'=>$terminsoArray,
           'imagenes'=>$imagenesArray,
           'fecha_servicio' =>$detalle->getFechaServicio() ? $detalle->getFechaServicio()->format('Y-m-d'):null,
           
          ];

          switch ($estadoPago) {
           case 'APPROVED':
               $productoTipo = $detalle->getIdProductos()->getProductosTipo()->getId();
               $producto_n= $detalle->getIdProductos()->getSlugProducto();
               $producto_disponibilidad= $detalle->getIdProductos()->isDisponibilidadProducto();
               $id_user= $pedido->getLogin();
   
               // Verifica si el producto es de tipo 3 antes de llamar a gitcard_cupon.
               if ($productoTipo === 3 &&  str_starts_with($producto_n, 'GIFTCARD') && $producto_disponibilidad == true ) {
                 $this->gitcard_cupon($detalle->getIdProductos());
               }elseif($productoTipo === 3 && str_starts_with($producto_n, 'RECARGA') &&  $producto_disponibilidad  ==  true){
                   $this->giftcard_recarga($detalle->getIdProductos(),$id_user);
               }
               break;
   
           // Agrega otros casos si es necesario.
           default:
               // Lógica para otros estados de pago.
               break;
       }
      
       }

       


             $eml = (new TemplatedEmail())
             ->to($pedido->getTienda()->getLogin()->getEmail())
             ->subject('El estado del pedido'.' '. $pedido->getNumeroPedido() . ' ha sido ')
             ->htmlTemplate('pedidos/estado_pedido.html.twig') // Especifica la plantilla Twig para el cuerpo HTML
             ->context([
               'nombre'=>$pedido->getTienda()->getLogin()->getUsuarios()->getNombre().' '.$pedido->getTienda()->getLogin()->getUsuarios()->getApellido(),
               'n_pedido' => $pedido->getNumeroPedido(),
               'metodo_pago'=>$pedido->getMetodoPago()->getNombre(),
               'detalle'=>$productosPorVendedor[$idVendedor]['productos'], // Corrección aquí, 
               'direccion_cliente'=>$pedido->getDireccionPrincipal().' y '.$pedido->getDireccionSecundaria().' '.$pedido->getCustomerCity(),
               'nombre_cliente'=>$pedido->getCustomer(),
               'metodo_envio'=>  $pedido->getMetodoEnvio() ? $pedido->getMetodoEnvio()->getNombre():'',
               'subtotal' => $pedido->getSubtotal(),
               'impuestos' => $pedido->getIva(),
               'costo_envio' => $pedido->getCostoEnvio(), 
               'comision_paypal'=> $pedido->getComisionPaypal(), 
               'total' => $pedido->getTotalFinal(),
               'estado_pago'=>$pedido->getEstado()
            ]);
   
              $this->mailer->send($eml);


              return  $data;
    }


    private function gitcard_cupon($producto)
    {
        // Obtener el producto desde la base de datos
        $p = $this->em->getRepository(Productos::class)->find($producto);
        $nombre = $p->getSlugProducto();
        $precio = $p->getPrecioNormalProducto();
        $email= $p->getDescripcionCortaProducto();
        $n_usuario= $p->getDescripcionLargaProducto();
    
        // Verificar si ya existe un cupón con el mismo código
        $cuponExistente = $this->em->getRepository(Cupon::class)->findOneBy(['codigo_cupon' => $nombre]);
    
        if ($cuponExistente) {
            // Si ya existe, no crear otro y retornar
            return;
        }
  
        
         // Calcular el gasto mínimo dinámicamente
        $gastoMinimo = $precio > 0 ? $precio + 1 : 5; // Aseguramos que el gasto mínimo sea mayor que el precio o un valor mínimo por defecto
  
        // Crear el nuevo cupón
        $cupon = new Cupon();
        $cupon->setCodigoCupon($nombre);
        $cupon->setValorDescuento($precio);
        $cupon->setGastoMinimo($gastoMinimo);
        $cupon->setTipoDescuento('VALOR');
        $cupon->setTipo('GIFTCARD');
        $cupon->setLimiteUso(1);
        $p->setDisponibilidadProducto(disponibilidad_producto: false);
    
        // Persistir y guardar en la base de datos
        $this->em->persist($cupon);
        $this->em->flush();
  
        // Enviar correo electrónico al administrador
          $eml = (new TemplatedEmail())
              ->to($email)
              ->subject('Un amigo te regaló una gift card.')
              ->htmlTemplate('gitcard/gitcard.html.twig') // Especifica la plantilla Twig para el cuerpo HTML
              ->context([
              'cupon' => $nombre,
              'nombre'=>$n_usuario,
              'descuento'=>$precio,
              'gasto_minimo'=>$gastoMinimo
               ]);
      
          $this->mailer->send($eml);
    }

    private function giftcard_recarga($producto,Login $login){
      
        $p = $this->em->getRepository(Productos::class)->find($producto);
        $user=$login;

        if($user instanceof Login){
            $saldo= $user->getSaldo();
            $ganancia=$user->getGanancia();
    
        }
        if (!$saldo){
            $saldo =new Saldo();
            $saldo->setLogin($user);
            $this->em->persist($saldo);
            $this->em->flush();
        }


            $recarga= new Recargas();
            $recarga->setProducto($p);
            $recarga->setSaldo($saldo);
            $recarga->setTipoRecarga('CUPON_RECARGA');
            $saldo->setSaldo($saldo->getSaldo() + $p->getPrecioNormalProducto());
            $producto->setDisponibilidadProducto(false);
            $this->em->persist($recarga);
            $this->em->flush();
    
    }

}
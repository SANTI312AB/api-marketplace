<?php  

namespace App\Interfaces;

use App\Entity\DetallePedido;
use App\Entity\Pedidos;
use App\Repository\GeneralesAppRepository;
                                                              use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PedidosInterface{

     private $request;
     private $router;
 
     private $em;
     private $parameters;

     private $generalesAppRepository;
 
 
     public function __construct(RequestStack $request,UrlGeneratorInterface $router,EntityManagerInterface $em,ParameterBagInterface $parameters,GeneralesAppRepository $generalesAppRepository){
 
         $this->request = $request->getCurrentRequest(); 
         $this->router = $router;
         $this->em= $em;
         $this->parameters = $parameters;
         $this->generalesAppRepository = $generalesAppRepository->findOneBy(['nombre'=>'front','atributoGeneral'=>'Url']);
     }

     public function vista_venta(array $pedidos):JsonResponse{

        $domain = $this->request->getSchemeAndHttpHost(); 
        $host = $this->router->getContext()->getBaseUrl();
        $totalSubtotal = 0;
        $totalImpuestos = 0;
        $totalCostoEnvio = 0;
        $totalTotal = 0;
        $calculo_paypal=0;
        $subtototal_original=0;

        $venta = array_map(function($pedido) {     
            return  [
             
                'numero' => $pedido->getNVenta(),
                'tipo_pago'=>$pedido->getMetodoPago() ? $pedido->getMetodoPago()->getNombre():'',
                'cupon'=>[
                   'codigo'=>$pedido->getCupon() ? $pedido->getCupon()->getCodigoCupon():'',
                   'tipo_descuento'=> $pedido->getCupon() ? $pedido->getCupon()->getTipoDescuento():'' ,
                   'descuento'=> $pedido->getCupon() ? $pedido->getCupon()->getValorDescuento() :''
                  ],
                'fecha' =>$pedido->getFechaPedido()->format('c'),   
                'estado' => $pedido->getEstado() ? $pedido->getEstado():'',
                'referencia'=>$pedido->getReferenciaPedido() ? $pedido->getReferenciaPedido():'',
                'autorizacion'=>$pedido->getAutorizacion()?$pedido->getAutorizacion():'',
                'customer'=>[
                 'nombre'=>$pedido->getLogin()->getUsuarios() ? $pedido->getLogin()->getUsuarios()->getNombre():'',
                 'apellido'=>$pedido->getLogin()->getUsuarios() ? $pedido->getLogin()->getUsuarios()->getApellido():'',
                 'dni'=>$pedido->getDniCustomer() ? $pedido->getDniCustomer():'',
                 'celular'=>$pedido->getCelularCustomer() ? $pedido->getCelularCustomer():'',
                 'direccion_principal'=>$pedido->getDireccionPrincipal() ? $pedido->getDireccionPrincipal():'',
                 'direccion_secundaria'=>$pedido->getDireccionSecundaria() ? $pedido->getDireccionSecundaria():'',
                 'ubicacion_referencia'=>$pedido->getUbicacionReferencia() ? $pedido->getUbicacionReferencia():'',
                 'ciudad'=>$pedido->getCustomerCity() ? $pedido->getCustomerCity():'' ,
                 'provincia'=>$pedido->getProvincia() ? $pedido->getProvincia():'',
                 'region'=>$pedido->getRegion() ? $pedido->getRegion():'',
                 'codigo_postal'=>$pedido->getCodigoPostalCustomer() ? $pedido->getCodigoPostalCustomer():'',
                ],
             'datos_factura'=>[
                 'nombre' => $pedido->getFactura() ? $pedido->getFactura()->getNombre():'',
                 'apellido' =>  $pedido->getFactura() ? $pedido->getFactura()->getApellido():'',
                 'telefono' =>  $pedido->getFactura() ? $pedido->getFactura()->getTelefono():'',
                 'email' =>  $pedido->getFactura() ? $pedido->getFactura()->getEmail():'',
                 'dni' => $pedido->getFactura() ? $pedido->getFactura()->getDni():'',
               ],
               'saldo_usado' => $pedido->getMontoSaldo()?? 0,
               'monto_pasarela' =>$pedido->getMontoPasarela() ?? 0,
               'pago_mixto' => $pedido->isPagoMixto() ?? false
            ];
             
         }, $pedidos);
         
         $uniqueNVenta = isset($venta[0]) ? $venta[0] : [];
    
         $pedidosArray = [];
          
         
 
         foreach ($pedidos as $pedido) {
 
           $detallesArray=[];
 
         foreach ($pedido->getDetallePedidos() as $detalle) {
 
             
 
             $terminosArray = [];
             $imagenesArray = [];
             $variaciones = $detalle->getIdVariacion() ? $detalle->getIdVariacion() : null;
             
             if ($variaciones !== null) {
                 foreach ($variaciones->getTerminos() as $termino) {
                     $terminosArray[] = [
                         'atributo'=>$termino->getAtributos()->getNombre(),
                         'nombre' => $termino->getNombre()
                     ];
                 }
             
                 if ($variaciones->getVariacionesGalerias()->isEmpty()) {
                     foreach ($detalle->getIdProductos()->getProductosGalerias() as $galeria) {
                         $imagenesArray[] = [
                             'url' => $domain . $host . '/public/productos/' . $galeria->getUrlProductoGaleria(),
                         ];
                     }
                 } else {
                     foreach ($variaciones->getVariacionesGalerias() as $galeria) {
                         $imagenesArray[] = [
                             'url' => $domain . $host . '/public/productos/' . $galeria->getUrlVariacion(),
                         ];
                     }
                 }
             } else {
     
                 foreach ($detalle->getIdProductos()->getProductosGalerias() as $galeria) {
                     $imagenesArray[] = [
                         'url' => $domain . $host . '/public/productos/' . $galeria->getUrlProductoGaleria(),
                     ];
                 }
             }
             $detallesArray[]=[
                 'id'=>$detalle->getIdProductos()->getId(),
                 'nombre_producto'=>$detalle->getNombreProducto(),
                 'productos_tipo'=>$detalle->getIdProductos()->getProductosTipo()->getTipo(),
                 'slug'=>$detalle->getIdProductos()->getSlugProducto(),
                 'terminos'=>$terminosArray,
                 'galeria'=>$imagenesArray,
                 'tipo_envio'=>[
                     'id'=>$detalle->getIdProductos()->getEntrgasTipo()->getId(),
                     'nombre'=>$detalle->getIdProductos()->getEntrgasTipo()->getTipo(),
                  ], 
      
                 'cantidad'=>$detalle->getCantidad(),
                 'peso'=>$detalle->getPeso(),
                 'subtotal_original'=>$detalle->getSubtotalOriginal(),
                 'precio_unitario'=>$detalle->getPrecioUnitario(),
                 'subtotal'=>$detalle->getSubtotal(),
                 'impuestos'=>$detalle->getImpuesto(),
                 'precio_final'=>$detalle->getTotal() ,
                 'ciudad_remite'=>$detalle->getCiudadRemite(),
                 'provincia_remite'=>$detalle->getProvincia(),
                 'region_remite'=>$detalle->getRegion()
             ];
     
    
         }
       
             $pedidosArray [] =[
                 'numero'=>$pedido->getNumeroPedido(),   
                 'estado_envio'=>[
                   'id'=>$pedido->getEstadoEnvio()? $pedido->getEstadoEnvio() ->getId():'',
                  'nombre'=> $pedido->getEstadoEnvio() ? $pedido->getEstadoEnvio()->getNobreEstado():'',
                  'fecha_retirar'=> $pedido->getFechaRetirarAdomicilio()? $pedido->getFechaRetirarAdomicilio():'',
                  'fecha_retiro'=> $pedido->getFechaRetiroAdomicilio() ? $pedido->getFechaRetiroAdomicilio():'',
                  'fecha_entrego'=>$pedido->getFechaEntregaAdomicilio() ? $pedido->getFechaEntregaAdomicilio():''
                 ],
                 'estado_retiro'=>[
                    'id'=>$pedido->getEstadoRetiro() ? $pedido->getEstadoRetiro()->getId():'',
                    'nombre'=> $pedido->getEstadoRetiro() ? $pedido->getEstadoRetiro()->getNobreEstado():'',
                    'fecha_retirar'=> $pedido->getFechaRetirarFisico() ? $pedido->getFechaRetirarFisico():'',
                    'fecha_entrego'=>$pedido->getFechaEntregoFisico() ? $pedido->getFechaEntregoFisico():''
                 ],
             
                 'productos'=>$detallesArray,
                 'subtotal_original'=>$pedido->getSubtotalOriginal(),
                 'subtotal'=> $pedido->getSubtotal(),
                 'impuestos'=> $pedido->getIva(),
                 'costo_envio'=> $pedido->getCostoEnvio(),
                 'comision_paypal'=>$pedido->getComisionPaypal() ? $pedido->getComisionPaypal():0,
                 'total'=> $pedido->getTotalFinal(),
             ];
 
             $subtototal_original+=$pedido->getSubtotalOriginal();
             $totalSubtotal+= $pedido->getSubtotal();
             $totalImpuestos+= $pedido->getIva();
             $totalCostoEnvio+= $pedido->getCostoEnvio();
             $totalTotal+= $pedido->getTotalFinal();
             $calculo_paypal= $pedido->getComisionPaypal();
 
         }
 
             return new JsonResponse(['venta'=>$uniqueNVenta,'pedidos'=>$pedidosArray,'subtotal'=>$totalSubtotal,'impuestos'=>$totalImpuestos,'costo_envio'=>$totalCostoEnvio,'total'=>$totalTotal,'comision_paypal'=>$calculo_paypal,'subtotal_original'=>$subtototal_original]);       

     }

     public function vista_pedido_vendedor(Pedidos $pedido):JsonResponse{


          $domain = $this->request->getSchemeAndHttpHost(); 
          $host = $this->router->getContext()->getBaseUrl();

          $tienda= $pedido->getTienda();

          $factura= $pedido->getFactura();

          if ($factura !== null) {
              $nombre_factura  = $factura->getNombre();
              $apellido_factura = $factura->getApellido();
              $dni_factura     = $factura->getDni();
              $email_factura   = $factura->getEmail();
              $telefono_factura= $factura->getTelefono();
          
          }else{
              $nombre_factura  = '';
              $apellido_factura = '';
              $dni_factura     = '';
              $email_factura   = '';
              $telefono_factura= '';
       
          }
      
          $guiasArray = [];
      
          foreach ($pedido->getServientregas() as $guia) {
      
              if ($guia->getTienda() && $tienda && $guia->getTienda()->getId() == $tienda->getId() && $guia->isAnulado() == false) {
                  $guiasArray[] = [
                      'id' => $guia->getId(),
                      'n_guia' => $guia->getCodigoServientrega(),
                      'msj_servientrega' => $guia->getMsjServientrega(),
                      'tienda' => $guia->getTienda()->getId(),
                      'metodo_envio'=> $guia->getMetodoEnvio()->getNombre()
                  ];
              }
          }
      
          $cabeceraPedido = [
              'venta'=>$pedido->getNVenta(),
              'numero'=>$pedido->getNumeroPedido(),
              'tipo_pago'=>$pedido->getMetodoPago() ? $pedido->getMetodoPago()->getNombre():'',
              'cupon'=>[
                  'codigo'=>$pedido->getCupon() ? $pedido->getCupon()->getCodigoCupon():'',
                  'tipo_descuento'=> $pedido->getCupon() ? $pedido->getCupon()->getTipoDescuento():'' ,
                  'descuento'=> $pedido->getCupon() ? $pedido->getCupon()->getValorDescuento() :'' 
              ],
              'metodo_envio'=>[
                  "nombre"=>$pedido->getMetodoEnvio() ? $pedido->getMetodoEnvio()->getNombre():'',
                  "id"=>$pedido->getMetodoEnvio() ? $pedido->getMetodoEnvio()->getId():'',
              ],
               
                'referencia'=>$pedido->getReferenciaPedido(),
                'autorizacion'=>$pedido->getAutorizacion()?$pedido->getAutorizacion():'',
                'estado'=>$pedido->getEstado(),
                'tipo_envio'=>$pedido->getTipoEnvio(),
                'estado_envio'=>[
                       'id'=>$pedido->getEstadoEnvio()? $pedido->getEstadoEnvio() ->getId():'',
                       'nombre'=> $pedido->getEstadoEnvio() ? $pedido->getEstadoEnvio()->getNobreEstado():'',
                       'listo_para_retirar'=> $pedido->getFechaRetirarAdomicilio()? $pedido->getFechaRetirarAdomicilio()->format('c'):'',
                       'retirado'=> $pedido->getFechaRetiroAdomicilio() ? $pedido->getFechaRetiroAdomicilio()->format('c'):'',
                       'en_camino'=>$pedido->getFechaEnCamino() ? $pedido->getFechaEnCamino():'',
                       'entregado'=>$pedido->getFechaEntregaAdomicilio() ? $pedido->getFechaEntregaAdomicilio()->format('c'):''
                 ],
                'estado_retiro'=>[
                         'id'=>$pedido->getEstadoRetiro() ? $pedido->getEstadoRetiro()->getId():'',
                         'nombre'=> $pedido->getEstadoRetiro() ? $pedido->getEstadoRetiro()->getNobreEstado():'',
                         'listo_para_retirar'=> $pedido->getFechaRetirarFisico() ? $pedido->getFechaRetirarFisico()->format('c'):'',
                         'entregado'=>$pedido->getFechaEntregoFisico() ? $pedido->getFechaEntregoFisico()->format('c'):''
                 ],
                'fecha' => $pedido->getFechaPedido()->format('c'),
                'customer'=>[
                    'nombre'=>$pedido->getLogin()->getUsuarios()->getNombre(),
                    'apellido'=>$pedido->getLogin()->getUsuarios()->getApellido(),
                    'dni'=>$pedido->getDniCustomer(),
                    'celular'=>$pedido->getCelularCustomer(),
                    'direccion_principal'=>$pedido->getDireccionPrincipal() ? $pedido->getDireccionPrincipal():'',
                    'direccion_secundaria'=>$pedido->getDireccionSecundaria() ? $pedido->getDireccionSecundaria():'',
                    'ubicacion_referencia'=>$pedido->getUbicacionReferencia() ? $pedido->getUbicacionReferencia():'',
                    'ciudad'=>$pedido->getCustomerCity(),
                    'provincia'=>$pedido->getProvincia(),
                    'region'=>$pedido->getRegion(),
                    'codigo_postal'=>$pedido->getCodigoPostalCustomer(),
                ],
                'datos_factura'=>[
                    'nombre' => $nombre_factura,
                    'apellido' => $apellido_factura,
                    'telefono' => $telefono_factura,
                    'email' => $email_factura,
                    'dni' => $dni_factura,
                ],
                'guias'=>$guiasArray, 
          ];
      
          $detalles= $this->em->getRepository(DetallePedido::class)->findBy(['pedido'=>$pedido,'tienda'=>$tienda]);
       
          $detallesArray=[];
          foreach ($detalles as $detalle) {
              $terminosArray = [];
              $imagenesArray = [];
              $variaciones = $detalle->getIdVariacion() ? $detalle->getIdVariacion() : null;
              
              if ($variaciones !== null) {
                  foreach ($variaciones->getTerminos() as $termino) {
                      $terminosArray[] = [
                          'atributo'=>$termino->getAtributos()->getNombre(),
                          'nombre' => $termino->getNombre()
                      ];
                  }
              
                  if ($variaciones->getVariacionesGalerias()->isEmpty()) {
                      foreach ($detalle->getIdProductos()->getProductosGalerias() as $galeria) {
                          $imagenesArray[] = [
                              'url' => $domain . $host . '/public/productos/' . $galeria->getUrlProductoGaleria(),
                          ];
                      }
                  } else {
                      foreach ($variaciones->getVariacionesGalerias() as $galeria) {
                          $imagenesArray[] = [
                              'url' => $domain . $host . '/public/productos/' . $galeria->getUrlVariacion(),
                          ];
                      }
                  }
              } else {
      
                  foreach ($detalle->getIdProductos()->getProductosGalerias() as $galeria) {
                      $imagenesArray[] = [
                          'url' => $domain . $host . '/public/productos/' . $galeria->getUrlProductoGaleria(),
                      ];
                  }
              }
              $detallesArray[]=[
                  /*detalle_pedido*/
                  'id'=>$detalle->getIdProductos()->getId(),
                  'nombre_producto'=>$detalle->getNombreProducto(),
                  'productos_tipo'=>$detalle->getIdProductos()->getProductosTipo()->getTipo(),
                  'slug'=>$detalle->getIdProductos()->getSlugProducto(),
                  'terminos'=>$terminosArray,
                  'galeria'=>$imagenesArray,
                  'tipo_envio'=>[
                      'id'=>$detalle->getIdProductos()->getEntrgasTipo()->getId(),
                      'nombre'=>$detalle->getIdProductos()->getEntrgasTipo()->getTipo(),
                   ],
                  'cantidad'=>$detalle->getCantidad(),
                  'peso'=>$detalle->getPeso(),
                  'subtotal_original'=>$detalle->getSubtotalOriginal(),
                  'precio_unitario'=>$detalle->getSubtotalUnitario(),
                  'subtotal'=>$detalle->getSubtotal(),
                  'impuestos'=>$detalle->getImpuesto(),
                  'precio_final'=>$detalle->getTotal() ,
                  'ciudad_remite'=>$detalle->getCiudadRemite(),
                  'provincia_remite'=>$detalle->getProvincia(),
                  'region_remite'=>$detalle->getRegion()
              ];            
          }

          return new JsonResponse(['pedido'=>$cabeceraPedido,'productos'=>$detallesArray,'subtotal'=>$pedido->getSubtotal(),'impuestos'=>$pedido->getIva(),'costo_envio'=>$pedido->getCostoEnvio(),'total'=>$pedido->getTotalFinal(),'comision_paypal'=>$pedido->getComisionPaypal(),'subtotal_original'=>$pedido->getSubtotalOriginal()]);
     }


     public function vista_pedido_clinete(Pedidos $pedido):JsonResponse{
          $domain = $this->request->getSchemeAndHttpHost(); 
          $host = $this->router->getContext()->getBaseUrl();
          
          $factura= $pedido->getFactura();

          if ($factura !== null) {
              $nombre_factura  = $factura->getNombre();
              $apellido_factura = $factura->getApellido();
              $dni_factura     = $factura->getDni();
              $email_factura   = $factura->getEmail();
              $telefono_factura= $factura->getTelefono();
          
          }else{
              $nombre_factura  = '';
              $apellido_factura = '';
              $dni_factura     = '';
              $email_factura   = '';
              $telefono_factura= '';
       
          }
      
           $url=null;
           if($pedido->getEstado() === 'PENDING'){
               if($pedido->getMetodoPago()->getId() == 1){
                    $url= $this->generalesAppRepository->getValorGeneral() . '/checkout/deposito/' .$pedido->getNVenta();
               }else{
                  $url= $pedido->getUrlPago();
               }
           }   
      
          $cabeceraPedido = [
              'venta'=>$pedido->getNVenta(),
              'url_pago'=>$url,
              'tipo_pago'=>$pedido->getMetodoPago() ? $pedido->getMetodoPago()->getNombre():'',
              'cupon'=>[
                  'codigo'=>$pedido->getCupon() ? $pedido->getCupon()->getCodigoCupon():'',
                  'tipo_descuento'=> $pedido->getCupon() ? $pedido->getCupon()->getTipoDescuento():'' ,
                  'descuento'=> $pedido->getCupon() ? $pedido->getCupon()->getValorDescuento() :''
                 ],
              'metodo_envio'=>[
                  "nombre"=>$pedido->getMetodoEnvio() ? $pedido->getMetodoEnvio()->getNombre():'',
                  "id"=>$pedido->getMetodoEnvio() ? $pedido->getMetodoEnvio()->getId():'',
              ],
              'numero'=>$pedido->getNumeroPedido(),
                'referencia'=>$pedido->getReferenciaPedido(),
                'autorizacion'=>$pedido->getAutorizacion()?$pedido->getAutorizacion():'',
                'estado'=>$pedido->getEstado(),
                'tipo_envio'=>$pedido->getTipoEnvio(),
                 'estado_envio'=>[
                       'id'=>$pedido->getEstadoEnvio()? $pedido->getEstadoEnvio() ->getId():'',
                       'nombre'=> $pedido->getEstadoEnvio() ? $pedido->getEstadoEnvio()->getNobreEstado():'',
                       'listo_para_retirar'=> $pedido->getFechaRetirarAdomicilio()? $pedido->getFechaRetirarAdomicilio()->format('c'):'',
                       'retirado'=> $pedido->getFechaRetiroAdomicilio() ? $pedido->getFechaRetiroAdomicilio()->format('c'):'',
                       'en_camino'=>$pedido->getFechaEnCamino() ? $pedido->getFechaEnCamino():'',
                       'entregado'=>$pedido->getFechaEntregaAdomicilio() ? $pedido->getFechaEntregaAdomicilio()->format('c'):''
                 ],
                'estado_retiro'=>[
                         'id'=>$pedido->getEstadoRetiro() ? $pedido->getEstadoRetiro()->getId():'',
                         'nombre'=> $pedido->getEstadoRetiro() ? $pedido->getEstadoRetiro()->getNobreEstado():'',
                         'listo_para_retirar'=> $pedido->getFechaRetirarFisico() ? $pedido->getFechaRetirarFisico()->format('c'):'',
                         'entregado'=>$pedido->getFechaEntregoFisico() ? $pedido->getFechaEntregoFisico()->format('c'):''
                 ],
                'fecha' => $pedido->getFechaPedido()->format('c'),
                'customer'=>[
                    'nombre'=>$pedido->getLogin()->getUsuarios()->getNombre(),
                    'apellido'=>$pedido->getLogin()->getUsuarios()->getApellido(),
                    'dni'=>$pedido->getDniCustomer(),
                    'celular'=>$pedido->getCelularCustomer(),
                    'direccion_principal'=>$pedido->getDireccionPrincipal() ? $pedido->getDireccionPrincipal():'',
                    'direccion_secundaria'=>$pedido->getDireccionSecundaria() ? $pedido->getDireccionSecundaria():'',
                    'ubicacion_referencia'=>$pedido->getUbicacionReferencia() ? $pedido->getUbicacionReferencia():'',
                    'ciudad'=>$pedido->getCustomerCity(),
                    'provincia'=>$pedido->getProvincia() ? $pedido->getProvincia():'',
                    'region'=>$pedido->getRegion() ? $pedido->getRegion():'',
                    'codigo_postal'=>$pedido->getCodigoPostalCustomer(),
                ],
                'datos_factura'=>[
                    'nombre' => $nombre_factura,
                    'apellido' => $apellido_factura,
                    'telefono' => $telefono_factura,
                    'email' => $email_factura,
                    'dni' => $dni_factura,
                ],
          ];
          $detalles= $this->em->getRepository(DetallePedido::class)->findBy(['pedido'=>$pedido]);
      
          $detallesArray=[];
          foreach ($detalles as $detalle) {
              $terminosArray = [];
              $imagenesArray = [];
              $variaciones = $detalle->getIdVariacion() ? $detalle->getIdVariacion() : null;
              
              if ($variaciones !== null) {
                  foreach ($variaciones->getTerminos() as $termino) {
                      $terminosArray[] = [
                          'atributo'=>$termino->getAtributos()->getNombre(),
                          'nombre' => $termino->getNombre()
                      ];
                  }
              
                  if ($variaciones->getVariacionesGalerias()->isEmpty()) {
                      foreach ($detalle->getIdProductos()->getProductosGalerias() as $galeria) {
                          $imagenesArray[] = [
                              'url' => $domain . $host . '/public/productos/' . $galeria->getUrlProductoGaleria(),
                          ];
                      }
                  } else {
                      foreach ($variaciones->getVariacionesGalerias() as $galeria) {
                          $imagenesArray[] = [
                              'url' => $domain . $host . '/public/productos/' . $galeria->getUrlVariacion(),
                          ];
                      }
                  }
              } else {
      
                  foreach ($detalle->getIdProductos()->getProductosGalerias() as $galeria) {
                      $imagenesArray[] = [
                          'url' => $domain . $host . '/public/productos/' . $galeria->getUrlProductoGaleria(),
                      ];
                  }
              }
              $detallesArray[]=[
                  /*detalle_pedido*/
                  'id'=>$detalle->getIdProductos()->getId(),
                  'nombre_producto'=>$detalle->getNombreProducto(),
                  'productos_tipo'=>$detalle->getIdProductos()->getProductosTipo()->getTipo(),
                  'slug'=>$detalle->getIdProductos()->getSlugProducto(),
                  'terminos'=>$terminosArray,
                  'galeria'=>$imagenesArray,
                  'tipo_envio'=>[
                      'id'=>$detalle->getIdProductos()->getEntrgasTipo()->getId(),
                      'nombre'=>$detalle->getIdProductos()->getEntrgasTipo()->getTipo(),
                   ],    
                  'cantidad'=>$detalle->getCantidad(),
                  'peso'=>$detalle->getPeso(),
                  'subtotal_original'=>$detalle->getSubtotalOriginal(),
                  'precio_unitario'=>$detalle->getPrecioUnitario(),
                  'subtotal'=>$detalle->getSubtotal(),
                  'impuestos'=>$detalle->getImpuesto(),
                  'precio_final'=>$detalle->getTotal(),
                  'ciudad_remite'=>$detalle->getCiudadRemite(),
                  'provincia_remite'=>$detalle->getProvincia(),
                  'region_remite'=>$detalle->getRegion()
              ];
      
          }
      
      
          return new JsonResponse(['pedido'=>$cabeceraPedido,'productos'=>$detallesArray,'subtotal'=>$pedido->getSubtotal(),'impuestos'=>$pedido->getIva(),'costo_envio'=>$pedido->getCostoEnvio(),'total'=>$pedido->getTotalFinal(),'comision_paypal'=>$pedido->getComisionPaypal(),'subtotal_original'=>$pedido->getSubtotalOriginal()]);
     }

     public function lista_pedidos_cliente(Pedidos $pedido){
        
          $factura = $pedido->getFactura();

          if ($factura !== null) {
              $nombre_factura = $factura->getNombre();
              $apellido_factura = $factura->getApellido();
              $dni_factura = $factura->getDni();
              $email_factura = $factura->getEmail();
              $telefono_factura = $factura->getTelefono();
          } else {
              $nombre_factura = '';
              $apellido_factura = '';
              $dni_factura = '';
              $email_factura = '';
              $telefono_factura = '';
          }
  
          $catidad_total = 0;
          foreach ($pedido->getDetallePedidos() as $detalle) {
              $catidad_total += $detalle->getCantidad();
          }
  
          $estado_envio = false;
          if ($pedido->getEstado() === 'APPROVED' && $pedido->getTipoEnvio() === 'A DOMICILIO' && $pedido->getEstadoEnvio()->getId() == 22) {
              $estado_envio = true;
          } elseif ($pedido->getEstado() === 'APPROVED' && $pedido->getTipoEnvio() === 'RETIRO EN TIENDA FISICA' && $pedido->getEstadoRetiro()->getId() == 22) {
              $estado_envio = true;
          } elseif ($pedido->getEstado() === 'APPROVED' && $pedido->getTipoEnvio() === 'AMBOS' && $pedido->getEstadoRetiro()->getId() == 22 && $pedido->getEstadoEnvio()->getId() == 22) {
              $estado_envio = true;
          } else {
              $estado_envio = false;
          }

  
          $pedidosArray = [
              'venta' => $pedido->getNVenta(),
              'numero' => $pedido->getNumeroPedido(),
              'tipo_pago' => $pedido->getMetodoPago() ? $pedido->getMetodoPago()->getNombre() : '',
              'referencia' => $pedido->getReferenciaPedido(),
              'autorizacion' => $pedido->getAutorizacion() ? $pedido->getAutorizacion() : '',
              'estado' => $pedido->getEstado(),
              'fecha' => $pedido->getFechaPedido(),
              'estado_envio' => [
                  'id' => $pedido->getEstadoEnvio() ? $pedido->getEstadoEnvio()->getId() : '',
                  'nombre' => $pedido->getEstadoEnvio() ? $pedido->getEstadoEnvio()->getNobreEstado() : '',
              ],
              'estado_retiro' => [
                  'id' => $pedido->getEstadoRetiro() ? $pedido->getEstadoRetiro()->getId() : '',
                  'nombre' => $pedido->getEstadoRetiro() ? $pedido->getEstadoRetiro()->getNobreEstado() : '',
              ],
              'tipo_envio' => $pedido->getTipoEnvio(),
              'venta_concretada' => $estado_envio,
              'customer' => [
                  'nombre' => $pedido->getLogin()->getUsuarios()->getNombre(),
                  'apellido' => $pedido->getLogin()->getUsuarios()->getApellido(),
                  'dni' => $pedido->getDniCustomer(),
                  'celular' => $pedido->getCelularCustomer(),
                  'direccion_principal' => $pedido->getDireccionPrincipal() ? $pedido->getDireccionPrincipal() : '',
                  'direccion_secundaria' => $pedido->getDireccionSecundaria() ? $pedido->getDireccionSecundaria() : '',
                  'ubicacion_referencia' => $pedido->getUbicacionReferencia() ? $pedido->getUbicacionReferencia() : '',
                  'ciudad' => $pedido->getCustomerCity(),
                  'codigo_postal' => $pedido->getCodigoPostalCustomer(),
              ],
              'datos_factura' => [
                  'nombre' => $nombre_factura,
                  'apellido' => $apellido_factura,
                  'telefono' => $telefono_factura,
                  'email' => $email_factura,
                  'dni' => $dni_factura,
              ],
              'total' => $pedido->getTotalFinal(),
              'items' => $catidad_total
          ];

          return $pedidosArray;

     }

     public function lista_pedidos_vendedor(Pedidos $pedido){
        
          $catidad_total = 0;
          foreach ($pedido->getDetallePedidos() as $detalle) {
              $catidad_total += $detalle->getCantidad();
          }
  
          $estado_envio = false;
          if ($pedido->getEstado() === 'APPROVED' && $pedido->getTipoEnvio() === 'A DOMICILIO' && $pedido->getEstadoEnvio()->getId() == 22) {
              $estado_envio = true;
          } elseif ($pedido->getEstado() === 'APPROVED' && $pedido->getTipoEnvio() === 'RETIRO EN TIENDA FISICA' && $pedido->getEstadoRetiro()->getId() == 22) {
              $estado_envio = true;
          } elseif ($pedido->getEstado() === 'APPROVED' && $pedido->getTipoEnvio() === 'AMBOS' && $pedido->getEstadoRetiro()->getId() == 22 && $pedido->getEstadoEnvio()->getId() == 22) {
              $estado_envio = true;
          } else {
              $estado_envio = false;
          }  
          $pedidosArray= [
              'venta' => $pedido->getNVenta(),
              'tipo_pago' => $pedido->getMetodoPago() ? $pedido->getMetodoPago()->getNombre() : '',
              'numero_orden' => $pedido->getNumeroPedido(),
              'estado' => $pedido->getEstado(),
              'estado_envio' => [
                  'id' => $pedido->getEstadoEnvio() ? $pedido->getEstadoEnvio()->getId() : '',
                  'nombre' => $pedido->getEstadoEnvio() ? $pedido->getEstadoEnvio()->getNobreEstado() : '',
              ],
              'estado_retiro' => [
                  'id' => $pedido->getEstadoRetiro() ? $pedido->getEstadoRetiro()->getId() : '',
                  'nombre' => $pedido->getEstadoRetiro() ? $pedido->getEstadoRetiro()->getNobreEstado() : '',
              ],
              'tipo_envio' => $pedido->getTipoEnvio(),
              'venta_concretada' => $estado_envio,
              'fecha' => $pedido->getFechaPedido(),
              'autorizacion' => $pedido->getAutorizacion() ? $pedido->getAutorizacion() : '',
              'total' => $pedido->getTotalFinal(),
              'items' => $catidad_total,
              'ciudad_envio' => $pedido->getCustomerCity(),
              'cliente' => [
                  'nombres' => $pedido->getLogin()->getUsuarios()->getNombre() . ' ' . $pedido->getLogin()->getUsuarios()->getApellido(),
                  'dni' => $pedido->getDniCustomer(),
                  'celular' => $pedido->getCelularCustomer(),
              ],
          ];

          return $pedidosArray;

     }

}
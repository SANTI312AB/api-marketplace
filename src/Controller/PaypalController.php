<?php

namespace App\Controller;

use App\Entity\FuncionesEspeciales;
use App\Entity\GeneralesApp;
use App\Interfaces\ErrorsInterface;
use App\Service\DynamicMailerFactory;
use App\Service\PaypalService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use App\Entity\Carrito;
use App\Entity\Cupon;
use App\Entity\Estados;
use App\Entity\Pedidos;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Attributes as OA;
use App\Service\EmailPedidoService;
use App\Service\QrCodeGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaypalController extends AbstractController
{

    private $emailPedidoService;

    private $qrCodeGenerator;

    private $paypalService;

    private $errorsInterface;

    private $mailer;

    public function __construct(EmailPedidoService $emailPedidoService,QrCodeGenerator $qrCodeGenerator, PaypalService $paypalService, ErrorsInterface $errorsInterface, DynamicMailerFactory $mailer){
        $this->emailPedidoService = $emailPedidoService;
        $this->qrCodeGenerator = $qrCodeGenerator;
        $this->paypalService = $paypalService;
        $this->errorsInterface = $errorsInterface;
        $this->mailer = $mailer;
    }


/*
     #[Route('/paypal/ver_orden/{pedido}', name: 'ver_orden_paypal', methods:['GET', 'OPTIONS'])]
     public function ver_orden($pedido,EntityManagerInterface $entityManager,GeneralesAppRepository $generalesAppRepositor)
     {
        $pedido = $entityManager->getRepository(Pedidos::class)->findBy(['n_venta' => $pedido]);
        
        if (!$pedido) {
            return $this->json(['error' => 'Pedido no encontrado'], Response::HTTP_NOT_FOUND);
        }

        foreach ($pedido as $pedido) {

        $id = $pedido->getReferenciaPedido();
        $url = $this->paypalService->data_url()."/v2/checkout/orders/".$id;

        $auth = $this->paypalService->getToken();
      
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "Authorization: Bearer " . $auth
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        $rest= $result;

        $json = json_decode($rest);

        if (curl_errno($ch)) {
            return $this->json(['response' => 'Error Paypal: ' . curl_error($ch)])->setStatusCode($httpCode);
        }
        if ($httpCode !== 200){

            return $this->json($json)->setStatusCode($httpCode);    
        }


       }

        return $this->json($json->links[1]->href);
    
     }



     #[Route('/api/paypal/capturar_pago/{pedido}', name: 'autorizar_pago_paypal',methods:['GET','OPTIONS'])]
     public function autorizar_pago($pedido,EntityManagerInterface $entityManager, GeneralesAppRepository $generalesAppRepositor): Response
     {
        
        $pedido = $entityManager->getRepository(Pedidos::class)->findBy(['n_venta' => $pedido]);

        if (!$pedido) {
            return $this->json(['error' => 'Pedido no encontrado'], Response::HTTP_NOT_FOUND);
        }

        foreach ($pedido as $pedido) {
            
            $id = $pedido->getReferenciaPedido();
            $n_venta = $pedido->getNVenta();

            $url = $this->paypalService->data_url()."/v2/checkout/orders/$id/capture";
    
            $auth = $this->paypalService->getToken();
    
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
             curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
             curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
             curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "PayPal-Request-Id: " . $n_venta, // Usar solo el n_venta como PayPal-Request-Id
                "Authorization: Bearer " . $auth,
            ]);
    
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
            $rest = $result;
    
            $json = json_decode($rest);

        }
    
        
        return $this->json($json)->setStatusCode(Response::HTTP_OK);
  
     }

     */

      #[Route('/paypal_test', name: 'paypal_test_function', methods:['POST'])]
      #[OA\Tag(name: 'Pagos')]
      public function action(): Response
      {
        $n_venta = 'V-' . rand(0000, 9999);
        
        $subtotal=4.860869565217391;
        $iva= 0.7291304347826086;
        $costo_envio= 1.76445;
        $total= 7.6486279999999995;
        try{
            $response= $this->paypalService->createOrder($n_venta, $total, $subtotal, $iva, $costo_envio);
        
            $api_response= json_decode($response);
            $request_id= $api_response->id;
            $url= $api_response->links[1]->href;
        }catch(Exception $e){
            
            $error[]=[
                'description'=>$e->getMessage(),
            ];

            return $this->errorsInterface->error_message('Error al procesar la transacción PayPal.',$error);
        }

         return $this->json($url);
      }


     #[Route('/paypal/hook', name: 'hook_paypal', methods:['POST'])]
     #[OA\Tag(name: 'Pagos')]
     #[OA\RequestBody(
        required: true,
        description:'Actualiza el estado del pedido con los servicios de paypal',
        content: new OA\JsonContent(
            example: [
        
            ]
        )
    )]
     public function hoock(Request $request,EntityManagerInterface $entityManager,UrlGeneratorInterface $router)
     {
        $domain = $request->getSchemeAndHttpHost(); 
        $host = $router->getContext()->getBaseUrl();
    
        $body = $request->getContent();
    
        $data = json_decode($body, true);
  
        $n_venta = $data['resource']['purchase_units'][0]['reference_id'];
        $referenceId= $data['resource']['id'];

        $ingresado= $entityManager->getRepository(Estados::class)->findOneBy(['id' => 19]);
        $pendiente = $entityManager->getRepository(Estados::class)->findOneBy(['id' => 23]);
    
        $pedidos = $entityManager->getRepository(Pedidos::class)->findBy(['n_venta' =>$n_venta,'referencia_pedido'=>$referenceId, 'metodo_pago'=>3 ]);

        if (!$pedidos) {
           return $this->errorsInterface->error_message('Pedido no encontrado', Response::HTTP_NOT_FOUND);
        }

        $cartsToDelete = []; 
        $cuponToUpdate=[];
        $cliente=[];
        $total_Subtotal=0;
        $total_iva=0;
        $total_costo_envio= 0;
        $total_final=0;
        $total_comision_paypal=0;
        $data=[];
        foreach ($pedidos as $pedido) {

        $id_cupon=$pedido->getCupon() ? $pedido->getCupon()->getId():null;
        if($id_cupon){
            $cupon= $entityManager->getRepository(Cupon::class)->findOneBy(['id'=>$id_cupon]);
        }else{
            $cupon= null;
        }

        $user = $pedido->getLogin();
        $carrito = $entityManager->getRepository(Carrito::class)->findOneBy(['login' => $user]);

        $cliente[]= $pedido;

        $id = $pedido->getReferenciaPedido();
        $n_venta = $pedido->getNVenta();
        $url = $this->paypalService->data_url()."/v2/checkout/orders/".$id;

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
        
        $rest= $result;

        $json = json_decode($rest);


        if (curl_errno($ch)) {
            return $this->errorsInterface->error_message('Error Paypal: ' . curl_error($ch), $httpCode);
        }
        if ($httpCode !== 200){
            return $this->errorsInterface->error_message($json, $httpCode);
        }  


        if ($json->status === 'APPROVED') {

            $url = $this->paypalService->data_url()."/v2/checkout/orders/$id/capture";

            $auth = $this->paypalService->getToken();
    
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
             curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
             curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
             curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "PayPal-Request-Id: " . $n_venta, // Usar solo el n_venta como PayPal-Request-Id
                "Authorization: Bearer " . $auth,
            ]);
    
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
            $rest = $result;
    
            $j = json_decode($rest);

            if($j->status === 'COMPLETED'){
               $pedido->setEstado("APPROVED"); 
               $pedido->setEstadoEnvio($ingresado);
               $pedido->setEstadoRetiro($ingresado);
               $pedido->setFechaPago( new \DateTime());
               $entityManager->flush();


               if ($carrito !== null) {

                $cartsToDelete[] = $carrito; 

               }

             if ($cupon !== null) {

                $cuponToUpdate[] = $cupon; 
            
             }
   
            }
            
        }elseif($json->status === 'COMPLETED'){
            $pedido->setEstado('APPROVED');
            $pedido->setEstadoEnvio($ingresado);
            $pedido->setEstadoRetiro($ingresado);
            $pedido->setFechaPago( new \DateTime());
            $entityManager->flush();


            
        }elseif($json->status === 'APPROVED' || $json->status === 'PAYER_ACTION_REQUIRED' ){
            $pedido->setEstado('PENDING');
            $pedido->setEstadoEnvio($pendiente);
            $pedido->setEstadoRetiro($pendiente);
            $pedido->setFechaPago( new \DateTime());
            $entityManager->flush();
        }
  
        //email servicio

        $data= $this->emailPedidoService->sendemail_pedido($pedido, $pedido->getEstado());
         
        $total_Subtotal+= $pedido->getSubtotal();
        $total_iva+=$pedido->getIva();
        $total_costo_envio+=$pedido->getCostoEnvio();
        $total_comision_paypal+=$pedido->getComisionPaypal();
        $total_final= $total_Subtotal + $total_iva + $total_costo_envio + $total_comision_paypal;

       }

        foreach ($cartsToDelete as $cartToDelete) {
             $this->deleteCarrito($entityManager, $cartToDelete);
        }

        foreach ($cuponToUpdate as $cupon) {

            $uso_cupon= $cupon->getUsoCupon() ? $cupon->getUsoCupon():0;
            $cupon->setUsoCupon($uso_cupon + 1);
            $entityManager->flush();

        }


          foreach ($cliente as  $c){
            $email_cliente= $c->getLogin()->getEmail();
            $nombre_cliente= $c->getLogin()->getUsuarios()->getNombre().' '.$pedido->getLogin()->getUsuarios()->getApellido();
            $n_venta= $c->getNVenta();
            $estado=$c->getEstado();
            $metodo_pago=$c->getMetodoPago()->getNombre();
            $direccion_cliente= $c->getDireccionPrincipal() .'y'. $c->getDireccionSecundaria().' '.$c->getCustomerCity();
            $estado_pago= $c->getEstado();
            $n_pedido= $c->getNumeroPedido();
        }

        $front_url= $entityManager->getRepository(GeneralesApp::class)->findOneBy(['nombre'=>'front','atributoGeneral'=>'Url']);

        $url_front = $front_url->getValorGeneral()."/cuenta/compras/{$n_venta}";
    
        $qrFilename = $this->qrCodeGenerator->generateAndSave(
            $url_front,
            "venta_{$n_venta}.png"
        );

            switch ($estado) {
                case 'APPROVED':
                    $admin_email_pedidos = $entityManager->getRepository(FuncionesEspeciales::class)->findOneBy(['id' => 2]);
                    $email_facturacion = $entityManager->getRepository(FuncionesEspeciales::class)->findOneBy(['id' => 5]);
            
                    $emu = null;
                    if ($email_facturacion->isActivo() && $admin_email_pedidos->isActivo()) {
                        $emu = [
                            $admin_email_pedidos->getDescripcion(),
                            $email_facturacion->getDescripcion()
                        ];
                    } elseif ($email_facturacion->isActivo()) {
                        $emu = [$email_facturacion->getDescripcion()];
                    } elseif ($admin_email_pedidos->isActivo()) {
                        $emu = [$admin_email_pedidos->getDescripcion()];
                    }
            
                    if ($emu !== null) {
                        try {
                            $eml = (new TemplatedEmail())
                                ->to(...$emu)
                                ->subject('La venta ' . $n_venta . ' ha sido ')
                                ->htmlTemplate('pedidos/estado_pedido_cliente.html.twig')
                                ->context([
                                    'qr'=>$domain . $host . '/public/qr_codes/' .$qrFilename,
                                    'n_venta' => $n_venta,
                                    'nombre' => $nombre_cliente,
                                    'metodo_pago' => $metodo_pago,
                                    'detalle' => $data,
                                    'subtotal' => $total_Subtotal,
                                    'impuestos' => $total_iva,
                                    'costo_envio' => $total_costo_envio,
                                    'direccion_cliente' => $direccion_cliente,
                                    'comision_paypal'=> $total_comision_paypal,
                                    'total' => $total_final,
                                    'estado_pago' => $estado_pago
                                ]);
            
                            $this->mailer->send($eml);
                        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
                            // Log the error or handle it as needed
                            error_log('Error enviando el correo: ' . $e->getMessage());
                        }
                    }
                    break;
            }
    
             $eml = (new TemplatedEmail())
                ->to($email_cliente)
                ->subject('El estado de la venta'.' '. $n_venta . ' ha sido ')
                ->htmlTemplate('pedidos/estado_pedido_cliente.html.twig') // Especifica la plantilla Twig para el cuerpo HTML
                ->context([
                'qr'=>$domain . $host . '/public/qr_codes/' .$qrFilename,
                'n_venta' => $n_venta,
                'nombre'=>$nombre_cliente,
                'metodo_pago'=>$metodo_pago,
                'detalle'=>$data,
                'subtotal' => $total_Subtotal,
                'impuestos' => $total_iva,
                'costo_envio' => $total_costo_envio, 
                'direccion_cliente'=>$direccion_cliente,
                'total' => $total_final, 
                'comision_paypal'=> $total_comision_paypal,
                'estado_pago'=>$estado_pago 
                 ]);
        
                $this->mailer->send(message: $eml);
        
        

        return $this->errorsInterface->succes_message($json->status,'venta',$n_venta);

    }

     private function deletecarrito(EntityManagerInterface $entityManager,Carrito $carrito):void{
        $entityManager->remove($carrito);
        $entityManager->flush();
     }

}

<?php

namespace App\Controller;

use App\Entity\Carrito;
use App\Entity\Cupon;
use App\Entity\Estados;
use App\Entity\FuncionesEspeciales;
use App\Entity\GeneralesApp;
use App\Entity\Pedidos;
use App\Interfaces\ErrorsInterface;
use App\Service\DynamicMailerFactory;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Service\EmailPedidoService;
use App\Service\QrCodeGenerator;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class PlaceToPayController extends AbstractController
{

    private $emailPedidoService;

    private $qrCodeGenerator;

    private $errorsInterface;

    private $mailer;

    public function __construct(EmailPedidoService $emailPedidoService,QrCodeGenerator $qrCodeGenerator, ErrorsInterface $errorsInterface,DynamicMailerFactory $mailer){
        $this->emailPedidoService = $emailPedidoService;
        $this->qrCodeGenerator = $qrCodeGenerator;
        $this->errorsInterface = $errorsInterface;
        $this->mailer = $mailer;
    }


    #[Route('/notificacion_pago', name: 'hook-placetopay', methods: ['POST'])]
    #[OA\Tag(name: 'Pagos')]
    #[OA\RequestBody(
        required: true,
        description: 'Actualiza el estado de un pedido con los servicio de place to pay',
        content: new OA\JsonContent(
            example: [

            ]
        )
    )]
    public function notificacionSolicitud(Request $request, EntityManagerInterface $entityManager, UrlGeneratorInterface $router)
    {
        $domain = $request->getSchemeAndHttpHost();
        $host = $router->getContext()->getBaseUrl();
        $data = json_decode($request->getContent(), true);

        $data = [
            "status" => [
                "status" => $data['status']['status'],
                "reason" => $data['status']['reason'],
                "message" => $data['status']['message'],
                "date" => $data['status']['date']
            ],
            "requestId" => $data['requestId'],
            "reference" => $data['reference'],
            "signature" => $data['signature']
        ];

        $pedidos = $entityManager->getRepository(Pedidos::class)->findBy(['referencia_pedido' => $data['requestId'], 'n_venta' => $data['reference'], 'metodo_pago' => 2]);

        if (empty($pedidos)) {
            return $this->errorsInterface->error_message('No hay pedidos con este número de venta', Response::HTTP_NOT_FOUND);
        }

        $cartsToDelete = [];

        $cuponToUpdate = [];
        $cliente = [];
        $total_Subtotal = 0;
        $total_iva = 0;
        $total_costo_envio = 0;
        $total_final = 0;
        $data = null;
        foreach ($pedidos as $pedido) {

            $id = $pedido->getReferenciaPedido();
            $id_cupon = $pedido->getCupon() ? $pedido->getCupon()->getId() : null;
            $user = $pedido->getLogin();

            $carrito = $entityManager->getRepository(Carrito::class)->findOneBy(['login' => $user]);

            $cliente[] = $pedido;

            if ($id_cupon) {
                $cupon = $entityManager->getRepository(Cupon::class)->findOneBy(['id' => $id_cupon]);
            } else {
                $cupon = null;
            }

            $generales = $entityManager->getRepository(GeneralesApp::class);
            $auth = $generales->getLoginPTP();
            $front_url = $generales->findOneBy(['nombre' => 'front', 'atributoGeneral' => 'Url']);

            $apiUrl = $generales->data_url() . "/session/" . $id;
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array_merge($auth)));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $rest = $response;
            if (curl_errno($ch)) {

                return $this->errorsInterface->error_message('Error cURL: ' . curl_error($ch), $httpCode);
            }

            if ($httpCode != 200) {
                return $this->errorsInterface->error_message('Respuesta cURL ' . $httpCode . ' : ' . $response, $httpCode);
            }

            $json = json_decode($response, true);

            // Update the order's details
            if (isset($json['payment']) && is_array($json['payment']) && count($json['payment']) > 0) {
                $authorization = $json['payment'][0]['authorization'];
                $internalReference = $json['payment'][0]['internalReference'];
                $fecha_pago = $json['payment'][0]['status']['date'] ?? null;
                $pedido->setAutorizacion($authorization);
                $pedido->setReferenciaInterna($internalReference);
                $pedido->setFechaPago(new \DateTime($fecha_pago));
            }

            if (isset($json['payment'][0]['status']['status'])) {
                $estadoPago = $json['payment'][0]['status']['status'];
            } elseif (isset($json['status']['status'])) {
                $estadoPago = $json['status']['status'];
            } else {
                $estadoPago = null; // or some default value
            }


            $pedido->setEstado($estadoPago);

            $ingresado = $entityManager->getRepository(Estados::class)->findOneBy(['id' => 19]);
            $pendiente = $entityManager->getRepository(Estados::class)->findOneBy(['id' => 23]);
            $cancelado = $entityManager->getRepository(Estados::class)->findOneBy(['id' => 24]);

            $emu = [
                $email_vendedor = $pedido->getTienda()->getLogin()->getEmail()
            ];

            switch ($estadoPago) {
                case 'APPROVED':
                    $pedido->setEstadoEnvio($ingresado);
                    $pedido->setEstadoRetiro($ingresado);
                    if ($carrito !== null) {

                        $cartsToDelete[] = $carrito;

                    }

                    if ($cupon !== null) {

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

                case null:
                    $pedido->setEstadoEnvio($cancelado);
                    $pedido->setFechaRechazo(new DateTime());
            }


            $entityManager->flush();

            $data = $this->emailPedidoService->sendemail_pedido($pedido, $estadoPago);
            $total_Subtotal += $pedido->getSubtotal();
            $total_iva += $pedido->getIva();
            $total_costo_envio += $pedido->getCostoEnvio();
            $total_final = $total_Subtotal + $total_iva + $total_costo_envio;

        }


        foreach ($cuponToUpdate as $cupon) {
            $uso_cupon = $cupon->getUsoCupon() ?? 0;
            $cupon->setUsoCupon($uso_cupon + 1);
            $entityManager->flush();
        }


        foreach ($cartsToDelete as $cartToDelete) {
            $this->deleteCarrito($entityManager, $cartToDelete);
        }


        foreach ($cliente as $c) {

            $email_cliente = $c->getLogin()->getEmail();
            $nombre_cliente = $c->getLogin()->getUsuarios()->getNombre() . ' ' . $pedido->getLogin()->getUsuarios()->getApellido();
            $n_venta = $c->getNVenta();
            $estado = $c->getEstado();
            $metodo_pago = $c->getMetodoPago()->getNombre();
            $direccion_cliente = $c->getDireccionPrincipal() . 'y' . $c->getDireccionSecundaria() . ' ' . $c->getCustomerCity();
            $estado_pago = $c->getEstado();
            $n_pedido = $c->getNumeroPedido();
        }

        $url = $front_url->getValorGeneral() . "/cuenta/compras/{$n_venta}";


        $qrFilename = $this->qrCodeGenerator->generateAndSave(
            $url,
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
                                'qr' => $domain . $host . '/public/qr_codes/' . $qrFilename,
                                'n_venta' => $n_venta,
                                'nombre' => $nombre_cliente,
                                'metodo_pago' => $metodo_pago,
                                'detalle' => $data,
                                'subtotal' => $total_Subtotal,
                                'impuestos' => $total_iva,
                                'costo_envio' => $total_costo_envio,
                                'direccion_cliente' => $direccion_cliente,
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
            ->subject('El estado de la venta' . ' ' . $n_venta . ' ha sido ')
            ->htmlTemplate('pedidos/estado_pedido_cliente.html.twig') // Especifica la plantilla Twig para el cuerpo HTML
            ->context([
                'qr' => $domain . $host . '/public/qr_codes/' . $qrFilename,
                'n_venta' => $n_venta,
                'nombre' => $nombre_cliente,
                'metodo_pago' => $metodo_pago,
                'detalle' => $data,
                'subtotal' => $total_Subtotal,
                'impuestos' => $total_iva,
                'costo_envio' => $total_costo_envio,
                'direccion_cliente' => $direccion_cliente,
                'total' => $total_final,
                'estado_pago' => $estado_pago
            ]);

        $this->mailer->send($eml);


        return $this->errorsInterface->succes_message('Pedidos actualizados exitosamente', 'venta', $n_venta);
    }

    private function deletecarrito(EntityManagerInterface $entityManager, Carrito $carrito): void
    {

        $entityManager->remove($carrito);
        $entityManager->flush();
    }


    #[Route('/checkout2/{id}', name: 'checkout2', methods: ['GET'])]
    #[OA\Tag(name: 'Pagos')]
    public function chckout2($id,EntityManagerInterface $entityManager){
        $rest='';
        if (!empty($id)) {
            $generales = $entityManager->getRepository(GeneralesApp::class);
            $auth = $generales->getLoginPTP();
            $referencia = $id;

            $apiUrl = $generales->data_url()."/session/" . $referencia;
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array_merge($auth)));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $rest=$response;
            if (curl_errno($ch)) {
              return  $this->errorsInterface->error_message('Error cURL: ' . curl_error($ch), $httpCode);
            }

            if ($httpCode!=200){
                $this->errorsInterface->error_message('Respuesta cURL '.$httpCode.' : ' . $response, $httpCode);
            }

        }

        curl_close($ch);

        $data= json_decode($rest);

        
        return $this->json($data);

    }


    #[Route('/reversar_pago/{id}', name: 'app_reversar_pago', methods: ['POST'])]
    #[OA\Tag(name: 'Pagos')]
    public function reverse(EntityManagerInterface $entityManager, $id = null): Response
    {
        if (!$id) {
            return $this->errorsInterface->error_message('Falta el parámetro de ID.', Response::HTTP_BAD_REQUEST);
        }

        $pedidos = $entityManager->getRepository(Pedidos::class)->findBy([
            'metodo_pago' => 2,
            'estado' => 'APPROVED',
            'referencia_interna' => $id,
        ]);

        if (!$pedidos || count($pedidos) === 0) {
            return $this->errorsInterface->error_message('No se encontró ningún pedido aprobado con esa referencia.', Response::HTTP_NOT_FOUND);
        }

        $generales = $entityManager->getRepository(GeneralesApp::class);
        $auth = $generales->getLoginPTP();
        $apiUrl = $generales->data_url() . '/reverse';

        $errores = [];
        $reembolsosExitosos = 0;

        foreach ($pedidos as $pedido) {
            $refInterna = $pedido->getReferenciaInterna();

            if (!$refInterna) {
                continue;
            }

            $payload = [
                'auth' => $auth['auth'],
                'internalReference' => (int) $refInterna,
            ];

            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                ],
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                $errores[] = "Error cURL en pedido ID {$pedido->getId()}: $curlError";
                continue;
            }

            if ($httpCode !== 200) {
                $errores[] = "HTTP $httpCode en pedido ID {$pedido->getId()}: $response";
                continue;
            }

            $pedido->setEstado('REIMBURSED');
            $entityManager->persist($pedido);
            $reembolsosExitosos++;
        }

        if ($reembolsosExitosos > 0) {
            $entityManager->flush();
        }

        if (!empty($errores)) {
            return $this->errorsInterface->error_message(
                'Algunos reembolsos fallaron: ' . implode(' | ', $errores),
                Response::HTTP_PARTIAL_CONTENT
            );
        }

        if ($reembolsosExitosos === 0) {
            return $this->errorsInterface->error_message('No se pudo procesar ningún reembolso.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->errorsInterface->succes_message("Se procesaron $reembolsosExitosos reembolso(s) correctamente.");
    }
   
/*

    #[Route('/agregar_tarjeta/{id}', name: 'pago-tarjeta', methods: ['GET','OPTIONS'])]
    public function crearToken($id,EntityManagerInterface $entityManager){
        $rest='';
        if (!empty($id)) {
            $generales = $entityManager->getRepository(GeneralesApp::class);
            $auth = $generales->getLoginPTP();
            //Recibes el numero de pedido, obtienes el numero de referencia y se arma la URL
            $referencia = $id;  $fecha = date("d-m-Y");

            $generales = $entityManager->getRepository(GeneralesApp::class);
            $auth = $generales->getLoginPTP();
            $front_url= $generales->findOneBy([['nombre'=>'front','atributoGeneral'=>'Url']]);
    
            $apiUrl = $generales->data_url()
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array_merge($auth,$parametros)));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $rest=$response;
            if (curl_errno($ch)) {
                $rest = ['response' => 'Error cURL: ' . curl_error($ch), 'code' => $httpCode];
            }

            if ($httpCode!=200){
                $rest= ['response'=>'Respuesta cURL '.$httpCode.' : ' . $response,'code'=>$httpCode];
            }else{
                //consulta correcta, se actualiza el estado del pedido, no siempre quiere decir que el pago se proceso con exito
                // Los estados de pagos solo pueden ser : APROBADO, RECHAZADO, PENDIENTE, CANCELADO, DEVUELTO
            }



        }


        return $this->json($rest);

    }


    #[Route('/pagar-token/{token}', name: 'pagar-token', methods: ['GET'])]
    public function pagarToken($token, EntityManagerInterface $entityManager){
        $rest='';$id=1231;
        if (!empty($id)) {
             $generales = $entityManager->getRepository(GeneralesApp::class);
            $auth = $generales->getLoginPTP();
            $front_url= $generales->findOneBy([['nombre'=>'front','atributoGeneral'=>'Url']]);
    
            $apiUrl = $generales->data_url()
            //$token="32c3162a68dc2429074c8eba0051588269dafec6de5faa3a92764873d0f61e3a";
            $parametros=$generales->pagarTarjetaGuardadas($referencia,"Prueba",19.45,date('c',strtotime($fecha."+ 10 minutes")),"http://localhost/shopbyback/checkout/".$referencia,"89.117.32.164",true,$token);
            $apiUrl = $this->getParameter('place_to_pay_url');
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array_merge($auth,$parametros)));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $rest=$response;
            if (curl_errno($ch)) {
                $rest = ['response' => 'Error cURL: ' . curl_error($ch), 'code' => $httpCode];
            }

            if (curl_errno($ch)) {
                $rest = ['response' => 'Error cURL: ' . curl_error($ch), 'code' => $httpCode];
            }

            if ($httpCode!=200){
                $rest= ['response'=>'Respuesta cURL '.$httpCode.' : ' . $response,'code'=>$httpCode];
            }

            $api_response = json_decode($rest);
            if (is_object($api_response)){
                if (isset($api_response->requestId)){
                    $id_respuesta= $api_response->requestId;
                }
                $status=$api_response->status;
                if ($status->status=='OK' && !empty($id_respuesta) ){
                    //se actualiza la tabla pedido con el numero de referencia
                }
            }

            curl_close($ch);
            return new RedirectResponse($api_response->processUrl);


        }


        return $this->json($rest);

    }

*/

}

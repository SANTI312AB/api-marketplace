<?php

namespace App\Controller;

use App\Entity\LogsApi;
use App\Entity\Productos;
use App\Form\WhatsAppType;
use App\Interfaces\ErrorsInterface;
use App\Service\WhatsAppApiService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use DateTime;

final class WhatsAppController extends AbstractController
{

    private $whatsAppApiService;
    private $request;  // Injecting RequestStack into the controller.
    private $em;

    private $errorsInterface;

    public function __construct(WhatsAppApiService $whatsAppApiService, RequestStack $request,EntityManagerInterface $em, ErrorsInterface $errorsInterface){
        $this->whatsAppApiService = $whatsAppApiService;  // Injecting WhatsAppApiService into the controller.
        $this->request = $request->getCurrentRequest();  // Injecting RequestStack into the controller.
        $this->em = $em;  // Injecting EntityManager into the controller.
        $this->errorsInterface = $errorsInterface;
    }
    #[Route('/whats_app/send_message', name: 'app_whats_app_send_message', methods:['POST'])]
    #[OA\Tag(name: 'WhatsApp')]
    #[OA\RequestBody(
        description: 'Enviar mensaje a nuemro de WhatsApp.',
        content: new Model(type: WhatsAppType::class)
    )]
    public function index(): Response
    {
        $form = $this->createForm(WhatsAppType::class);
        $form->handleRequest($this->request);
    
        if (!$form->isSubmitted()) {
            return $this->errorsInterface->error_message('Formulario no enviado', Response::HTTP_BAD_REQUEST);
        }
    
        if (!$form->isValid()) {
            return $this->errorsInterface->form_errors($form);
        }
    
        try {
            $data = $form->getData();
            $numero = $data['numero'];
            $mensaje = $data['mensaje'];

            // Enviar el mensaje usando el template "hello_world"
            $response = $this->whatsAppApiService->sendTemplateMessage(
                $numero, 
                'shopby', // Usar la nueva plantilla
                'es_MX', 
                [$mensaje] // Enviar el mensaje como parámetro para {{1}}
            );


            /*return $this->json([
                'status' => 'Enviado',
                'message_id' => $response['messages'][0]['id'] ?? null
            ]);*/

            return $this->errorsInterface->succes_message(
                'Mensaje enviado correctamente',
                Response::HTTP_OK,
                [
                    'status' => 'Enviado',
                    'message_id' => $response['messages'][0]['id'] ?? null
                ]
            );
            

    
        } catch (\InvalidArgumentException $e) {
            return $this->errorsInterface->error_message('Contenido inválido', Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return $this->errorsInterface->error_message('Error en WhatsApp', Response::HTTP_FAILED_DEPENDENCY);
        } catch (\Exception $e) {
            return $this->errorsInterface->error_message('Error interno', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    


    #[Route('/webhook/whatsapp', name: 'whatsapp_webhook', methods: ['GET', 'POST'])]
    #[OA\Tag(name: 'WhatsApp')]
    public function handleWebhook(): Response
    {
        $api_log = new LogsApi();
        $api_log->setAcctionLog('whatsapp_webhook');
        $api_log->setFechaLog(new DateTime());
        $api_log->setIp($this->request->getClientIp());
    
        try {
            if ($this->request->isMethod('GET')) {
                $hubChallenge = $this->request->query->get('hub_challenge');
                $hubVerifyToken = $this->request->query->get('hub_verify_token');
                $expectedToken = $this->whatsAppApiService->webhook_data(); // Usar parámetro de entorno
    
                $api_log->setMethod('GET');
             
                if ($hubVerifyToken === $expectedToken) {
                    $api_log->setMessage('Validación correcta de hook');
                    $api_log->setResponseLog(200);
                    $this->em->persist($api_log);
                    $this->em->flush();
                    return new Response($hubChallenge, 200);
                }
    
                $api_log->setMessage('Token inválido: ' . $hubVerifyToken);
                $api_log->setResponseLog(403);
                $this->em->persist($api_log);
                $this->em->flush();
                return new Response('Invalid verify token', 403);
            }
    
            if ($this->request->isMethod('POST')) {
                $data = json_decode($this->request->getContent(), true);
                $api_log->setMethod('POST');
                $api_log->setMessage(json_encode($data));
    
                $statusCode = 200;
                $message = 'Evento no manejado';
                
                if (isset($data['entry'][0]['changes'][0]['value']['messages'])) {
                    $messageData = $data['entry'][0]['changes'][0]['value']['messages'][0];
                    $phoneNumber = $messageData['from'];
                    
                    // Manejar diferentes tipos de mensajes
                    if (isset($messageData['text'])) {
                        $message = "Mensaje de texto de {$phoneNumber}: " . $messageData['text']['body'];
                    } elseif (isset($messageData['image'])) {
                        $message = "Imagen recibida de {$phoneNumber}";
                    } else {
                        $message = "Tipo de mensaje no soportado de {$phoneNumber}";
                    }
                    
                } elseif (isset($data['entry'][0]['changes'][0]['value']['statuses'])) {
                    $statusData = $data['entry'][0]['changes'][0]['value']['statuses'][0];
                    $messageId = $statusData['id'];
                    $status = $statusData['status'];
                    $error = $statusData['errors'][0]['code'] ?? 'Desconocido';
                    
                    $message = "Estado mensaje {$messageId}: {$status} (Error: {$error})";
                    $statusCode = $status === 'failed' ? 400 : 200;
                }
    
                $api_log->setMessage($message);
                $api_log->setResponseLog($statusCode);
                $this->em->persist($api_log);
                $this->em->flush();
    
                return new Response('OK', 200);
            }
    
            $api_log->setMethod('UNKNOWN');
            $api_log->setMessage('Método no permitido');
            $api_log->setResponseLog(405);
            $this->em->persist($api_log);
            $this->em->flush();
    
            return new Response('Method not allowed', 405);
    
        } catch (\Exception $e) {
            $api_log->setMessage('Error en webhook: ' . $e->getMessage());
            $api_log->setResponseLog(500);
            $this->em->persist($api_log);
            $this->em->flush();
            
            error_log("Error crítico en webhook: " . $e->getMessage());
            return new Response('Internal Server Error', 500);
        }
    }

     #[Route('/whats_app/syncProducts/{id}', name: 'add_products_whatspp', methods:['POST'])]
     #[OA\Tag(name: 'WhatsApp')]
     public function add_products($id=null): Response
     {
        
        if(!$id){
            return $this->errorsInterface->error_message('No se ha enviado el id del producto', Response::HTTP_BAD_REQUEST);
        }

        $producto= $this->em->getRepository(Productos::class)->findOneBy(['id'=>$id]);
        if(!$producto){
            return $this->errorsInterface->error_message('No se ha encontrado el producto', Response::HTTP_NOT_FOUND);
        }
        
       try {

        $nombre = $producto->getNombreProducto();
        $descripcion = $producto->getDescripcionCortaProducto();
        $slug= $producto->getSlugProducto();
        $precio = $producto->getPrecioNormalProducto();
        $precio_rebajado = $producto->getPrecioRebajadoProducto();
        $precioAUsar = ($precio_rebajado !== null && $precio_rebajado !== 0) ? $precio_rebajado : $precio;
        $imagen = 'https://www.shopify/image/image.jpg';
        $stock= $producto->getCantidadProducto();
        $productData = [
            'id'=>$slug,
            "name" => $nombre,
            "description" => $descripcion,
            "price" => $precioAUsar,
            "currency" =>'USD',
            "image_url" =>$imagen,
            "stock" => $stock
        ];
    

        $response = $this->whatsAppApiService->add_product($productData);

        return $this->errorsInterface->succes_message(
            'Producto sincronizado correctamente',
            null,
            [
            'status' => 'success',
            'product_id' => $response['id'] ?? null,
            'whatsapp_response' => $response
            ]
        );

      } catch (\InvalidArgumentException $e) {
         return $this->errorsInterface->error_message($e->getMessage(), Response::HTTP_BAD_REQUEST);
      } catch (\RuntimeException $e) {
         return $this->errorsInterface->error_message('Error en WhatsApp API', Response::HTTP_FAILED_DEPENDENCY);
      } catch (\Exception $e) {
         return $this->errorsInterface->error_message('Error interno', Response::HTTP_INTERNAL_SERVER_ERROR);
     }
   }
}

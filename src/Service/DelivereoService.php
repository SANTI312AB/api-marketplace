<?php 

namespace App\Service;

use App\Entity\DetallePedido;
use App\Entity\GeneralesApp;
use App\Entity\MetodosEnvio;
use App\Entity\Pedidos;
use App\Entity\Servientrega;
use App\Entity\Tiendas;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DelivereoService
{

    private $em;
    private $params;
    private $logger;

    private $jwtToken;
    private $tokenExpiry;

    private array $configServientrega = [];
     
    public function __construct( EntityManagerInterface $em,ParameterBagInterface $params, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->params = $params;
        $this->logger = $logger;
        $generales= $this->em->getRepository(GeneralesApp::class)->findBy(['nombre'=>'delivereo']);
        foreach ($generales as $parametro) {
        $this->configServientrega[$parametro->getAtributoGeneral()] = $parametro->getValorGeneral();
        }
    }


    public function url_delivereo(){
           $url =$this->configServientrega['Url'];
           return $url;
    }



    public function createBooking_delivereo($pedido)
    {
        // Buscar el pedido con el número proporcionado y estado 'APPROVED'
        $row = $this->em->getRepository(Pedidos::class)->findOneBy([
            'numero_pedido' => $pedido,
            'estado' => 'APPROVED'
        ]);

        // Obtener el método de envío con ID 3 (Delivereo)
        $metodo = $this->em->getRepository(MetodosEnvio::class)->findOneBy(['id' => 3]);

        if (!$row) {
            throw new Exception('Pedido no encontrado', Response::HTTP_NOT_FOUND);
        }

        if ($row->getGuiaContador() == 1) {
            throw new Exception('Las guías ya fueron generadas.', Response::HTTP_CONFLICT);
        }

        // Limpiar el nombre de la ciudad eliminando cualquier contenido entre paréntesis
        $ciudad = preg_replace('/\s*\(.*?\)\s*/', '', $row->getCustomerCity());

        // Obtener detalles del pedido
        $detalle = $this->em->getRepository(DetallePedido::class)->findOneBy(['pedido' => $row]);
        if (!$detalle) {
            throw new Exception('Detalle del pedido no encontrado.', Response::HTTP_BAD_REQUEST);
        }
        $id_tienda = $detalle->getTienda()->getId();
        $tienda = $this->em->getRepository(Tiendas::class)->findOneBy(['id' => $id_tienda]);

        // Preparar los datos para la solicitud a Delivereo
        $data = [
            "addresses" => [
                [
                    "address" => $row->getDireccionPrincipal(),
                    "addressCrossingStreet" => $row->getDireccionPrincipal(),
                    "addressMainStreet" => $row->getDireccionSecundaria(),
                    "addressOrder" => 1,
                    "countryCode" => "EC",
                    "fullAddress" => $row->getDireccionPrincipal() . ' ' . $row->getDireccionSecundaria(),
                    "phone" => $row->getCelularCustomer(),
                    "reference" => $row->getReferenciaPedido(),
                    "senderRecipientName" => $row->getCustomer()
                ]
            ],
            "bookingBusinessUserEmail" =>$this->configServientrega['Email'],
            "categoryType" => "MEDIUM",
            "cityType" => $ciudad,
            "description" => "Productos Shopby",
            "itemsPrice" => $row->getTotalFinal(),
            "lang" => "es",
            "order" => [
                'orderGuid' => $row->getNumeroPedido(),
                "orderItems" => [],
                "orderIva" => $row->getIva(),
                "orderSubTotal" => $row->getSubtotal(),
                "orderTotal" => $row->getTotalFinal(),
                "paymentMode" => "CREDIT_CARD"
            ],
            "points" => [
                [
                    "address" => $detalle->getDireccionRemite(),
                    "phone" => $detalle->getCelular(),
                    "pointLatitude" => $detalle->getLatitud(),
                    "pointLongitude" => $detalle->getLongitud(),
                    "pointOrder" => 1,
                    "reference" => $detalle->getReferencia(),
                    "senderRecipientName" => $detalle->getNombre()
                ],
                [
                    "address" => $row->getDireccionPrincipal() . ' ' . $row->getDireccionSecundaria(),
                    "phone" => $row->getCelularCustomer(),
                    "pointLatitude" => $row->getLatitud(),
                    "pointLongitude" => $row->getLongitud(),
                    "pointOrder" => 2,
                    "reference" => $row->getReferenciaPedido(),
                    "senderRecipientName" => $row->getCustomer()
                ]
            ],
            "shopName" => "Shopby"
        ];

        // Construir la URL completa para la solicitud de creación de booking
        $url = $this->configServientrega['Url'] . '/api/private/business-bookings/create';

        // Obtener el token JWT
        try {
            $token = $this->getJwtToken();
        } catch (Exception $e) {
            $this->logger->error('Error al obtener el token JWT:', ['message' => $e->getMessage()]);
            throw new Exception('Error al obtener el token JWT: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Inicializar cURL para la solicitud POST
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true, // Habilitar verificación SSL en producción
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode(array_merge($data)),
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Authorization: Bearer " . $token,
                "Content-Type: application/json"
            ]
        ]);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);

        // Registrar la respuesta y el código HTTP
        $this->logger->info('Respuesta de Delivereo:', ['response' => $response, 'http_code' => $httpcode]);

        curl_close($curl);

        if ($err) {
            $this->logger->error('Error de cURL al crear booking:', ['error' => $err]);
            throw new Exception('Error de cURL al crear booking: ' . $err, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $mp = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('Error al decodificar JSON de la respuesta de Delivereo:', [
                'error' => json_last_error_msg(),
                'response' => $response
            ]);
            throw new Exception('Error decoding JSON response: ' . json_last_error_msg() . ' - Raw response: ' . $response, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($httpcode == 200) {
            // Verificar si ya existe una guía para este pedido


            if (isset($mp['bookingId'])) {
                // Actualizar el estado del pedido
                $row->setGuiaContador(1);
                $this->em->persist($row);

                // Crear una nueva entidad de Servientrega
                $registroEntidad = new Servientrega();
                $registroEntidad->setPedido($row);
                $registroEntidad->setNPedido($row->getNumeroPedido());
                $registroEntidad->setFechaPedido($row->getFechaPedido());
                $registroEntidad->setIdCiudadEnvio($row->getIdDireccion());
                $registroEntidad->setCiudadEnvio($row->getCustomerCity());
                $registroEntidad->setDireccionPrincipal($row->getDireccionPrincipal());
                $registroEntidad->setDireccionSecundaria($row->getDireccionSecundaria());
                $registroEntidad->setUbicacionReferencia($row->getReferenciaPedido());
                $registroEntidad->setNombre($row->getLogin()->getUsuarios()->getNombre());
                $registroEntidad->setApellido($row->getLogin()->getUsuarios()->getApellido());
                $registroEntidad->setDni($row->getDniCustomer());
                $registroEntidad->setCeular($row->getCelularCustomer());
                $registroEntidad->setCiudadRemite($detalle->getCiudadRemite());
                $registroEntidad->setDireccionRemite($detalle->getDireccionRemite());
                $registroEntidad->setNombreVendedor($detalle->getNombre());
                $registroEntidad->setDniVendedor($detalle->getTienda()->getLogin()->getUsuarios()->getDni());
                $registroEntidad->setCelularVendedor($detalle->getCelular());
                $registroEntidad->setCodigoServientrega($mp['bookingId']);
                $registroEntidad->setMetodoEnvio($metodo);
                $registroEntidad->setTienda($tienda);

                $this->em->persist($registroEntidad);
                $this->em->flush();

                $this->logger->info('Booking creado exitosamente:', ['bookingId' => $mp['bookingId'], 'pedido' => $pedido]);
            } else {
                $this->logger->error('Respuesta de Delivereo sin bookingId:', ['response' => $mp]);
                throw new Exception('Invalid response from Delivereo API: ' . json_encode($mp), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {
            $this->logger->error('Respuesta no exitosa de Delivereo:', ['http_code' => $httpcode, 'response' => $mp]);
            throw new Exception('Invalid response from Delivereo API: ' . json_encode($mp), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($mp, $httpcode);
    }

    /**
     * Función privada para obtener el token JWT. Implementa cacheo para optimizar las solicitudes.
     *
     * @return string
     * @throws Exception
     */
    public function getJwtToken(): string
    {
        // Verificar si ya existe un token válido en cache
        if ($this->jwtToken && $this->tokenExpiry > new DateTime()) {
            return $this->jwtToken;
        }

        // Obtener un nuevo token
        $token = $this->login();

        // Asumir que el token expira en 1 hora. Ajusta según la respuesta real de la API.
        $this->tokenExpiry = new DateTime('+1 hour');
        $this->jwtToken = $token;

        return $this->jwtToken;
    }

    /**
     * Función privada para autenticar y obtener el token JWT desde la API de Delivereo.
     *
     * @return string
     * @throws Exception
     */
    private function login(): string
    {
        $data = [
            "apiKey" => $this->configServientrega['SecretKey'],
            "email" => $this->configServientrega['Email'],
            "lang" => "en",
            "ruc" => "-"
        ];

        $cliente = $this->configServientrega['Login'];
        $password = $this->configServientrega['Password'];
        $url =  $this->configServientrega['Url'] . '/api/protected/login/business';

        // Inicializar cURL para la solicitud de login
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true, // Habilitar verificación SSL en producción
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Content-Type: application/json"
            ],
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => "$cliente:$password",
        ]);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new Exception('cURL Error: ' . $err);
        }

        $mp = json_decode($response);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error decoding JSON response: ' . $response);
        }

        if (!isset($mp->jwtToken)) {
            throw new Exception('Invalid response from Delivereo API: ' . $response);
        }

        return $mp->jwtToken;
    }



     public function calculate_booking($direccion_p,$direccion_s,$ciudad,$latitud,$longitud,$latitud_user,$longitud_user)
    {
        

        $url = $this->configServientrega['Url'] . '/api/private/business-bookings/calculate';

        $data = [
            "addresses" => [
                [
                    "addressCrossingStreet" => $direccion_p,
                    "addressMainStreet" => $direccion_s,
                    "addressOrder" => 1,
                    "countryCode" => "EC",
                    "fullAddress" => $direccion_p.','.$direccion_s
                ]
            ],
            "categoryType" => "MEDIUM",
            "cityType" => $ciudad,
            "lang" => "es",
            "points" => [

                [
                    "pointLatitude" => $latitud,
                    "pointLongitude" => $longitud,
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
            $token = $this->getJwtToken();
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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

        return new JsonResponse(['error' => $err], $httpcode);
      } else {
        $mp= json_decode($response);
      }

      if($httpcode == 200 && $mp !== null && ( $mp->totalAmount !== null )){
        $costo_envio= $mp->totalAmount;
      }else{
        $costo_envio=0;
      }


    return $costo_envio;
  }


}
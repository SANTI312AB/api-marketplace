<?php

namespace App\Controller;

use App\Entity\Servientrega;
use App\Interfaces\ErrorsInterface;
use App\Service\DelivereoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

class DelivereoController extends AbstractController
{
    private $jwtToken;
    private $tokenExpiry;

    private $errorInterface;

    private $delivereoService;
    

    public function __construct(ErrorsInterface $errorInterface,DelivereoService $delivereoService)
    {
        $this->errorInterface = $errorInterface;
        $this->delivereoService= $delivereoService;
    }
    

    #[Route('/delivereo/calculate-booking', name: 'delivereo-calculate-booking', methods: ['GET'])]
    #[OA\Tag(name: 'Delivereo')]
    #[OA\Response(
        response: 200,
        description: 'Calculo de costo de envio Delivereo'
    )]
    public function calculate_booking(): Response
    {


        $url = $this->delivereoService->url_delivereo() . '/api/private/business-bookings/calculate';

        $data = [
            "addresses" => [
                [
                    "addressCrossingStreet" => 'Hurtado de Mendoza',
                    "addressMainStreet" => 'Quito 170148, Ecuador',
                    "addressOrder" => 1,
                    "countryCode" => "EC",
                    "fullAddress" => 'Hurtado de Mendoza &, Quito 170148, Ecuador'
                ]
            ],
            "categoryType" => "MEDIUM",//obligatorio
            "cityType" => 'QUITO',//obligatorio en la ciudades disponibles
            "lang" => "es",
            "points" => [

                [
                    "pointLatitude" => -0.25152880642576775,
                    "pointLongitude" => -78.52260244866501,
                    "pointOrder" => 1
                ],
                [
                    "pointLatitude" => -0.18399286517173408,
                    "pointLongitude" => -78.49230321992282,
                    "pointOrder" => 2
                ]
            ]
        ];


        try {
            $token = $this->delivereoService->getJwtToken();
        } catch (\Exception $e) {
            return $this->errorInterface->error_message(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data), // The array_merge was redundant
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Authorization: Bearer " . $token,
                "Content-Type: application/json"
            ],
            // --- ADD THESE LINES ---
            CURLOPT_CONNECTTIMEOUT => 10, // Wait 10 seconds to connect
            CURLOPT_TIMEOUT => 30,        // Wait a maximum of 30 seconds for the entire request
        ]);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);


        curl_close($curl);


        $decodedResponse = json_decode($response);
        return $this->json($decodedResponse);
    }


    #[Route('/delivereo/view-booking/{bookingId}', name:'view-delivereo-booking',methods:['POST'])]
    #[OA\Tag(name: 'Delivereo')]
    #[OA\Response(
        response: 200,
        description: 'Ver detalles  de entrega Delivereo'
    )]
    public function view_booking($bookingId,EntityManagerInterface $entityManager): Response
    {
        $guias= $entityManager->getRepository(Servientrega::class)->findOneBy(['codigo_servientrega'=>$bookingId,'metodo_envio'=>3,'anulado'=>false]);

        if (!$guias) {
            return $this->errorInterface->error_message('Guía no encontrada', Response::HTTP_NOT_FOUND);
        }
        $data=[
            "bookingId"=> $guias->getCodigoServientrega(),
            "lang"=>"es"
        ]; 

        $url = $this->delivereoService->url_delivereo().'/api/private/business-bookings/detail-full';

        try {
            $token = $this->delivereoService->getJwtToken();
        } catch (\Exception $e) {
            return $this->errorInterface->error_message(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode(array_merge($data)),
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Authorization: Bearer ".$token,
                "Content-Type: application/json"
            ]
        ]);
    
        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
    
        curl_close($curl);
    
        if ($err) {
            return $this->errorInterface->error_message(
                'Error de cURL: ' . $err,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );

        } else {
            $mp = json_decode($response);
        }
      
        return $this->json($mp)->setStatusCode($httpcode);

    }

}

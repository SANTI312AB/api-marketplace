<?php

namespace App\Controller;

use App\Entity\GeneralesApp;
use App\Interfaces\ErrorsInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

class HubspotController extends AbstractController
{
    private $errorInterface;

    private $em;

    private array $configApp = [];

    public function __construct(ErrorsInterface $errorInterface,EntityManagerInterface $em)
    {
        $this->errorInterface = $errorInterface;
        $this->em= $em;
        $generales= $this->em->getRepository(GeneralesApp::class)->findBy(['nombre'=>'hubspot']);
        foreach ($generales as $parametro) {
        $this->configApp[$parametro->getAtributoGeneral()] = $parametro->getValorGeneral();
        }
    }

    #[Route('/hubspot/list', name:'hubspot_list', methods:['GET'])]
    #[OA\Tag(name: 'Hubspot')]
    #[OA\Response(
        response: 200,
        description: 'Lista de contactos Hubpost'
    )]
    public function hubpost_list(): Response
    {
       
        $apiUrl=$this->configApp['Url'].'/contacts/v1/lists/all/contacts/all';
        
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Accept: application/json",
                "Authorization: Bearer ".$this->configApp['SecretKey'],
                "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
         
         
        if (curl_errno($ch)) {
            return $this->errorInterface->error_message(
                'Error cURL: ' . curl_error($ch),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                null,
                ['code' => $httpCode]
            );
         }
 
         if ($httpCode!=200){
            return $this->errorInterface->error_message(
                'Respuesta cURL '.$httpCode.' : ' . $response,
                Response::HTTP_INTERNAL_SERVER_ERROR,
                null,
                ['code' => $httpCode]
            );
         }
 
         $rest=$response;
         $api_response = json_decode($rest);
        
         return $this->json($api_response); 
    }

}

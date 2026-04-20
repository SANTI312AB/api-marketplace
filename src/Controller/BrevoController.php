<?php

namespace App\Controller;

use App\Entity\GeneralesApp;
use App\Interfaces\ErrorsInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

class BrevoController extends AbstractController
{
    private $errorsInterface;
    private $em;
    private array $configApp = [];
    public function __construct(ErrorsInterface $errorsInterface,EntityManagerInterface $em)
    {
        $this->errorsInterface = $errorsInterface;
        $this->em=$em;
        $generales= $this->em->getRepository(GeneralesApp::class)->findBy(['nombre'=>'brevo']);
        foreach ($generales as $parametro) {
        $this->configApp[$parametro->getAtributoGeneral()] = $parametro->getValorGeneral();
        }
    }

    #[Route('/brevo/contact_list', name: 'lista_contactos_brevo', methods:["GET"])]
    #[OA\Tag(name: 'Brevo')]
    #[OA\Response(
        response: 200,
        description: 'Lista de contactos Brevo'
    )]
    public function index(): Response
    {
        $apiUrl=$this->configApp['Url'].'/contacts';

         
        
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Accept: application/json",
                "api-key: ".$this->configApp['SecretKey'],
                "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
         
         
        if (curl_errno($ch)) {
            
            
            return $this->errorsInterface->error_message(
                'Error cURL: ' . curl_error($ch),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                 'code',
                 $httpCode
            );
         }
 
         if ($httpCode!=200){
            return $this->errorsInterface->error_message(
                'Respuesta cURL '.$httpCode.' : ' . $response,
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'code',
                $httpCode
            );
         }
 
         $rest=$response;
         $api_response = json_decode($rest);
        
         return $this->json($api_response); 
    }

}

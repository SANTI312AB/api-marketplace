<?php

namespace App\Controller;

use App\Entity\Ciudades;
use App\Entity\Provincias;
use App\Form\CiudadesType;
use App\Form\ProvinciasType;
use App\Repository\CiudadesRepository;
use App\Repository\ProvinciasRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;

#[Route('/provincias')]
#[OA\Tag(name: 'Provincias')]
class ProvinciasController extends AbstractController
{
    #[Route('/', name: 'app_provincias_index', methods: ['GET'])]
    public function index(ProvinciasRepository $provinciasRepository): Response
    {

        $provincias= $provinciasRepository->findAll();
        $provinciasArray = [];
        foreach ($provincias as $provincia) {

            $ciudadesArray = [];
            
            foreach ($provincia-> getCiudades() as $ciudad) {
                $ciudadesArray[] = [
                    'id' => $ciudad->getId(),
                    'nombre'=>$ciudad->getCiudad()                             
                ];
            }
            $provinciasArray[] = [
                'id'=> $provincia->getId(),
                'nombre'=>$provincia->getProvincia(),
                'ciudades'=>$ciudadesArray

            ];
        }
    
        return $this->json($provinciasArray);
    }

}

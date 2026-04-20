<?php

namespace App\Controller;

use App\Entity\Atributos;
use App\Entity\Terminos;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

class AtributosController extends AbstractController
{
    #[Route('/atributos/all', name: 'app_atributos',methods:['GET'])]
    #[OA\Tag(name: 'Atributos')]
    #[OA\Response(
        response: 200,
        description: 'Lista de Atributos con sus terminos'
    )]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $atributos = $entityManager->getRepository(Atributos::class)->findAll();

        $data=[];
        foreach ($atributos as $atributo){
            $terminos_data=[];
            foreach ($atributo->getTerminos() as $termino){
                $terminos_data[]=[
                    'id'=>$termino->getId(),
                    'nombre'=>$termino->getNombre(),
                    'codigo'=>$termino->getCodigo(),
                ];
            }
            $data[]=[
                'id'=>$atributo->getId(),
                'nombre'=>$atributo->getNombre(),
                'terminos'=>$terminos_data
            ];
        }
        
        return $this->json($data);
    }



    #[Route('/atributos/filter_terminos/{id}', name: 'filtro_atributos_terminos',methods:['GET'])]
    #[OA\Tag(name: 'Atributos')]
    #[OA\Response(
        response: 200,
        description: 'Filtro de terminos por id'
    )]
    public function filter_terminos($id,EntityManagerInterface $entityManager): Response
    {
        $terminos= $entityManager->getRepository(Terminos::class)->findOneBy(['id'=>$id]);

      
            $data=[
                "id"=>$terminos->getAtributos()->getId(),
                "nombre"=> $terminos->getAtributos()->getNombre(),
                "id_termino"=>$terminos->getId(),
                "nombre_termino"=>$terminos->getNombre(),
            ];
        

        return $this->json($data);
    }
}

<?php

namespace App\Controller;

use App\Entity\GeneralesApp;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InicioController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em){
        $this->em= $em;
    }
    
    #[Route('/', name: 'app_inicio', methods:['GET'])]
    public function index(): Response
    {
        /*$data_url= $this-em->getRepository(GeneralesApp::class)->findOneBy(['nombre'=>'front','atributoGeneral'=>'Url']);
        return $this->redirect($data_url->getValorGeneral().'/auth/login'); */
        return $this->json('Welcom');
    }
}

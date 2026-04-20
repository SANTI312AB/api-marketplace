<?php

namespace App\Controller;

use App\Entity\LogsFront;
use App\Form\LogsFrontType;
use App\Interfaces\ErrorsInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;

final class LogsFrontController extends AbstractController
{
    private $em;

    private $request;

    private $errorsInterface;


    public function __construct(EntityManagerInterface $em, RequestStack $request, ErrorsInterface $errorsInterface)
    {
        $this->em = $em;
        $this->request = $request->getCurrentRequest();
        $this->errorsInterface = $errorsInterface;
    }

    #[Route('/logs/front', name: 'app_logs_front',methods:['GET'])]
    #[OA\Tag(name: 'LogsFront-End')]
    #[OA\Response(
        response: 200,
        description: 'Lista de logs del front-end.'
    )]
    public function index(): Response
    {
        $logs= $this->em->getRepository(LogsFront::class)->findAll();

        return $this->json($logs);
    }


    #[Route('/logs/front', name: 'app_add_log_front_end', methods:['POST'])]
    #[OA\Tag(name: 'LogsFront-End')]
    #[OA\RequestBody(
        description: 'Añadir logs del front-end',
        content: new Model(type: LogsFrontType::class)
    )]
    public function action(): Response
    {
        $log= new LogsFront();
        $form = $this->createForm(LogsFrontType::class, $log);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
             $this->em->persist($log);
             $this->em->flush();
             return $this->errorsInterface->succes_message('Log guardado.');
        }
        return $this->errorsInterface->form_errors($form);
    }

}

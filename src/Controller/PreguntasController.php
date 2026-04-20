<?php

namespace App\Controller;

use App\Entity\Preguntas;
use App\Entity\Productos;
use App\Entity\Respuestas;
use App\Entity\Tiendas;
use App\Form\PreguntasType;
use App\Form\RespuestasType;
use App\Interfaces\ErrorsInterface;
use App\Repository\PreguntasRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;

class PreguntasController extends AbstractController
{
    private $errorsInterface;
    public function __construct(ErrorsInterface $errorsInterface)
    {
        $this->errorsInterface = $errorsInterface;
    }

    #[Route('/preguntas/{id}', name: 'lista_preguntas', methods: ['GET'])]
    #[OA\Tag(name: 'Preguntas')]
    #[OA\Response(
        response: 200,
        description: 'Lista de preguntas con sus respuestas por producto'
    )]
    public function index( EntityManagerInterface $entityManager,Productos $id = null): Response
    {

        if($id == null){
           return $this->errorsInterface->error_message('Producto no encontrado', Response::HTTP_NOT_FOUND);
        }
        $preguntas = $entityManager->getRepository(Preguntas::class)->findBy(['producto' => $id], ['fecha' => 'DESC']);
        
        $preguntas_array = [];

         

        $tienda_producto = $id->getTienda()->getId();
        
        
        foreach ($preguntas as $pregunta) {

            
        
            $respuestaArray = [];
            foreach ($pregunta->getRespuestas() as $respuesta) {

                $tienda_respuesta= $respuesta->getLogin()->getTiendas()->getId();

                if ($tienda_respuesta == $tienda_producto){
                    $tipo='seller';
                }elseif($tienda_respuesta !== $tienda_producto){
                    $tipo='customer';
                }
                $respuestaArray[] = [
                    'id' => $respuesta->getId(),
                    'respuesta' => $respuesta->getRespuesta(),
                    'username' => $respuesta->getLogin()->getUsername(),
                    'tipo'=>$tipo,
                    'fecha' => $respuesta->getFecha() ?  $respuesta->getFecha()->format('Y-m-d H:i:s'):'' ,
                ];
            }
        
            // Ordenar las respuestas por fecha utilizando una función anónima
            usort($respuestaArray, function($a, $b) {
                return strtotime($a['fecha']) - strtotime($b['fecha']);
            });
        
            $preguntas_array[] = [
                'id' => $pregunta->getId(),
                'pregunta' => $pregunta->getPregunta(),
                'username' => $pregunta->getLogin()->getUsername(),
                'fecha' => $pregunta->getFecha(),
                'respuestas' => $respuestaArray
            ];
        }
        
        return $this->json($preguntas_array);
    }


    #[Route('/api/preguntas_vendedor', name: 'preguntas_vendedor',methods:['GET'])]
    #[OA\Tag(name: 'Preguntas')]
    #[OA\Response(
        response: 200,
        description: 'Lista de preguntas por vendedor'
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function pre_vendedor(EntityManagerInterface $entityManager,PreguntasRepository $preguntasRepository): Response
    { 
        $user = $this->getUser();
        $tienda= $entityManager->getRepository(Tiendas::class)->findOneBy(['login'=>$user]);
        $preguntas= $preguntasRepository->preguntas_vendedor($tienda);
        
        $preguntas_array = [];

        foreach ($preguntas as $pregunta) {

             $respuestaArray=[];
             foreach ($pregunta->getRespuestas() as $respuesta) {
                 $respuestaArray[] = [
                     'id' => $respuesta->getId(),
                     'respuesta' => $respuesta->getRespuesta(),
                     'username' => $respuesta->getLogin()->getUsername(),
                     'fecha' => $respuesta->getFecha(),
                 ];
             }

             

            $preguntas_array[] = [
                'id' => $pregunta->getId(),
                'pregunta' => $pregunta->getPregunta(),
                'username' => $pregunta->getLogin()->getUsername(),
                'fecha' => $pregunta->getFecha(),
                'respuestas' => $respuestaArray  
            ];
        }

        return $this->json($preguntas_array);
    }


    #[Route('/api/add_pregunta/{id}', name:'añadir_pregunta',methods:['POST'])]
    #[OA\Tag(name: 'Preguntas')]
    #[OA\RequestBody(
        description: 'Añade una pregunta a un producto',
        content: new  Model(type: PreguntasType::class)
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function add_pregunta(Request $request,EntityManagerInterface $entityManager, $id=null): Response
    {
        if ($id == null){
            return $this->errorsInterface->error_message('Parametro no especificado.', Response::HTTP_BAD_REQUEST);
        }
        $user = $this->getUser();

        $tienda= $entityManager->getRepository(Tiendas::class)->findOneBy(['login'=>$user]);

        $producto = $entityManager->getRepository( Productos::class)->find($id);


        if (!$producto) {
            return $this->errorsInterface->error_message('Producto no encontrado', Response::HTTP_NOT_FOUND);
        }

        if ($producto->getTienda() == $tienda) {
            return $this->errorsInterface->error_message('No se puede hacer comentarios en un producto de tu misma tienda', Response::HTTP_FORBIDDEN);
        }


        $pregunta = new Preguntas();

        $form= $this->createForm(PreguntasType::class,$pregunta);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pregunta->setProducto($producto);
            $pregunta->setLogin($user);
            $entityManager->persist($pregunta);
            $entityManager->flush();

            return $this->errorsInterface->succes_message('Pregunta añadida correctamente');
        }

        return $this->errorsInterface->form_errors($form);

    }


    #[Route('/api/edit_pregunta/{id}', name:'editar_pregunta',methods:['PUT'])]
    #[OA\Tag(name: 'Preguntas')]
    #[OA\RequestBody(
        description: 'Edita una pregunta a un producto',
        content: new  Model(type: PreguntasType::class)
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function adit_pregunta(Request $request,EntityManagerInterface $entityManager, $id = null): Response
    {
        if($id  == null){
            return $this->errorsInterface->error_message('Parametro no especificado.', Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();

        $pregunta= $entityManager->getRepository(Preguntas::class)->findOneBy(['id'=>$id, 'login'=>$user]);
        
        if (!$pregunta) {
            return $this->errorsInterface->error_message('Pregunta no encontrada', Response::HTTP_NOT_FOUND);
        }

        $form= $this->createForm(PreguntasType::class,$pregunta);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
    
            $entityManager->flush();

           return $this->errorsInterface->succes_message('Pregunta editada correctamente');
        }

         return $this->errorsInterface->form_errors($form);
    }


     #[Route('/api/add_respuesta/{id}', name: 'añadir_respuesta',methods:['POST'])]
     #[OA\Tag(name: 'Preguntas')]
     #[OA\RequestBody(
        description: 'Añade una respuesta a una pregunta de un producto',
        content: new  Model(type: RespuestasType::class)
     )]
     #[Security(name: 'Bearer')]
     #[IsGranted('IS_AUTHENTICATED_FULLY')]
     public function add_respuesta(Request $request,EntityManagerInterface $entityManager,Preguntas $id = null ): Response
     {
        if (!$id) {
            return $this->errorsInterface->error_message('Parametro no especificado.', Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        $respusta= new Respuestas();

        $form= $this->createForm(RespuestasType::class, $respusta);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $respusta->setPregunta($id);
            $respusta->setLogin($user);
            $entityManager->persist($respusta);
            $entityManager->flush();

            return $this->errorsInterface->succes_message('Respuesta añadida correctamente');
        }

          return $this->errorsInterface->form_errors($form);
     }


     #[Route('/api/edit_respuesta/{id}', name: 'editar_respuesta',methods:['PUT'])]
     #[OA\Tag(name: 'Preguntas')]
     #[OA\RequestBody(
        description: 'Edita una respuesta a una pregunta de un producto',
        content: new  Model(type: RespuestasType::class)
     )]
     #[Security(name: 'Bearer')]
     #[IsGranted('IS_AUTHENTICATED_FULLY')]
     public function adit_respuesta(Request $request,EntityManagerInterface $entityManager,$id=null): Response
     {
        if($id  == null){
            return $this->errorsInterface->error_message('Parametro no especificado.', Response::HTTP_BAD_REQUEST);
        }
        $user = $this->getUser();
        $respuesta= $entityManager->getRepository(Respuestas::class)->findOneBy(['id'=>$id, 'login'=>$user]);
         if (!$respuesta) {
            return $this->errorsInterface->error_message('Respuesta no encontrada', Response::HTTP_NOT_FOUND);
        }

        $form= $this->createForm(RespuestasType::class, $respuesta);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $entityManager->flush();

           return $this->errorsInterface->succes_message('Respuesta editada correctamente');
        }

        return $this->errorsInterface->form_errors($form);

     }

     #[Route('/api/delete_pregunta/{id}', name: 'delete_pregunta',methods:['DELETE'])]
     #[OA\Tag(name: 'Preguntas')]
     #[OA\Response(
         response: 200,
         description: 'Eliminar pregunta'
     )]
     #[Security(name: 'Bearer')]
     #[IsGranted('IS_AUTHENTICATED_FULLY')]
     public function delete_p(EntityManagerInterface $entityManager,$id=null): Response
     {
        if($id  == null){
            return $this->errorsInterface->error_message('Parametro no especificado.', Response::HTTP_BAD_REQUEST);
         }
        $user = $this->getUser();
        $pregunta= $entityManager->getRepository(Preguntas::class)->findOneBy(['id'=>$id, 'login'=>$user]);
        if (!$pregunta) {
           return $this->errorsInterface->error_message('Pregunta no encontrada', Response::HTTP_NOT_FOUND);
         }
        $entityManager->remove($pregunta);
        $entityManager->flush();

        return $this->errorsInterface->succes_message('Pregunta eliminada correctamente');

     }


     #[Route('/api/delete_respuesta/{id}', name: 'delete_respuesta',methods:['DELETE'])]
     #[OA\Tag(name: 'Preguntas')]
     #[OA\Response(
         response: 200,
         description: 'Eliminar una respuesta'
     )]
     #[Security(name: 'Bearer')]
     #[IsGranted('IS_AUTHENTICATED_FULLY')]
     public function delete_r(EntityManagerInterface $entityManager,$id=null): Response
     { 
        if($id  == null){
            return $this->errorsInterface->error_message('Parametro no especificado.', Response::HTTP_BAD_REQUEST);
         }
        $user = $this->getUser();
        $respuesta= $entityManager->getRepository(Respuestas::class)->findOneBy(['id'=>$id, 'login'=>$user]);
         if (!$respuesta) {
            return $this->errorsInterface->error_message('Respuesta no encontrada', Response::HTTP_NOT_FOUND);
         }
        $entityManager->remove($respuesta);
        $entityManager->flush();

        return $this->errorsInterface->succes_message('Respuesta eliminada correctamente');
     }


    }


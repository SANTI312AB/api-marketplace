<?php

namespace App\Controller;

use App\Entity\Hotspot;
use App\Entity\Login;
use App\Entity\Scenes;
use App\Entity\Tiendas;
use App\Entity\VirtualTour;
use App\Form\HotspotsType;
use App\Form\ScenesType;
use App\Form\VirtualTourType;
use App\Interfaces\ErrorsInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


Class VirtualTourController extends AbstractController
{
    private $em;
    private $request;

    private $router;
    private $errorsInterface;
    public function __construct(EntityManagerInterface $em, RequestStack  $request,UrlGeneratorInterface $router, ErrorsInterface $errorsInterface){
        $this->em = $em;  // Injecting EntityManager into the controller.
        $this->request = $request->getCurrentRequest();   // Injecting RequestStack into the controller.
        $this->router = $router;  // Injecting UrlGeneratorInterface into the controller.
        $this->errorsInterface = $errorsInterface;
    }

    #[Route('/virtual/tour/{tienda}', name: 'app_virtual_tour', methods:['GET'])]
    #[OA\Tag(name: 'VirtualTour')]
    #[OA\Response(
        response: 200,
        description: 'Lista de tours virtuales de una tienda por slug.',
    )]
    public function tour($tienda=null): Response
    {
        if (!$tienda) {
            return $this->errorsInterface->error_message('No hay parámetro.', Response::HTTP_NOT_FOUND);
        }

        $t= $this->em->getRepository(Tiendas::class)->findBy(['slug'=>$tienda]);
        
        if (!$t) {
            return $this->errorsInterface->error_message('No se encontro la tienda.', Response::HTTP_NOT_FOUND);
        }

        $virtualTour= $this->em->getRepository(VirtualTour::class)->findBy(['tienda'=>$t]);

        if (!$virtualTour) {
            return $this->errorsInterface->error_message('Virtual Tour no encontrados', Response::HTTP_NOT_FOUND);
        }

        $baseUrl = $this->request->getSchemeAndHttpHost() . $this->request->getBasePath();
        $tourArray=[];

        foreach ($virtualTour as $tour){
            $scena=null;

            foreach ($tour->getScenes() as $data){
                $scena= $data->getImagePath();
                break;
            }

            $tourArray []= [
                'id' => $tour->getId(),
                'tienda'=>$tour->getTienda()? $tour->getTienda()->getNombreTienda():'',
                'nombre' => $tour->getNombre(),
                'scena'=>$baseUrl . '/public/virtual_scenes/' .$scena
            ];  
        }

        return $this->json($tourArray);  // Returning JSON response with virtual tour data.
    }

    #[Route('/virtual/tour/scenes/{virtualTour}', name: 'app_tour_scenes', methods: ['GET'])]
    #[OA\Tag(name: 'VirtualTour')]
    public function scenes(?VirtualTour $virtualTour): Response
    {
        if (!$virtualTour) {
            return $this->errorsInterface->error_message('Recurso no encontrado', Response::HTTP_NOT_FOUND);
        }
    
        $baseUrl = $this->request->getSchemeAndHttpHost() . $this->request->getBasePath();
    
        $tourData = [
            'id'     => $virtualTour->getId(),
            'name'   => $virtualTour->getNombre(),
            'scenes' => []
        ];
    
        foreach ($virtualTour->getScenes() as $scene) {
            $sceneData = [
                'id'        => $scene->getId(),
                'name'      => $scene->getNombre(),
                'imagePath' => $baseUrl . '/public/virtual_scenes/' . $scene->getImagePath(),
                'initialView' => [
                    'yaw'   => 0,
                    'pitch' => 0,
                    'fov'   => 0,
                ],
                'hotspots' => []
            ];
    
            foreach ($scene->getHotspots() as $hotspot) {
                $sceneData['hotspots'][] = [
                    'yaw'   => $hotspot->getYaw(),
                    'pitch' => $hotspot->getPitch(),
                    'type'  => $hotspot->getType(),
                    'text'  => $hotspot->getText(),
                    'url'   => $hotspot->getUrl(), // puede ser interno o externo
                    'slug_producto' => $hotspot->getSlugProducto()
                ];
            }
    
            $tourData['scenes'][] = $sceneData;
        }
    
        return $this->json($tourData);
    }
    

    #[Route('/api/my_virtualTour', name: 'app_my_virtualTour',methods:['GET'])]
    #[OA\Tag(name: 'VirtualTour')]
    #[OA\Response(
        response: 200,
        description: 'Lista de tours virtuales por usuario.',
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function get_my_virtualTour(): Response
    {
        $user= $this->getUser();
        $tienda = $this->em->getRepository(Tiendas::class)->findOneBy(['login' => $user]);
        $virtualTour= $this->em->getRepository(VirtualTour::class)->findBy(['tienda'=>$tienda]);

        if (!$virtualTour) {
            return $this->errorsInterface->error_message('Virtual Tour no encontrados', Response::HTTP_NOT_FOUND);
        }
        $tourArray=[];

        foreach ($virtualTour as $tour){
            $tourArray []= [
                'id' => $tour->getId(),
                'nombre' => $tour->getNombre()
         
            ];  
        }

        return $this->json($tourArray);  // Returning JSON response with virtual tour data.
    }

    #[Route('/api/add_virtualTour', name: 'app_add_virtualTour',methods:['POST'])]
    #[OA\Tag(name: 'VirtualTour')]
    #[OA\RequestBody(
        description: 'Crea un tour virtual de una tienda.',
        content: new Model(type: VirtualTourType::class)
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function action(): Response
    {

        $user= $this->getUser();
        $tienda = $this->em->getRepository(Tiendas::class)->findOneBy(['login' => $user]);

        if (!$tienda) {
            return $this->errorsInterface->error_message('Tienda no encontrada', Response::HTTP_NOT_FOUND);
        }
        $virtualTour = new VirtualTour();

        $form = $this->createForm(VirtualTourType::class,$virtualTour);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
            $virtualTour->setTienda($tienda);
            $this->em->persist($virtualTour);
            $this->em->flush();

             return $this->errorsInterface->succes_message('Guardado', Response::HTTP_OK);

        }

         return $this->errorsInterface->form_errors($form);
     
    }

    #[Route('/api/edit_virtualTour/{id}', name: 'app_edit_virtualTour',methods:['PUT'])]
    #[OA\Tag(name: 'VirtualTour')]
    #[OA\RequestBody(
        description: 'Edita un tour virtual de una tienda.',
        content: new Model(type: VirtualTourType::class)
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit_virtualTour($id=null): Response
    {
        if (!$id) {
            return $this->errorsInterface->error_message('Parametro no proporcionado.', Response::HTTP_NOT_FOUND);
        }

        $user= $this->getUser();
        $tienda = $this->em->getRepository(Tiendas::class)->findOneBy(['login' => $user]);

        if (!$tienda) {
            return $this->errorsInterface->error_message('Tienda no encontrada', Response::HTTP_NOT_FOUND);
        }

        $virtualTour = $this->em->getRepository(VirtualTour::class)->findOneBy(['id'=>$id,'tienda'=>$tienda]);

        if (!$virtualTour) {
            return $this->errorsInterface->error_message('Virtual Tour no encontrado', Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(VirtualTourType::class,$virtualTour);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            return $this->errorsInterface->succes_message('Editado', Response::HTTP_OK);

        }

        return $this->errorsInterface->form_errors($form);

    }

    #[Route('/api/delete_virtualTour/{id}', name: 'app_delete_virtualTour',methods:['DELETE'])]
    #[OA\Tag(name: 'VirtualTour')]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete_virtualTour($id=null): Response
    {
        if (!$id) {
            return $this->errorsInterface->error_message('Parametro no proporcionado.', Response::HTTP_NOT_FOUND);
        }

        $user= $this->getUser();
        $tienda = $this->em->getRepository(Tiendas::class)->findOneBy(['login' => $user]);

        if (!$tienda) {
            return $this->errorsInterface->error_message('Tienda no encontrada', Response::HTTP_NOT_FOUND);
        }

        $virtualTour = $this->em->getRepository(VirtualTour::class)->findOneBy(['id'=>$id,'tienda'=>$tienda]);

        if (!$virtualTour) {
            return $this->errorsInterface->error_message('Virtual Tour no encontrado', Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($virtualTour);
        $this->em->flush();
        return $this->errorsInterface->succes_message('Eliminado', Response::HTTP_OK);
    }

    #[Route('/api/show_scene/{id}', name: 'app_ver_scenas',methods:['GET'])]
    #[OA\Tag(name: 'VirtualTour')]
    #[OA\Response(
        response: 200,
        description: 'Muestra las escenas de un tour virtual.',
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function show_scenes(?VirtualTour $virtualTour): Response
    {

        if (!$virtualTour) {
            return $this->errorsInterface->error_message('Recurso no encontrado', Response::HTTP_NOT_FOUND);
        }


        $user= $this->getUser();
        if(!$user instanceof Login){
            return $this->errorsInterface->error_message('Error entidad.', Response::HTTP_NOT_FOUND);
        }

        if($virtualTour->getTienda()->getId() !== $user->getTiendas()->getId()){
            return $this->errorsInterface->error_message('Tour no encontrado.', Response::HTTP_NOT_FOUND);
        }
    
        $baseUrl = $this->request->getSchemeAndHttpHost() . $this->request->getBasePath();
    
        $tourData = [
            'id'     => $virtualTour->getId(),
            'name'   => $virtualTour->getNombre(),
            'scenes' => []
        ];
    
        foreach ($virtualTour->getScenes() as $scene) {
            $sceneData = [
                'id'        => $scene->getId(),
                'name'      => $scene->getNombre(),
                'imagePath' => $baseUrl . '/public/virtual_scenes/' . $scene->getImagePath(),
                'initialView' => [
                    'yaw'   => 0,
                    'pitch' => 0,
                    'fov'   => 0,
                ],
                'hotspots' => []
            ];
    
            foreach ($scene->getHotspots() as $hotspot) {
                $sceneData['hotspots'][] = [
                    'id'=>$hotspot->getId(),
                    'yaw'   => $hotspot->getYaw(),
                    'pitch' => $hotspot->getPitch(),
                    'type'  => $hotspot->getType(),
                    'text'  => $hotspot->getText(),
                    'url'   => $hotspot->getUrl(), // puede ser interno o externo
                    'slug_producto' => $hotspot->getSlugProducto()
                ];
            }
    
            $tourData['scenes'][] = $sceneData;
        }
    
        return $this->json($tourData);
    }

    #[Route('/api/add_ecenes/{virtualTour}', name: 'app_add_ecenes',methods:['POST'])]
    #[OA\Tag(name: 'VirtualTour')]
    #[OA\RequestBody(
    description: 'Añade una imagen al tour virtual de una tienda.',
    content: [
        new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                properties: [
                    new OA\Property(
                        property: 'image',
                        type: 'string',
                        format: 'binary',
                        description: 'Archivo de imagen único'
                    ),
                ]
            )
        ),
    ]
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function add_scenes( $virtualTour=null): Response
    {

        if (!$virtualTour) {
            return $this->errorsInterface->error_message('Parametro no proporcionado.', Response::HTTP_NOT_FOUND);
        }

        $user= $this->getUser();
        $tienda = $this->em->getRepository(Tiendas::class)->findOneBy(['login' => $user]);

        $VIRTUAL_TOUR= $this->em->getRepository(VirtualTour::class)->findOneBy(['id'=>$virtualTour,'tienda'=>$tienda]);

        if (!$VIRTUAL_TOUR) {
            return $this->errorsInterface->error_message('Virtual Tour no encontrado', Response::HTTP_NOT_FOUND);
        }


        $scenes= $this->em->getRepository(Scenes::class)->findBy(['virtualTour'=>$VIRTUAL_TOUR]);
        if ($scenes) { // Cambiar !$scenes por $scenes
            return $this->errorsInterface->error_message('Ya hay una escena en el virtual tour', Response::HTTP_BAD_REQUEST);
        }

        $form = $this->createForm(ScenesType::class);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
           
            $slug = str_replace(' ', '-', $VIRTUAL_TOUR->getNombre());

            // Convierte el slug a minúsculas.
            $slug = strtolower($slug);

            // Elimina caracteres especiales y acentos.
            $slug = preg_replace('/[^a-z0-9-]/', '', $slug);

            // Elimina guiones duplicados.
            $slug = preg_replace('/-+/', '-', $slug);

            $archivo = $form->get('image')->getData();

                if ($archivo instanceof UploadedFile) {
                    // Genera un nombre único para el archivo
                    $nombreArchivo = $slug . '-' . uniqid() . '.' . $archivo->guessExtension();

                    // Mueve el archivo al directorio de almacenamiento
                    $directorioAlmacenamiento = $this->getParameter('virtual_scenes');
                    $archivo->move($directorioAlmacenamiento, $nombreArchivo);

                    $galeria = new Scenes();
                    $galeria->setImagePath($nombreArchivo);
                    $galeria->setVirtualTour($VIRTUAL_TOUR);
                    $this->em->persist($galeria);
                    $this->em->flush();

                }

             return $this->errorsInterface->succes_message('Guardado', Response::HTTP_OK);


        }

         return $this->errorsInterface->form_errors($form);

    }

    #[Route('/api/delete_scene/{id}', name: 'app_delete_scene', methods:['DELETE'])]
    #[OA\Tag(name: 'VirtualTour')]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete_scene(VirtualTour $id= null): Response
    {
        if(!$id){
             return $this->errorsInterface->error_message('Virtual Tour no encontrado', Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($id);
        $this->em->flush();
        return $this->errorsInterface->succes_message('Eliminado', Response::HTTP_OK);
    }

    #[Route('/api/add_hotspots/{scene}', name: 'app_add_hots',methods:['POST'])]
    #[OA\Tag(name: 'VirtualTour')]
    #[OA\RequestBody(
        description: 'Añade hotspots a una scena de un tour_virtual.',
        content: new Model(type: HotspotsType::class)
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function add_hotspots(Scenes $scene=null): Response
    {
        if(!$scene){
             return $this->errorsInterface->error_message('Scena no encontrada', Response::HTTP_NOT_FOUND);
        }
        $hotspot= new Hotspot();

        $form = $this->createForm(HotspotsType::class, $hotspot);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {

            $hotspot->setScene($scene);
            $this->em->persist($hotspot);
            $this->em->flush();
            return $this->errorsInterface->succes_message('Guardado', Response::HTTP_OK);
        }

        
         return $this->errorsInterface->form_errors($form);
    }

    #[Route('/api/edit_hotspot/{id}', name: 'app_edit_hotspot', methods:['PATCH'])]
    #[OA\Tag(name: 'VirtualTour')]
    #[OA\RequestBody(
        description: 'Edita un hotspot de un tour_virtual.',
        content: new Model(type: HotspotsType::class)
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit_hotspot(Hotspot $hotspot=null): Response
    {
        if(!$hotspot){
             return $this->errorsInterface->error_message('Hotspot no encontrado.', Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(HotspotsType::class, $hotspot);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->em->persist($hotspot);
            $this->em->flush();
            return $this->errorsInterface->succes_message('Guardado', Response::HTTP_OK);
        }

         return $this->errorsInterface->form_errors($form); 

    }

    #[Route('/api/delete_hotspot/{id}', name: 'app_delete_hotspot', methods:['DELETE'])]
    #[OA\Tag(name: 'VirtualTour')]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete_hotspot(Hotspot $hotspot=null): Response
    {
        if(!$hotspot){
           return $this->errorsInterface->error_message('Hotspot no encontrado', Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($hotspot);
        $this->em->flush();
        return $this->errorsInterface->succes_message('Eliminado', Response::HTTP_OK);
    }
   
}

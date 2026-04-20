<?php

namespace App\Controller;

use App\Entity\Productos;
use App\Entity\Subastas;
use App\Entity\Tiendas;
use App\Form\SubastasType;
use App\Interfaces\ErrorsInterface;
use App\Repository\SubastasRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use DateTime;

#[Route('/api/subastas')]
#[OA\Tag(name: 'Subastas')]
#[Security(name: 'Bearer')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class SubastasController extends AbstractController
{
    private $errorsInterface;

    public function __construct(ErrorsInterface $errorsInterface)
    {
        $this->errorsInterface = $errorsInterface;
    }
  
    #[Route('/', name: 'app_subastas_index', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Lista de subastas del vendedor',
    )]
    #[OA\Parameter(
        name:"id_producto",
        in:"query",
        description:"Filtra subasta por id de producto."
    )]
    public function index(Request $request,SubastasRepository $subastasRepository,EntityManagerInterface $entityManager,UrlGeneratorInterface $router): Response
    {   $allowedParams = [
            'id_producto'
        ];

        // Obtener todos los parámetros enviados
        $queryParams = array_keys($request->query->all());

        // Detectar parámetros no permitidos
        $invalidParams = array_diff($queryParams, $allowedParams);

        if (!empty($invalidParams)) {
            return $this->errorsInterface->error_message(
                'Parámetros no permitidos: ' . implode(', ', $invalidParams),
                Response::HTTP_BAD_REQUEST
            );
        }
        $host = $router->getContext()->getBaseUrl();
        $domain = $request->getSchemeAndHttpHost(); 
        $data=[];
        $user= $this->getUser();
        $id_producto= $request->query->get('id_producto');
        if ($id_producto){
            $subastas= $subastasRepository->findBy(['IdProducto'=>$id_producto]);

            foreach ($subastas as $subasta) {
                $data=[
                 'id'=>$subasta->getId(),
                 'inicio_subasta'=>$subasta->getInicioSubasta(),
                 'fin_subasta'=>$subasta->getFinSubasta(),
                 'valor_minimo'=>$subasta->getValorMinimo(),
                 'activo'=>$subasta->isActivo(),
                 'producto'=>[
                    'id'=>$subasta->getIdProducto()->getId(),
                    'IdVariacion'=>$subasta->getIdVariacion() ? $subasta->getIdVariacion()->getId():'',
                 ]

                ];
             }
            
        }else{

            $tienda= $entityManager->getRepository(Tiendas::class)->findOneBy(['login'=>$user]);
            $subastas= $subastasRepository->findBy(['tienda'=>$tienda]);

            foreach ($subastas as $subasta) {
                $s= $subasta->getIdVariacion() ? $subasta->getIdVariacion() : null;
      
            
                $imagenesArray=[];
                $terminsoArray=[];
                if($s != null){
                
                  $variacion = $subasta->getIdVariacion();
    
                 if ($variacion->getVariacionesGalerias()->isEmpty()) {
    
                  foreach ($subasta->getIdProducto()->getProductosGalerias() as $galeria) {
                    $imagenesArray[] = [
                        'id' => $galeria->getId(),
                        'url' => $domain . $host . '/public/productos/' . $galeria->getUrlProductoGaleria(),
                    ];
                  }
    
                 } else {
           
                foreach ($variacion->getVariacionesGalerias() as $galeria) {
                    $imagenesArray[] = [
                        'id' => $galeria->getId(),
                        'url' => $domain . $host . '/public/productos/' . $galeria->getUrlVariacion(),
                    ];
                }
                
               }
    
                  foreach($subasta->getIdVariacion()->getTerminos() as $termino){
    
                      $terminsoArray[]=[
                        'nombre'=>$termino->getNombre()
                      ];
                  }
        
                }else{
            
                  
                  foreach($subasta->getIdProducto()->getProductosGalerias() as $galeria ){
                    $imagenesArray[]=[
                       'id'=>$galeria->getId(),
                       'url'=> $domain.$host.'/public/productos/'.$galeria->getUrlProductoGaleria()            
                    ];
                  }
                  
                }

                $data=[
                 'id'=>$subasta->getId(),
                 'inicio_subasta'=>$subasta->getInicioSubasta(),
                 'fin_subasta'=>$subasta->getFinSubasta(),
                 'valor_minimo'=>$subasta->getValorMinimo(),
                 'activo'=>$subasta->isActivo(),
                 'producto'=>[
                    'id'=>$subasta->getIdProducto()->getId(),
                    'IdVariacion'=>$subasta->getIdVariacion() ? $subasta->getIdVariacion()->getId():'',
                    'nombre' => $subasta->getIdProducto()->getNombreProducto(),
                    'slug' => $subasta->getIdProducto()->getSlugProducto(),
                    'galeria'=>$imagenesArray,
                    'terminos' => $terminsoArray
                 ]
     
                ];
             }
            
            
        }

        return $this->json($data);   
    }

    #[Route('/new/{id}', name: 'app_subastas_new', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'Añadir Subasta',
        content: new Model(type: SubastasType::class)
    )]
    public function new($id,Request $request, EntityManagerInterface $entityManager): Response
    {
        $user= $this->getUser();
        $tienda= $entityManager->getRepository(Tiendas::class)->findOneBy(['login'=>$user]);
       
        $producto = $entityManager->getRepository(Productos::class)->findOneBy(['id'=>$id,'tienda'=>$tienda,'productos_ventas'=>2]);

            if(!$producto){
                 return $this->errorsInterface->error_message('El producto no se encuentra disponible', Response::HTTP_NOT_FOUND);
            }

        $subastas= $entityManager->getRepository(Subastas::class)->findBy(['tienda'=>$tienda,'IdProducto'=>$producto,'activo'=>true]);

        if($subastas){
           return $this->errorsInterface->error_message('Solo puede tener una subasta activa por producto', Response::HTTP_NOT_ACCEPTABLE);
        }
        $subasta = new Subastas();
        $form = $this->createForm(SubastasType::class, $subasta);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

         $activoField = $request->request->get('subastas')['activo'] ?? null;

        // Si no está en la solicitud, establecer el valor de 'activo' en null o true
           if ($activoField === null) {
            $subasta->setActivo(true);
           }
        
            $subasta->setIdProducto($producto);
            $subasta->setTienda($tienda);
            $entityManager->persist($subasta);
            $entityManager->flush();

            return $this->errorsInterface->succes_message('Guardado', Response::HTTP_OK);
        }

         return $this->errorsInterface->form_errors($form);
    }

    #[Route('/{id}', name: 'app_subastas_show', methods: ['GET'])]
    public function show(Request $request,UrlGeneratorInterface $router,Subastas $subasta = null): Response
    {

        if (!$subasta){
             return $this->errorsInterface->error_message('No se encuentra la subasta', Response::HTTP_NOT_FOUND);
        }

        $host = $router->getContext()->getBaseUrl();
        $domain = $request->getSchemeAndHttpHost(); 

        $s= $subasta->getIdVariacion() ? $subasta->getIdVariacion() : null;
      
            
        $imagenesArray=[];
        $terminsoArray=[];
        if($s != null){
        
          $variacion = $subasta->getIdVariacion();

         if ($variacion->getVariacionesGalerias()->isEmpty()) {

          foreach ($subasta->getIdProducto()->getProductosGalerias() as $galeria) {
            $imagenesArray[] = [
                'id' => $galeria->getId(),
                'url' => $domain . $host . '/public/productos/' . $galeria->getUrlProductoGaleria(),
            ];
          }

         } else {
   
        foreach ($variacion->getVariacionesGalerias() as $galeria) {
            $imagenesArray[] = [
                'id' => $galeria->getId(),
                'url' => $domain . $host . '/public/productos/' . $galeria->getUrlVariacion(),
            ];
        }
        
       }

          foreach($subasta->getIdVariacion()->getTerminos() as $termino){

              $terminsoArray[]=[
                'nombre'=>$termino->getNombre()
              ];
          }

        }else{
    
          
          foreach($subasta->getIdProducto()->getProductosGalerias() as $galeria ){
            $imagenesArray[]=[
               'id'=>$galeria->getId(),
               'url'=> $domain.$host.'/public/productos/'.$galeria->getUrlProductoGaleria()            
            ];
          }
          
        }

        $data=[
         'id'=>$subasta->getId(),
         'inicio_subasta'=>$subasta->getInicioSubasta(),
         'fin_subasta'=>$subasta->getFinSubasta(),
         'valor_minimo'=>$subasta->getValorMinimo(),
         'producto'=>[
            'id'=>$subasta->getIdProducto()->getId(),
            'id_variacion'=>$subasta->getIdVariacion() ? $subasta->getIdVariacion()->getId():'',
            'nombre' => $subasta->getIdProducto()->getNombreProducto(),
            'slug' => $subasta->getIdProducto()->getSlugProducto(),
            'galeria'=>$imagenesArray,
            'terminos' => $terminsoArray
         ]

        ];

        return $this->json($data);

      
    }

    #[Route('/{id}/edit', name: 'app_subastas_edit', methods: ['PUT'])]
    #[OA\RequestBody(
        description: 'Editar subasta a partir de id',
        content: new Model(type: SubastasType::class)
    )]
    public function edit(Request $request, Subastas $subasta = null, EntityManagerInterface $entityManager): Response
    {
        if (!$subasta) {
            return $this->errorsInterface->error_message('No se encuentra la subasta', Response::HTTP_NOT_FOUND);
        }
    
        $id_producto = $subasta->getIdProducto();
        $user = $this->getUser();
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login' => $user]);
    
        // Verificar si hay una subasta activa diferente a la actual
        $subastaActiva = $entityManager->getRepository(Subastas::class)
            ->findOneBy(['tienda' => $tienda, 'IdProducto' => $id_producto, 'activo' => true]);
    
        // Si se está intentando activar la subasta y ya hay otra activa, bloquear
        if ($subasta->isActivo() === false && $subastaActiva && $subastaActiva->getId() !== $subasta->getId()) {
            return $this->errorsInterface->error_message('Solo puede tener una subasta activa por producto', Response::HTTP_NOT_ACCEPTABLE);
        }
    
        $form = $this->createForm(SubastasType::class, $subasta);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {

            $activoField = $request->request->get('subastas')['activo'] ?? null;

        // Si no está en la solicitud, establecer el valor de 'activo' en null o true
           if ($activoField === null) {
            $subasta->setActivo(true);
           }
            $entityManager->flush();

            return $this->errorsInterface->succes_message('Editado', Response::HTTP_OK);
        }
    
        // Manejo de errores de validación
        return $this->errorsInterface->form_errors($form);
    }

    
    #[Route('/{id}', name: 'app_subastas_delete', methods: ['DELETE'])]
    #[OA\Response(
        response: 200,
        description: 'Eliminar subasta por id',
    )]
    public function delete(Subastas $subasta = null, EntityManagerInterface $entityManager): Response
    {         
            if (!$subasta) {
               return $this->errorsInterface->error_message('No se encuentra la subasta', Response::HTTP_NOT_FOUND);
            }
        
            $entityManager->remove($subasta);
            $entityManager->flush();

            return $this->errorsInterface->succes_message('Eliminado', Response::HTTP_OK);
    }
}

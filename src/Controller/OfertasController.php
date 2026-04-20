<?php
namespace App\Controller;

use App\Entity\Ofertas;
use App\Entity\Productos;
use App\Entity\Subastas;
use App\Form\OfertasType;
use App\Interfaces\ErrorsInterface;
use App\Repository\OfertasRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use DateTime;

#[Route('/api/ofertas')]
#[OA\Tag(name: 'Ofertas')]
#[Security(name: 'Bearer')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class OfertasController extends AbstractController
{
    private $errorsInterface;

    public function __construct(ErrorsInterface $errorsInterface)
    {
        $this->errorsInterface = $errorsInterface;
    }

    #[Route('/mis_ofertas', name: 'app_ofertas_index', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Listar Ofertas',
    )]
    public function index(Request $request,OfertasRepository $ofertasRepository,UrlGeneratorInterface $router): Response
    {
        $host = $router->getContext()->getBaseUrl();
        $domain = $request->getSchemeAndHttpHost(); 
        $user = $this->getUser();
        $datos = $ofertasRepository->findBy(['login' => $user]);

        $array = [];
        foreach ($datos as $data) {
            
            $s= $data->getSubasta()  ? $data->getSubasta()->getIdVariacion() : null;
      
            
                $imagenesArray=[];
                $terminsoArray=[];
                if($s != null){
                
                  $variacion = $data->getSubasta()->getIdVariacion();
    
                 if ($variacion->getVariacionesGalerias()->isEmpty()) {
    
                  foreach ($data->getSubasta()->getIdProducto()->getProductosGalerias() as $galeria) {
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
    
                  foreach($data->getSubasta()->getIdVariacion()->getTerminos() as $termino){
    
                      $terminsoArray[]=[
                        'nombre'=>$termino->getNombre()
                      ];
                  }
        
                }else{
            
                  
                  foreach($data->getSubasta()->getIdProducto()->getProductosGalerias() as $galeria ){
                    $imagenesArray[]=[
                       'id'=>$galeria->getId(),
                       'url'=> $domain.$host.'/public/productos/'.$galeria->getUrlProductoGaleria()            
                    ];
                  }
                  
                }

            $array[] = [
                'id' => $data->getId(),
                'monto' => $data->getMonto(),
                'fecha' => $data->getFecha(),
                'inicio_subasta'=>$data->getSubasta()->getInicioSubasta(),
                'fin_subasta'=>$data->getSubasta()->getFinSubasta(),
                'fecha_creacion'=>$data->getFecha(),
                'fecha_edicion'=> $data->getFechaEdicion() ? $data->getFechaEdicion():'',
                'valor_minimo'=>$data->getSubasta()->getValorMinimo(),
                'producto'=>[
                     'id'=>$data->getSubasta()->getIdProducto()->getId(),
                    'id_variacion'=>$data->getSubasta()->getIdVariacion() ? $data->getSubasta()->getIdVariacion()->getId():'',
                     'nombre' => $data->getSubasta()->getIdProducto()->getNombreProducto(),
                     'slug' => $data->getSubasta()->getIdProducto()->getSlugProducto(),
                     'galeria'=>$imagenesArray,
                     'terminos' => $terminsoArray
                ]
            ];
        }

        return $this->json($array);
    }

    #[Route('/new/{id}', name: 'app_ofertas_new', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'Añadir Oferta',
        content: new Model(type: OfertasType::class)
    )]
    public function new(Subastas $id=null,Request $request, EntityManagerInterface $entityManager): Response
    {

        if(!$id){
            return $this->errorsInterface->error_message('La subasta no existe', Response::HTTP_NOT_FOUND);
        }
        $user = $this->getUser();

        $ofertas= $entityManager->getRepository(Ofertas::class)->findBy(['subasta'=>$id,'login'=>$user]);

        if ($ofertas) {
            return $this->errorsInterface->error_message('Usted ya ha realizado una oferta en esta subasta', Response::HTTP_BAD_REQUEST);
        }


        $now = new DateTime('now');
        $fechaInicioSubasta = $id->getInicioSubasta();
        $fechaFinSubasta = $id->getFinSubasta();

        if ($fechaInicioSubasta >= $now) {
                return $this->errorsInterface->error_message('La subasta aún no ha comenzado', Response::HTTP_BAD_REQUEST);
        }

        if ($fechaFinSubasta <= $now) {
                return $this->errorsInterface->error_message('La subasta ya ha terminado', Response::HTTP_BAD_REQUEST);
        }

        
        $oferta = new Ofertas();
        $form = $this->createForm(OfertasType::class, $oferta);
        $form->handleRequest($request);

        
        if ($form->isSubmitted() && $form->isValid()) {

            $monto = $form->get('monto')->getData();


            if ($monto <  $id->getValorMinimo() ) {
               return $this->errorsInterface->error_message('El monto debe ser mayor a'.' '.$id->getValorMinimo(), Response::HTTP_BAD_REQUEST);
            }


            $oferta->setLogin($user);
            $oferta->setSubasta($id);
            $oferta->setFecha(new DateTime('now'));
            $entityManager->persist($oferta);
            $entityManager->flush();

            return $this->errorsInterface->succes_message('Guardado');
        }
 
        return $this->errorsInterface->form_error(
            $form,
            'Error al guardar la oferta'
        );
    }



    #[Route('/{id}/show', name: 'app_ofertas_show', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Mostrar Oferta',
    )]
    public function show(Ofertas $oferta): Response
    {
        $data=[
             'id' => $oferta->getId(),
                'monto' => $oferta->getMonto(),
                'fecha' => $oferta->getFecha(),
                'fecha_edicion'=> $oferta->getFechaEdicion() ? $oferta->getFechaEdicion():''

        ];

        return $this->json($data);

    }

    #[Route('/{id}/edit', name: 'app_ofertas_edit', methods: ['PUT'])]
    #[OA\RequestBody(
        description: 'Editar Oferta',
        content: new Model(type: OfertasType::class)
    )]
    public function edit(Request $request, Ofertas $oferta = null, EntityManagerInterface $entityManager): Response
    {
        if (!$oferta) {
            return $this->errorsInterface->error_message('La oferta no existe', Response::HTTP_NOT_FOUND);
        }

            $now = new DateTime('now');
            $fechaInicioSubasta = $oferta->getSubasta()->getInicioSubasta();
            $fechaFinSubasta = $oferta->getSubasta()->getFinSubasta();

            if ($fechaInicioSubasta >= $now) {
                return $this->errorsInterface->error_message('La subasta aún no ha comenzado', Response::HTTP_BAD_REQUEST);
            }

            if ($fechaFinSubasta <= $now) {
                return $this->errorsInterface->error_message('La subasta ya ha terminado', Response::HTTP_BAD_REQUEST);
            }

        $form = $this->createForm(OfertasType::class, $oferta);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $monto = $form->get('monto')->getData();


            if ($monto  <  $oferta->getSubasta()->getValorMinimo() ) {
                return $this->errorsInterface->error_message('El monto debe ser mayor a'.' '.$oferta->getSubasta()->getValorMinimo(), Response::HTTP_BAD_REQUEST);
            }

        
            

            $oferta->setFechaEdicion(new DateTime('now'));
            $entityManager->flush();

            return $this->json(['message' => 'Editado'])->setStatusCode(Response::HTTP_OK);
        }

        return $this->errorsInterface->form_error(
            $form,
            'Error al editar la oferta'
        );
    }

    #[Route('/{id}/delete', name: 'app_ofertas_delete', methods: ['DELETE'])]
    #[OA\Response(
        response: 200,
        description: 'Eliminar Oferta',
    )]
    public function delete(Request $request, Ofertas $oferta = null, EntityManagerInterface $entityManager): Response
    {
        if (!$oferta) {
            return $this->errorsInterface->error_message('La oferta no existe', Response::HTTP_NOT_FOUND);
        }

        $now = new DateTime('now');
        $fechaInicioSubasta = $oferta->getSubasta()->getInicioSubasta();
        $fechaFinSubasta = $oferta->getSubasta()->getFinSubasta();

        if ($fechaInicioSubasta >= $now) {
            return $this->errorsInterface->error_message('La subasta aún no ha comenzado', Response::HTTP_BAD_REQUEST);
        }

        if ($fechaFinSubasta <= $now) {
            return $this->errorsInterface->error_message('La subasta ya ha terminado', Response::HTTP_BAD_REQUEST);
        }

      
            $entityManager->remove($oferta);
            $entityManager->flush();

            return $this->errorsInterface->succes_message('Oferta eliminada correctamente');  
    }
}
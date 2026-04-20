<?php

namespace App\Controller;


use App\Entity\EntregasTipo;
use App\Entity\Estados;
use App\Interfaces\ErrorsInterface;
use App\Repository\ProductosMarcasRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;

class ProductosMarcasController extends AbstractController
{
    private $errorsInterface;
    public function __construct(ErrorsInterface $errorsInterface){
         $this->errorsInterface= $errorsInterface;
    }
    #[Route('/api/marcas', name: 'app_productos_marcas', methods: ['GET'])]
    #[OA\Tag(name: 'Marcas')]
    #[OA\Response(
        response: 200,
        description: 'Lista de marcas con todas las categorias a las que pertenese'
    )]
    public function index(ProductosMarcasRepository $productosMarcasRepository): Response
    {
        $marcas= $productosMarcasRepository->findBy(['published'=>true]);
        $marcasArray=[];
        foreach($marcas as $marca){
            
            $categoriasArray=[];
            foreach ($marca->getCategorias() as $categoria){
                $categoriasArray[]=[
                    'id'=>$categoria->getId(),
                    'nombre'=>$categoria->getNombre(),
                ];
            }
            $marcasArray[]=[
                'id'=>$marca->getId(),
                'nombre'=>$marca->getNombreM(),
                'categorias'=>$categoriasArray
            ];
        }

        return $this->json($marcasArray);
    }

    #[Route('/marcas', name: 'lista-marcas',methods:['GET'])]
    #[OA\Tag(name: 'Marcas')]
    #[OA\Response(
        response: 200,
        description: 'Lista de marcas'
    )]
    #[OA\Parameter(
        name: "categoria",
        in: "query",
        description: "filtra las marcas segun las categorias publicas."
    )]
    public function index_marcas(Request $request,ProductosMarcasRepository $productosMarcasRepository): Response
    {
        $allowedParams = [
            'categoria'
        ];

        // Obtener todos los parámetros enviados
        $queryParams = array_keys($request->query->all());

        // Detectar parámetros no permitidos
        $invalidParams = array_diff($queryParams, $allowedParams);

        if (!empty($invalidParams)) {
            return $this->errorsInterface->error_message(
                'Parámetros no permitidos',
                Response::HTTP_BAD_REQUEST
            );
        }
        $categoria = $request->query->get('categoria');
        $marcas= $productosMarcasRepository->findCategoriesWithMarcas($categoria);
        $marcasArray=[];
        foreach($marcas as $marca){
               
            $marcasArray[]=[
                'id'=>$marca->getId(),
                'nombre'=>$marca->getNombreM(),
                'slug'=>$marca->getSlug()    
            ];
        }

        return $this->json($marcasArray);
    }


    #[Route('/marcas_tienda', name: 'lista-marcas-tienda',methods:['GET'])]
    #[OA\Tag(name: 'Marcas')]
    #[OA\Response(
        response: 200,
        description: 'Lista de marcas de la tienda'
    )]
    #[OA\Parameter(
        name: "categoria",
        in: "query",
        description: "filtra las marcars segun las categorias de la tienda oficial."
    )]
    public function index_marcas_tienda(Request $request,ProductosMarcasRepository $productosMarcasRepository): Response
    {
        $categoria = $request->query->get('categoria');
        $marcas= $productosMarcasRepository->findproductos_marcas($categoria);
        $marcasArray=[];
        foreach($marcas as $marca){
               
            $marcasArray[]=[
                'id'=>$marca->getMarcas() ? $marca->getMarcas()->getId() :'',
                'nombre'=> $marca->getMarcas()  ? $marca->getMarcas()->getNombreM():'',
                'slug'=>$marca->getMarcas() ? $marca->getMarcas()->getSlug():''   
            ];
        }

        return $this->json($marcasArray);
    }


    #[Route('/entregas_tipo', name: 'tipo_entrega',methods: ['GET'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Lista de metodos de envio de productos'
    )]
    public function t_entrega(EntityManagerInterface $entityManager): Response
    {
        $entregas_tipo= $entityManager->getRepository(EntregasTipo::class)->findAll();
        $data=[];
        foreach($entregas_tipo as $entrega){
            $data[]=[
                'id'=>$entrega->getId(),
                'nombre'=>$entrega->getTipo(),
                'slug'=>$entrega->getSlug()
                ];
            }
        return $this->json($data);
    }

    #[Route('/estado_producto', name: 'estado',methods: ['GET'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Lista de estados del producto'
    )]
    public function estado_producto(EntityManagerInterface $entityManager): Response
    {
        $estados= $entityManager->getRepository(Estados::class)->findBy(['tipo_estado'=>'PRODUCTO']);
        $data=[];
        foreach($estados as $estado){
            $data[]=[
                'id'=>$estado->getId(),
                'nombre'=>$estado->getNobreEstado(),
                'slug'=>$estado->getSlug(),
                ];
        }

        return $this->json($data);
    }

}

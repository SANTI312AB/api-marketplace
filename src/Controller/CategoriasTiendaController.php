<?php

namespace App\Controller;

use App\Entity\CategoriasTienda;
use App\Entity\Productos;
use App\Entity\Tiendas;
use App\Form\CategoriasTiendaEditType;
use App\Form\CategoriasTiendaType;
use App\Form\TCategoriasBannerType;
use App\Form\TProductosCategoriasType;
use App\Interfaces\ErrorsInterface;
use App\Repository\CategoriasTiendaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;

#[Route('/api/tienda/categorias')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class CategoriasTiendaController extends AbstractController
{
    private $errorsInterface;

    public function __construct(ErrorsInterface $errorsInterface)
    {
        $this->errorsInterface = $errorsInterface;
    }

    #[Route('/', name: 'app_categorias_tienda_index', methods: ['GET'])]
    #[OA\Tag(name: 'Tienda')]
    #[OA\Response(
        response: 200,
        description: 'Lista de categorias de la tienda',
    )]
    #[Security(name: 'Bearer')]
    public function index(Request $request,UrlGeneratorInterface $router,CategoriasTiendaRepository $categoriasTiendaRepository,EntityManagerInterface $entityManager): Response
    {
        $domain = $request->getSchemeAndHttpHost(); 
        $host = $router->getContext()->getBaseUrl();

        $user = $this->getUser();
        $tienda= $entityManager->getRepository(Tiendas::class)->findOneBy(['login'=>$user]);
    
        $categoriasTiendas = $categoriasTiendaRepository->findBy(['Tiendas'=>$tienda]);

        $Array=[];
        foreach ($categoriasTiendas as $categoriaTienda) {

            $subcategoriasTiendasArray = [];

            foreach ($categoriaTienda->getSubcategoriasTiendas() as $subcategoria){

                $subcategoriasTiendasArray[] = [
                    'id' => $subcategoria->getId(),
                    'nombre' => $subcategoria->getNombre(),
                    'image'=> $subcategoria->getImagen() ?  $domain . $host . '/public/subcategorias/' . $subcategoria->getImagen():''
                ];
            }

            $Array[]=[
                'id'=>$categoriaTienda->getId(),
                'nombre'=>$categoriaTienda->getNombre(),
                'banner'=>$categoriaTienda->getBanner() ? $domain.$host. '/public/categorias/'. $categoriaTienda->getBanner():'',
                'subcategorias'=>$subcategoriasTiendasArray
            ];
        }
            
        return $this->json($Array);
    }

    #[Route('/new', name: 'app_categorias_tienda_new', methods: ['POST'])]
    #[OA\Tag(name: 'Tienda')]
    #[OA\RequestBody(
        description: 'Añade una categoria a la tienda',
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(properties: [
                    new OA\Property(
                        property: 'nombre',
                        type: 'text',
                    ),

                    new OA\Property(
                        property: 'banner',
                        type: 'file',
                    ),

                    new OA\Property(
                        property: 'imagen',
                        type: 'file',
                    ),
                ])
            ),
        ]
    )]
    #[Security(name: 'Bearer')]
    public function new(Request $request,UrlGeneratorInterface $router, EntityManagerInterface $entityManager,CategoriasTiendaRepository $categoriasTiendaRepository): Response
    {
        $domain = $request->getSchemeAndHttpHost(); 
        $host = $router->getContext()->getBaseUrl();
        $user = $this->getUser();
        $tienda= $entityManager->getRepository(Tiendas::class)->findOneBy(['login'=>$user]);

        if($tienda->getEstado()->getNobreEstado() !=='VERIFICADO'){
            return $this->errorsInterface->error_message(
                'La tienda no está verificada',
                Response::HTTP_BAD_REQUEST
            );
        }


        $numero_categorias = $categoriasTiendaRepository->findBy(['Tiendas'=>$tienda]);

        if(count($numero_categorias) >=100 ){

           return $this->errorsInterface->error_message(
               'Solo se puede crear un máximo de 6 categorías para la tienda',
               Response::HTTP_CONFLICT
           );
        }

        $categoriasTienda = new CategoriasTienda();
        $form = $this->createForm(CategoriasTiendaType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $nombre= $form->get('nombre')->getData();
            $banner= $form->get('banner')->getData();
            $imagen= $form->get('imagen')->getData();
    

            $categoriasTiendas = $categoriasTiendaRepository->findBy([
                'Tiendas' => $tienda,
                'nombre' => $nombre,
            ]);
    
    // Filtra la categoría que estás editando para no incluirla en el resultado.
            $categoriasTiendas = array_filter($categoriasTiendas, function ($categoria) use ($categoriasTienda) {
               return $categoria->getId() !== $categoriasTienda->getId();
           });
    
         if (!empty($categoriasTiendas)) {
            return $this->errorsInterface->error_message('Ya existe una categoría con ese nombre', Response::HTTP_BAD_REQUEST);
        }


            $slug = str_replace(' ', '-', $nombre);


            // Convierte el slug a minúsculas.
            $slug = strtolower($slug);

            // Elimina caracteres especiales y acentos.
            $slug = preg_replace('/[^a-z0-9áéíóúüñ-]/', '', $slug);

             // Elimina guiones duplicados.
            $slug = preg_replace('/-+/', '-', $slug);

            $categoriasTienda->setNombre($nombre);
            $categoriasTienda->setTiendas($tienda);
            $categoriasTienda->setSlug($slug.'-'.uniqid());


            if ($banner instanceof UploadedFile) {

                $nombreArchivo = $slug.'-'.uniqid() .'.'. $banner->guessExtension();
                $directorioAlmacenamiento = $this->getParameter('images_category');
                $banner->move($directorioAlmacenamiento, $nombreArchivo);
                $categoriasTienda->setBanner($nombreArchivo);
    
            }
    
            if ($imagen instanceof UploadedFile) {

                $nombreArchivo = $slug.'-'.uniqid() .'.'. $imagen->guessExtension();
                $directorioAlmacenamiento = $this->getParameter('images_category');
                $imagen->move($directorioAlmacenamiento, $nombreArchivo);
                $categoriasTienda->setImagen($nombreArchivo);
    
            }
    
            $entityManager->persist($categoriasTienda);
            $entityManager->flush();

            $data=[
                'id'=>$categoriasTienda->getId(),
                'nombre'=>$categoriasTienda->getNombre(),
                'banner'=>$categoriasTienda->getBanner() ? $domain.$host. '/public/categorias/'. $categoriasTienda->getBanner():'',
                'imagen'=>$categoriasTienda->getImagen() ? $domain.$host. '/public/categorias/'. $categoriasTienda->getImagen():'',
            ];                          
    
        

            return $this->errorsInterface->succes_message('Guardado', $data);
        }

        
         return $this->errorsInterface->form_errors($form);
        
    }

   

    #[Route('/{id}', name: 'app_categorias_tienda_edit', methods: ['PUT'])]
    #[OA\Tag(name: 'Tienda')]
    #[OA\RequestBody(
        description: 'Edita una categoria de la tienda',
        content: new Model(type: CategoriasTiendaEditType::class)  
    )]
    #[Security(name: 'Bearer')]
    public function edit(Request $request,UrlGeneratorInterface $router,$id, EntityManagerInterface $entityManager,CategoriasTiendaRepository $categoriasTiendaRepository): Response
    {
        $domain = $request->getSchemeAndHttpHost(); 
        $host = $router->getContext()->getBaseUrl();
        $user = $this->getUser();
        $tienda= $entityManager->getRepository(Tiendas::class)->findOneBy(['login'=>$user]);

        if($tienda->getEstado()->getNobreEstado() !=='VERIFICADO'){
            return $this->errorsInterface->error_message(
                'La tienda no está verificada',
                Response::HTTP_BAD_REQUEST
            );
        }


        $categoriasTienda= $entityManager->getRepository(CategoriasTienda::class)->findOneBy(['id'=>$id,'Tiendas'=>$tienda]);
        if(!$categoriasTienda){
            return $this->errorsInterface->error_message(
                'No existe la categoría',
                Response::HTTP_NOT_FOUND
            );
        }

        $form = $this->createForm(CategoriasTiendaEditType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

        
            $nombre= $form->get('nombre')->getData();

            $categoriasTiendas = $categoriasTiendaRepository->findBy([
                'Tiendas' => $tienda,
                'nombre' => $nombre,
            ]);
    
    // Filtra la categoría que estás editando para no incluirla en el resultado.
            $categoriasTiendas = array_filter($categoriasTiendas, function ($categoria) use ($categoriasTienda) {
               return $categoria->getId() !== $categoriasTienda->getId();
           });
    
         if (!empty($categoriasTiendas)) {
            return $this->errorsInterface->error_message(
                'Ya existe una categoría con ese nombre',
                Response::HTTP_BAD_REQUEST
            );
        }
            $slug = str_replace(' ', '-', $nombre);


            // Convierte el slug a minúsculas.
            $slug = strtolower($slug);

            // Elimina caracteres especiales y acentos.
            $slug = preg_replace('/[^a-z0-9áéíóúüñ-]/', '', $slug);

             // Elimina guiones duplicados.
            $slug = preg_replace('/-+/', '-', $slug);

            if($nombre !== $categoriasTienda->getNombre()){
                $categoriasTienda->setNombre($nombre);
                $categoriasTienda->setUpdateAt(new \DateTime());
                $categoriasTienda->setSlug($slug.uniqid());
            }

            $entityManager->flush();

            $data=[

                'id'=>$categoriasTienda->getId(),
                'nombre'=>$categoriasTienda->getNombre(),
                'banner'=>$categoriasTienda->getBanner() ? $domain.$host. '/public/categorias/'. $categoriasTienda->getBanner():'',
                'imagen'=>$categoriasTienda->getImagen() ? $domain.$host. '/public/categorias/'. $categoriasTienda->getImagen():'',
            ];  

           return $this->errorsInterface->succes_message('Editado', $data);
        }

          return $this->errorsInterface->form_errors($form);

    }


    #[Route('/edit_images/{id}', name: 'editar_imagenes_categoria_tienda',methods:['POST'])]
    #[OA\Tag(name: 'Tienda')]
    #[OA\RequestBody(
        description: 'Edita imagenes de la categoria de la tienda',
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(properties: [
                    new OA\Property(
                        property: 'banner',
                        type: 'file',
                    ),
                    new OA\Property(
                        property: 'imagen',
                        type: 'file',
                    ),
                ])
            ),
        ]
    )]
    #[Security(name: 'Bearer')]
    public function edit_images(Request $request,UrlGeneratorInterface $router,$id, EntityManagerInterface $entityManager): Response
    {
        $domain = $request->getSchemeAndHttpHost(); 
        $host = $router->getContext()->getBaseUrl();
        $user = $this->getUser();
        $tienda= $entityManager->getRepository(Tiendas::class)->findOneBy(['login'=>$user]);

        if($tienda->getEstado()->getNobreEstado() !=='VERIFICADO'){
            return $this->errorsInterface->error_message(
                'La tienda no está verificada',
                Response::HTTP_BAD_REQUEST
            );
        }


        $categoriasTienda = $entityManager->getRepository(CategoriasTienda::class)->find($id);
        $form = $this->createForm(TCategoriasBannerType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
           
            $slug = str_replace(' ', '-', $categoriasTienda->getNombre());


            // Convierte el slug a minúsculas.
            $slug = strtolower($slug);

            // Elimina caracteres especiales y acentos.
            $slug = preg_replace('/[^a-z0-9áéíóúüñ-]/', '', $slug);

             // Elimina guiones duplicados.
            $slug = preg_replace('/-+/', '-', $slug);


            if ($form->get('banner')->isSubmitted()) {
                $banner = $form->get('banner')->getData();
                if ($banner instanceof UploadedFile && !empty($banner)) {

                    $nombreArchivo = $slug.'-'.uniqid() .'.'. $banner->guessExtension();
                    $directorioAlmacenamiento = $this->getParameter('images_category');
                    $banner->move($directorioAlmacenamiento, $nombreArchivo);
                    $categoriasTienda->setBanner($nombreArchivo);
                    $entityManager->flush();
        
                }
          
            }

            if ($form->get('imagen')->isSubmitted()) {
                $imagen = $form->get('imagen')->getData();

                if ($imagen instanceof UploadedFile && !empty($imagen)) {

                    $nombreArchivo = $slug.'-'.uniqid() .'.'. $imagen->guessExtension();
                    $directorioAlmacenamiento = $this->getParameter('images_category');
                    $imagen->move($directorioAlmacenamiento, $nombreArchivo);
                    $categoriasTienda->setImagen($nombreArchivo);
                    $entityManager->flush();
        
                }
            }

            $data=[
                'id'=>$categoriasTienda->getId(),
                'nombre'=>$categoriasTienda->getNombre(),
                'banner'=>$categoriasTienda->getBanner() ? $domain.$host. '/public/categorias/'. $categoriasTienda->getBanner():'',
                'imagen'=>$categoriasTienda->getImagen() ? $domain.$host. '/public/categorias/'. $categoriasTienda->getImagen():'',
            ];                          

            return $this->errorsInterface->succes_message('Guardado', $data);
        }

       
    

        return $this->errorsInterface->form_errors($form);
    }


     #[Route('/{id}/productos', name:'añadir_productos_categoria',methods:['POST'])]
     #[OA\Tag(name: 'Tienda')]
     #[OA\RequestBody(
        description: 'Añade productos a la categoria',
        content:  new Model(type: TProductosCategoriasType::class)  
     )]
     #[Security(name: 'Bearer')]
     public function addOrEditProducts($id, Request $request, EntityManagerInterface $entityManager): Response
     {
    
        $user = $this->getUser();
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login' => $user]);

        $categoriasTienda = $entityManager->getRepository(CategoriasTienda::class)->findOneBy(['id' => $id, 'Tiendas' => $tienda]);

        if (!$categoriasTienda) {
           return $this->errorsInterface->error_message(
               'No existe la categoría',
               Response::HTTP_NOT_FOUND
           );
       }

       $form = $this->createForm(TProductosCategoriasType::class);
       $form->handleRequest($request);

       if ($form->isSubmitted() && $form->isValid()) {
        $newProductos = $form->get('productos')->getData();
        $existingProductos = $categoriasTienda->getProductos();

        // Convert the collections to arrays for easier comparison
        $newProductosArray = $newProductos->toArray();
        $existingProductosArray = $existingProductos->toArray();

        // Add new products that are not already in the existing collection
        foreach ($newProductosArray as $producto) {
            if (!in_array($producto, $existingProductosArray)) {
                $categoriasTienda->addProducto($producto);
            }
        }

        // Remove products that are no longer in the new collection
        foreach ($existingProductosArray as $producto) {
            if (!in_array($producto, $newProductosArray)) {
                $categoriasTienda->removeProducto($producto);
            }
        }

        $entityManager->flush();


        return $this->errorsInterface->succes_message('Guardado');
       }

        return $this->errorsInterface->form_errors($form);
   }


     #[Route('/{id}/delete', name: 'app_categorias_tienda_delete', methods: ['DELETE'])]
     #[OA\Tag(name: 'Tienda')]
     #[OA\Response(
        response: 200,
        description: 'Elimina una categoria de la tienda',
     )]
     #[Security(name: 'Bearer')]
     public function delete($id, EntityManagerInterface $entityManager): Response
     {
             $categoriasTienda= $entityManager->getRepository(CategoriasTienda::class)->find($id);
             
             if (!$categoriasTienda) {
                return $this->errorsInterface->error_message(
                    'No existe la categoría',
                    Response::HTTP_NOT_FOUND
                );
             }
             $entityManager->remove($categoriasTienda);
             $entityManager->flush();

             return $this->errorsInterface->succes_message('Eliminado');
     }
}

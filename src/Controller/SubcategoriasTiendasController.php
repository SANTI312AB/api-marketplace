<?php

namespace App\Controller;

use App\Entity\CategoriasTienda;
use App\Entity\SubcategoriasTiendas;
use App\Entity\Tiendas;
use App\Form\SubcategoriasTiendaEditType;
use App\Form\SubcategoriasTiendaType;
use App\Form\TProductosSubcategoriasType;
use App\Form\TSubcategoriasImageType;
use App\Interfaces\ErrorsInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


#[Route('/api/tienda/subcategorias')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class SubcategoriasTiendasController extends AbstractController
{
    private $errorsInterface;

    public function __construct(ErrorsInterface $errorsInterface)
    {
        $this->errorsInterface = $errorsInterface;
    }

    #[Route('/new/{id}', name: 'app_subcategorias_tienda_new', methods: ['POST'])]
    #[OA\Tag(name: 'Tienda')]
    #[OA\RequestBody(
        description: 'Añade una subcategoria a la tienda apartir del id de un categoria de tienda',
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(properties: [
                    new OA\Property(
                        property: 'nombre',
                        type: 'text',
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
    public function new(Request $request,UrlGeneratorInterface $router, EntityManagerInterface $entityManager, CategoriasTienda $id = null): Response
    {
        $domain = $request->getSchemeAndHttpHost(); 
        $host = $router->getContext()->getBaseUrl();
        $user = $this->getUser();
        $tienda= $entityManager->getRepository(Tiendas::class)->findOneBy(['login'=>$user]);

        if($tienda->getEstado()->getNobreEstado() !=='VERIFICADO'){
             return $this->errorsInterface->error_message('La tienda no esta verificada', Response::HTTP_BAD_REQUEST);
        }


        if (!$id) {
            return $this->errorsInterface->error_message('Categoría de tienda no encontrada', Response::HTTP_NOT_FOUND);
        }


        $subcategoriaTienda = new SubcategoriasTiendas();
        $form = $this->createForm(SubcategoriasTiendaType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            $nombre= $form->get('nombre')->getData();
            $imagen= $form->get('imagen')->getData();
    

            $subcategoriaTiendas = $entityManager->getRepository( SubcategoriasTiendas::class)->findOneBy([
                'categoriaTienda' => $id,
                'nombre' => $nombre
            ]);
    
    
         if ($subcategoriaTiendas) {
            return $this->errorsInterface->error_message('Ya existe una subcategoria  con este nombre', Response::HTTP_BAD_REQUEST);
         }


            $slug = str_replace(' ', '-', $nombre);


            // Convierte el slug a minúsculas.
            $slug = strtolower($slug);

            // Elimina caracteres especiales y acentos.
            $slug = preg_replace('/[^a-z0-9áéíóúüñ-]/', '', $slug);

             // Elimina guiones duplicados.
            $slug = preg_replace('/-+/', '-', $slug);

            $subcategoriaTienda->setNombre($nombre);
            $subcategoriaTienda->setCategoriaTienda($id);
            $subcategoriaTienda->setSlug($slug.uniqid());

    
            if ($imagen instanceof UploadedFile) {

                $nombreArchivo = $slug.'-'.uniqid() .'.'. $imagen->guessExtension();
                $directorioAlmacenamiento = $this->getParameter('images_subcategory');
                $imagen->move($directorioAlmacenamiento, $nombreArchivo);
                $subcategoriaTienda->setImagen($nombreArchivo);
    
            }
    
            $entityManager->persist($subcategoriaTienda);
            $entityManager->flush();

            $data=[
                'id'=>$subcategoriaTienda->getId(),
                'nombre'=>$subcategoriaTienda->getNombre(),
                'image'=>$subcategoriaTienda->getImagen() ? $domain.$host. '/public/subcategorias/'. $subcategoriaTienda->getImagen():'',
            ];                          
    

             return $this->errorsInterface->succes_message('Guardado', null, $data);
        }

        
          return $this->errorsInterface->form_errors($form);
        
    }


    
    #[Route('/{id}', name: 'app_sucategorias_tienda_edit', methods: ['PUT'])]
    #[OA\Tag(name: 'Tienda')]
    #[OA\RequestBody(
        description: 'Edita una subcategoria  de la tienda',
        content: new Model(type: SubcategoriasTiendaEditType::class)  
    )]
    #[Security(name: 'Bearer')]
    public function edit(Request $request,UrlGeneratorInterface $router, EntityManagerInterface $entityManager, SubcategoriasTiendas $id = null): Response
    {
        $domain = $request->getSchemeAndHttpHost(); 
        $host = $router->getContext()->getBaseUrl();
        $user = $this->getUser();
        $tienda= $entityManager->getRepository(Tiendas::class)->findOneBy(['login'=>$user]);

        if($tienda->getEstado()->getNobreEstado() !=='VERIFICADO'){
             return $this->errorsInterface->error_message('La tienda no está verificada', Response::HTTP_BAD_REQUEST);
        }


        
        if(!$id){
             return $this->errorsInterface->error_message('No existe la subcategoria', Response::HTTP_NOT_FOUND);
        }

        $categoriasTienda= $entityManager->getRepository(CategoriasTienda::class)->find( $id->getCategoriaTienda()->getId());

    

        $form = $this->createForm(SubcategoriasTiendaEditType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

        
            $nombre= $form->get('nombre')->getData();


            $subcategoriaTiendas = $entityManager->getRepository(SubcategoriasTiendas::class)->findBy([
                'categoriaTienda' => $categoriasTienda,
                'nombre' => $nombre
            ]);
            
            // Filtra la categoría que estás editando para no incluirla en el resultado.
            $subcategoriaTiendas = array_filter($subcategoriaTiendas, function ($subcategoria) use ($id) {
                return $subcategoria->getId() !== $id->getId();
            });


            if (!empty($subcategoriaTiendas)) {
                 return $this->errorsInterface->error_message('Ya existe una subcategoria  con ese nombre', Response::HTTP_BAD_REQUEST);
            }
        
    
            

            $slug = str_replace(' ', '-', $nombre);


            // Convierte el slug a minúsculas.
            $slug = strtolower($slug);

            // Elimina caracteres especiales y acentos.
            $slug = preg_replace('/[^a-z0-9áéíóúüñ-]/', '', $slug);

             // Elimina guiones duplicados.
            $slug = preg_replace('/-+/', '-', $slug);

            if($nombre !== $id->getNombre()){
                $id->setNombre($nombre);
                $id->setUpdateAt( new \DateTime);
                $id->setSlug($slug.uniqid());
            }

            $entityManager->flush();

            $data=[

                'id'=>$id->getId(),
                'nombre'=>$id->getNombre(),
                'image'=>$id->getImagen() ? $domain.$host. '/public/subcategorias/'. $id->getImagen():'',
            ];  

             return $this->errorsInterface->succes_message('Editado', null, $data);
        }
    

         return $this->errorsInterface->form_errors($form);

    }



    #[Route('/edit_images/{id}', name: 'editar_imagenes_subcategoria_tienda',methods:['POST'])]
    #[OA\Tag(name: 'Tienda')]
    #[OA\RequestBody(
        description: 'Edita imagenes de la subcatergoria  de la tienda',
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(properties: [
                    new OA\Property(
                        property: 'imagen',
                        type: 'file',
                    ),
                ])
            ),
        ] 
       
    )]
    #[Security(name: 'Bearer')]
    public function edit_images(Request $request,UrlGeneratorInterface $router, EntityManagerInterface $entityManager, SubcategoriasTiendas $id = null): Response
    {
        $domain = $request->getSchemeAndHttpHost(); 
        $host = $router->getContext()->getBaseUrl();
        $user = $this->getUser();
        $tienda= $entityManager->getRepository(Tiendas::class)->findOneBy(['login'=>$user]);

        if($tienda->getEstado()->getNobreEstado() !=='VERIFICADO'){
            return $this->errorsInterface->error_message('La tienda no esta verificada', Response::HTTP_BAD_REQUEST);
        }


        if(!$id){
             return $this->errorsInterface->error_message('No existe la subcategoria', Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(TSubcategoriasImageType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
           
            $slug = str_replace(' ', '-', $id->getNombre());


            // Convierte el slug a minúsculas.
            $slug = strtolower($slug);

            // Elimina caracteres especiales y acentos.
            $slug = preg_replace('/[^a-z0-9áéíóúüñ-]/', '', $slug);

             // Elimina guiones duplicados.
            $slug = preg_replace('/-+/', '-', $slug);


            
            if ($form->get('imagen')->isSubmitted()) {
                $imagen = $form->get('imagen')->getData();

                if ($imagen instanceof UploadedFile && !empty($imagen)) {

                    $nombreArchivo = $slug.'-'.uniqid() .'.'. $imagen->guessExtension();
                    $directorioAlmacenamiento = $this->getParameter('images_subcategory');
                    $imagen->move($directorioAlmacenamiento, $nombreArchivo);
                    $id->setImagen($nombreArchivo);
                    $entityManager->flush();
                   
        
                }elseif(empty($imagen) || $imagen == null || $imagen = ""  ){
                   
                    $id->setImagen(null);
                    $entityManager->flush();
                }
            }

            $data=[
                'id'=>$id->getId(),
                'nombre'=>$id->getNombre(),
                'image'=>$id->getImagen() ? $domain.$host. '/public/subcategorias/'. $id->getImagen():'',
            ];                          
    

             return $this->errorsInterface->succes_message('Guardado', null, $data);
        }

        
         return $this->errorsInterface->form_errors($form);  
        
    }


    #[Route('/{id}/productos', name:'añadir_productos_subcategoria',methods:['POST'])]
    #[OA\Tag(name: 'Tienda')]
    #[OA\RequestBody(
       description: 'Añade productos a la subcategoria',
       content:  new Model(type: TProductosSubcategoriasType::class)  
    )]
    #[Security(name: 'Bearer')]
    public function addOrEditProducts($id, Request $request, EntityManagerInterface $entityManager): Response
    {
   
       $user = $this->getUser();


       $categoriasTienda = $entityManager->getRepository(SubcategoriasTiendas::class)->findOneBy(['id' => $id]);

       if (!$categoriasTienda) {
           return $this->errorsInterface->error_message('No existe la subcategoria', Response::HTTP_NOT_FOUND);
      }

      $form = $this->createForm(TProductosSubcategoriasType::class);
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
           if (!in_array($producto, haystack: $newProductosArray)) {
               $categoriasTienda->removeProducto($producto);
           }
       }

       $entityManager->flush();

         return $this->errorsInterface->succes_message('Guardado', Response::HTTP_OK);
      }

        return $this->errorsInterface->form_errors($form);
   }


    #[Route('/{id}/delete', name: 'app_subcategorias_tienda_delete', methods: ['DELETE'])]
    #[OA\Tag(name: 'Tienda')]
    #[OA\Response(
       response: 200,
       description: 'Elimina una subcategoria de la tienda',
    )]
    #[Security(name: 'Bearer')]
    public function delete($id, EntityManagerInterface $entityManager): Response
    {
            $subcategoriaTienda= $entityManager->getRepository( SubcategoriasTiendas::class)->find($id);

            if(!$subcategoriaTienda){

                 return $this->errorsInterface->error_message('No existe la subcategoría', Response::HTTP_NOT_FOUND);
            }
            
            $entityManager->remove($subcategoriaTienda);
            $entityManager->flush();

            return $this->errorsInterface->succes_message('Eliminado', Response::HTTP_OK);
    }

     
}

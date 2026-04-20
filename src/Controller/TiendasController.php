<?php

namespace App\Controller;

use App\Entity\GaleriaTienda;
use App\Entity\Tiendas;
use App\Form\CoverTiendaType;
use App\Form\GaleriaTindaType;
use App\Form\TiendasType;
use App\Interfaces\ErrorsInterface;
use App\Repository\TiendasRepository;
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

#[Route('/api/tienda')]
#[OA\Tag(name: 'Tienda')]
#[Security(name: 'Bearer')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class TiendasController extends AbstractController
{
    private $errorsInterface;

    public function __construct(ErrorsInterface $errorsInterface)
    {
        $this->errorsInterface = $errorsInterface;
    }

    #[Route('/mi_tienda', name: 'ver_mi_tienda', methods: ['GET'])]
    #[OA\Tag(name: 'Tienda')]
    #[OA\Response(
        response: 200,
        description: 'Ver una mi tienda.',
    )]
    public function viewTienda(Request $request, TiendasRepository $tiendasRepository, UrlGeneratorInterface $router, EntityManagerInterface $entityManager): Response
    {
    $host = $router->getContext()->getBaseUrl();
    $domain = $request->getSchemeAndHttpHost(); // Asegúrate de que tu controlador tenga el método getRequest() para obtener la solicitud actual.

    $user = $this->getUser();
    $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login'=>$user]);

    if (!$tienda) {
         return $this->errorsInterface->error_message('Tienda no encontrada', Response::HTTP_NOT_FOUND);
    }

    $galeriaArray = [];
    foreach ($tienda->getGaleriaTiendas() as $galeria) {
        $galeriaArray[] = [
            'id' => $galeria->getId(),
            'url' => $galeria->getUrl() ? $domain . $host . '/public/tiendas/' . $galeria->getUrl() : '',
            'seccion' => $galeria->getSeccion(),
        ];
    }

    $galeriasAgrupadas = array_reduce($galeriaArray, function ($resultado, $item) {
        $seccion = $item['seccion'];
        if (!array_key_exists($seccion, $resultado)) {
            $resultado[$seccion] = [];
        }
        $resultado[$seccion][] = $item;
        return $resultado;
    }, []);

    $categoriasArray = [];
    foreach ($tienda->getCategoriasTiendas() as $categoria) {

        $subcategoriasTiendasArray = [];

        foreach ($categoria->getSubcategoriasTiendas() as $subcategoria){

            $subcategoriasTiendasArray[] = [
                'id' => $subcategoria->getId(),
                'nombre' => $subcategoria->getNombre(),
                'image'=> $subcategoria->getImagen() ?  $domain . $host . '/public/subcategorias/' . $subcategoria->getImagen():''
            ];
        }

        $categoriasArray[] = [
            'id' => $categoria->getId(),
            'nombre' => $categoria->getNombre(),
            'slug' => $categoria->getSlug(),
            'banner'=>$categoria->getBanner() ? $domain.$host.'/public/categorias/'.$categoria->getBanner():'',
            'img' => $categoria->getImagen() ? $domain . $host . '/public/categorias/' . $categoria->getImagen() : '',
            'subcategorias'=>$subcategoriasTiendasArray
        ];
    }

    $data = [
        'avatar' => $tienda->getLogin()->getUsuarios() ? $domain . $host . '/public/user/selfie/' . $tienda->getLogin()->getUsuarios()->getAvatar() : '',
        'username' => $tienda->getLogin()->getUsername(),
        'nombre' => $tienda->getLogin()->getUsuarios()->getNombre(),
        'cover' => $tienda->getCover() ? $domain . $host . '/public/tiendas/' . $tienda->getCover() : '',
        'main'=>$tienda->getMain() ? $domain . $host . '/public/tiendas/' . $tienda->getMain() : '',
        'slug' => $tienda->getSlug(),
        'verificado' => $tienda->getEstado() ? $tienda->getEstado()->getNobreEstado() === 'VERIFICADO' : false,
        'galeria' => $galeriasAgrupadas,
        'categorias' => $categoriasArray,
        'visible'=>$tienda->isVisible() ? $tienda->isVisible() :'',
        'contacto'=>[
             'nombre_tienda' => $tienda->getNombreTienda(),
             'descripcion' => $tienda->getDescripcion(),
             'celular' => $tienda->getCelular(),
             'email' => $tienda->getEmail(),
             'ruc_tienda' => $tienda->getRucTienda(),
             'nombre_contacto'=> $tienda->getNombreContacto()
        ]

        ];

        return $this->json($data);
   }

   
   #[Route('/edit', name: 'app_tiendas_edit', methods: ['PUT'])]
   #[OA\Tag(name: 'Tienda')]
   #[OA\RequestBody(
       description: 'Edita información de la tienda del usuario.',
       content: new Model(type: TiendasType::class)
   )]
   public function edit(Request $request, EntityManagerInterface $entityManager): Response
   {
       $user = $this->getUser();
       $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login' => $user]);
   
       if (!$tienda) {
           return $this->errorsInterface->error_message('Tienda no encontrada', Response::HTTP_NOT_FOUND);
       }

       if($tienda->getEstado()->getNobreEstado() !=='VERIFICADO'){
             return $this->errorsInterface->error_message('La tienda no esta verificada', Response::HTTP_BAD_REQUEST);
        }
   
       // Clonamos el objeto tienda para evitar modificaciones directas en la entidad gestionada por Doctrine
       $tiendaClonada = clone $tienda;
   
       // Crea el formulario y aplica los datos al clon
       $form = $this->createForm(TiendasType::class, $tiendaClonada);
       $form->submit(json_decode($request->getContent(), true), false); // Deserializa el JSON y lo envía al formulario
   
       // Validar si el formulario ha sido enviado y si es válido
       if (!$form->isSubmitted()) {
           return $this->errorsInterface->error_message('Formulario no enviado', Response::HTTP_BAD_REQUEST);
       }
   
       if (!$form->isValid()) {
           // Si el formulario no es válido, recopilar errores y devolver un mensaje de error
           return $this->errorsInterface->form_errors($form);
       }
   
       // Si el formulario es válido, aplicamos solo los cambios enviados
       if ($form->get('nombre_tienda')->isSubmitted() && !empty($form->get('nombre_tienda')->getData())) {
           $nombre = $form->get('nombre_tienda')->getData();
           $slug = str_replace(' ', '-', $nombre);
           $slug = strtolower($slug);
           $slug = preg_replace('/[^a-z0-9áéíóúüñ-]/', '', $slug); // Remueve caracteres especiales
           $slug = preg_replace('/-+/', '-', $slug); // Remueve guiones duplicados
           $tienda->setSlug($slug);
           $tienda->setNombreTienda($nombre);
       }
   
       if ($form->get('descripcion')->isSubmitted() && !empty($form->get('descripcion')->getData())) {
           $tienda->setDescripcion($form->get('descripcion')->getData());
       }
   
       if ($form->get('celular')->isSubmitted() && !empty($form->get('celular')->getData())) {
           $tienda->setCelular($form->get('celular')->getData());
       }
   
       if ($form->get('email')->isSubmitted() && !empty($form->get('email')->getData())) {
           $tienda->setEmail($form->get('email')->getData());
       }
   
       if ($form->get('ruc_tienda')->isSubmitted() && !empty($form->get('ruc_tienda')->getData())) {
           $tienda->setRucTienda($form->get('ruc_tienda')->getData());
       }
   
       if ($form->get('nombre_contacto')->isSubmitted() && !empty($form->get('nombre_contacto')->getData())) {
           $tienda->setNombreContacto($form->get('nombre_contacto')->getData());
       }
   
       $entityManager->flush();

        return $this->errorsInterface->succes_message('Tienda actualizada con éxito', Response::HTTP_OK);
   }
   
   

    #[Route('/galeria', name: 'lista_galeria', methods:['GET'])]
    #[OA\Tag(name: 'Tienda')]
    #[OA\Response(
        response: 200,
        description: 'Lista de imagenes para la tienda',
    )]
    public function galeri_tienda_index(Request $request,EntityManagerInterface $entityManager,UrlGeneratorInterface $router): Response
    {
        $user = $this->getUser();
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login'=>$user]);

        if($tienda->getEstado()->getNobreEstado() !=='VERIFICADO'){
             return $this->errorsInterface->error_message('La tienda no esta verificada', Response::HTTP_BAD_REQUEST);
        }

        if (!$tienda) {
            return $this->errorsInterface->error_message('Tienda no encontrada', Response::HTTP_NOT_FOUND);
        }

        $host = $router->getContext()->getBaseUrl();
        $domain = $request->getSchemeAndHttpHost();
    
        $galerias = $entityManager->getRepository(GaleriaTienda::class)->findBy(['tienda' => $tienda]);
    
        $groupedImages = [];
    
        foreach ($galerias as $galeria) {
            $seccion = $galeria->getSeccion();
    
            if (!isset($groupedImages[$seccion])) {
                $groupedImages[$seccion] = [];
            }
    
            $groupedImages[$seccion][] = [
                'id' => $galeria->getId(),
                'url' => $galeria->getUrl() ? $domain . $host . '/public/tiendas/' . $galeria->getUrl() : '',
                'seccion' => $seccion,
            ];
        }
    
        return $this->json($groupedImages)->setStatusCode(Response::HTTP_OK);
    
    }
       

    #[Route('/galeria/new', name: 'añadir_galeria',methods:['POST'])]
    #[OA\Tag(name: 'Tienda')]
    #[OA\RequestBody(
        description: 'Añade una imagen  para la tienda', 
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: new Model(type: GaleriaTindaType::class))
            ),
        ]
    )]
    public function galeria_tienda(Request $request,EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login'=>$user]);

        if($tienda->getEstado()->getNobreEstado() !=='VERIFICADO'){
             return $this->errorsInterface->error_message('La tienda no esta verificada', Response::HTTP_BAD_REQUEST);
        }

        if (!$tienda) {
            return $this->errorsInterface->error_message('Tienda no encontrada', Response::HTTP_NOT_FOUND);
        }


        $galeria= new GaleriaTienda();

        $form = $this->createForm(GaleriaTindaType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            $imagen=$form->get('url')->getData();

            $section= $form->get('seccion')->getData();

            $imgs= $entityManager->getRepository(GaleriaTienda::class)->findBy(['tienda'=>$tienda,'seccion'=>$section]);

            switch ($section) {
                case 'sliders':
                    if (count($imgs) >= 6) {
                        return $this->errorsInterface->error_message('Solo se pueden cargar 6 imágenes en la sección slider', Response::HTTP_BAD_REQUEST);
                    }
                    break;
                case 'promociones':
                    if (count($imgs) >= 4) {
                        return $this->errorsInterface->error_message('Solo se pueden cargar 4 imágenes en la sección promociones', Response::HTTP_BAD_REQUEST);
                    }
                    break;
                case 'banners':
                    if (count($imgs) == 5) {
                        return $this->errorsInterface->error_message('Solo se puede cargar 5 imagenes en la sección banner', Response::HTTP_BAD_REQUEST);
                    }
                    break;
                default:
                    return $this->errorsInterface->error_message('Sección no válida', Response::HTTP_BAD_REQUEST);
            }


            
             
            $slug = str_replace(' ', '-', $tienda->getSlug() ? $tienda->getSlug():'img');


            // Convierte el slug a minúsculas.
            $slug = strtolower($slug);

            // Elimina caracteres especiales y acentos.
            $slug = preg_replace('/[^a-z0-9áéíóúüñ-]/', '', $slug);

             // Elimina guiones duplicados.
            $slug = preg_replace('/-+/', '-', $slug);
            

            if ($imagen instanceof UploadedFile ) {

                $nombreArchivo = $slug.'-'.uniqid() .'.'. $imagen->guessExtension();

                $directorioAlmacenamiento = $this->getParameter('images_tienda');
                $imagen->move($directorioAlmacenamiento, $nombreArchivo);
                $galeria->setTienda($tienda);
                $galeria->setUrl($nombreArchivo);
                $galeria->setSeccion($section);

            }

            $entityManager->persist($galeria);
            $entityManager->flush();

            return $this->errorsInterface->succes_message('Imagen añadida con éxito', Response::HTTP_OK);

        }

         return $this->errorsInterface->form_errors($form);

        
    }

    #[Route('/galeria/edit/{id}', name: 'editar_galeria',methods:['POST'])]
    #[OA\Tag(name: 'Tienda')]
    #[OA\RequestBody(
        description: 'Edita una imagen  para la tienda', 
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: new Model(type: GaleriaTindaType::class))
            ),
        ]
    )]
    public function galeria_edit(Request $request,$id,EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login'=>$user]);
        if($tienda->getEstado()->getNobreEstado() !=='VERIFICADO'){
           return $this->errorsInterface->error_message('La tienda no esta verificada', Response::HTTP_BAD_REQUEST);
        }

        $galeria = $entityManager->getRepository(GaleriaTienda::class)->findOneBy(['id'=>$id,'tienda'=>$tienda]);

        if (!$galeria) {
            return $this->errorsInterface->error_message('Imagen no encontrada', Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(GaleriaTindaType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $img=$form->get('url')->getData();
            $section= $form->get('seccion')->getData();
             
            $slug = str_replace(' ', '-',$galeria->getTienda() ? $galeria->getTienda()->getSlug():'img');

            // Convierte el slug a minúsculas.
            $slug = strtolower($slug);

            // Elimina caracteres especiales y acentos.
            $slug = preg_replace('/[^a-z0-9áéíóúüñ-]/', '', $slug);

             // Elimina guiones duplicados.
            $slug = preg_replace('/-+/', '-', $slug);
            

            if ($img instanceof UploadedFile ) {

                $nombreArchivo = $slug.'-'.uniqid() .'.'. $img->guessExtension();

                $directorioAlmacenamiento = $this->getParameter('images_tienda');
                $img->move($directorioAlmacenamiento, $nombreArchivo);
                $galeria->setUrl($nombreArchivo);
                $galeria->setSeccion($section);

            }

            $entityManager->flush();

            return $this->errorsInterface->succes_message('Imagen editada con éxito', Response::HTTP_OK);

        }

         return $this->errorsInterface->form_errors($form);

    }

    #[Route('/galeria/delete/{id}', name: 'eliminar_galeria', methods:['DELETE'])]
    #[OA\Tag(name: 'Tienda')]
    #[OA\Response(
        response: 200,
        description: 'Elimina una imagen de la tienda',
    )]
    public function galeria_delete($id,EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login'=>$user]);

        if($tienda->getEstado()->getNobreEstado() !=='VERIFICADO'){
             return $this->errorsInterface->error_message('La tienda no esta verificada', Response::HTTP_BAD_REQUEST);
        }

        $galeria = $entityManager->getRepository(GaleriaTienda::class)->findOneBy(['id'=>$id, 'tienda'=>$tienda]);

        if (!$galeria) {
             return $this->errorsInterface->error_message('Imagen no encontrada', Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($galeria);
        $entityManager->flush();

         return $this->errorsInterface->succes_message('Imagen eliminada con éxito', Response::HTTP_OK);
    }


    #[Route('/covers', name: 'add_covers_tienda',methods: ['POST'])]
    #[OA\Tag(name: 'Tienda')]
    #[OA\RequestBody(
        description: 'Añade un  cover o main la tienda', 
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: new Model(type: CoverTiendaType::class))
            ),
        ]
    )]
      public function cover_tienda(Request $request,EntityManagerInterface $entityManager): Response
      {
        $user = $this->getUser();
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login'=>$user]);
        if($tienda->getEstado()->getNobreEstado() !=='VERIFICADO'){
             return $this->errorsInterface->error_message('La tienda no esta verificada', Response::HTTP_BAD_REQUEST);
        }

        $form = $this->createForm(CoverTiendaType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cover= $form->get('cover')->getData();

            $main= $form->get('main')->getData();

            $slug = str_replace(' ', '-', $tienda->getSlug());

            // Convierte el slug a minúsculas.
            $slug = strtolower($slug);

            // Elimina caracteres especiales y acentos.
            $slug = preg_replace('/[^a-z0-9-]/', '', $slug);

             // Elimina guiones duplicados.
            $slug = preg_replace('/-+/', '-', $slug);

            if ($cover instanceof UploadedFile) {
                // Genera un nombre único para el archivo
                $nombreArchivo =  $slug.'-'.uniqid() .'.'. $cover->guessExtension();
                
                // Mueve el archivo al directorio de almacenamiento
                $directorioAlmacenamiento = $this->getParameter('images_tienda');
                $cover->move($directorioAlmacenamiento, $nombreArchivo);
                $tienda->setCover($nombreArchivo);     
               }
               
               if ($main instanceof UploadedFile) {
                
                $nombreArchivo = $slug.'-'.uniqid() .'-cedula.'. $main->guessExtension();      
                $directorioAlmacenamiento = $this->getParameter('images_tienda');
                $main->move($directorioAlmacenamiento, $nombreArchivo);
                $tienda->setMain($nombreArchivo);     

               } 


            $entityManager->flush();

             return $this->errorsInterface->succes_message('Imagen añadida con éxito', Response::HTTP_OK);
        }

         return $this->errorsInterface->form_errors($form); 
    }


    #[Route('/delete_covers', name: 'delete_covers_tienda', methods: ['PATCH'])]
    #[OA\Tag(name: 'Tienda')]
    #[OA\RequestBody(
        description: 'Eliminar un cover o main de la tienda', 
        content: new Model(type: CoverTiendaType::class)
    )]
    public function cover_tienda_delete(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login' => $user]);

        if ($tienda->getEstado()->getNobreEstado() !== 'VERIFICADO') {
             return $this->errorsInterface->error_message('La tienda no está verificada', Response::HTTP_BAD_REQUEST);
        }

        $form = $this->createForm(CoverTiendaType::class);
        
        $form->handleRequest($request);
        

            // Actualizar solo los campos enviados
            if ($form->get('cover')->isSubmitted()) {
                $cover = $form->get('cover')->getData();
                $tienda->setCover($cover);
                $entityManager->persist($tienda);
                $entityManager->flush();
            }

            if ($form->get('main')->isSubmitted()) {
                $main = $form->get('main')->getData();
                $tienda->setMain($main);
                $entityManager->persist($tienda);
                $entityManager->flush();
            }

           return $this->errorsInterface->succes_message('Imagen borrada', Response::HTTP_OK);

    }
  


    #[Route('/visibilidad', name: 'visivilidad_tienda',methods:['POST'])]
    #[OA\Tag(name: 'Tienda')]
    #[OA\Response(
        response:200,
        description: 'Cambiar el estado de la tienda a visible o no visible',
    )]
    public function cambiar_visivilidad(EntityManagerInterface $entityManager): Response
    {
        $user= $this->getUser();

        $tienda= $entityManager->getRepository(Tiendas::class)->findOneBy(['login'=>$user]);

        if($tienda->getEstado()->getNobreEstado() !=='VERIFICADO'){
             return $this->errorsInterface->error_message('La tienda no esta verificada', Response::HTTP_BAD_REQUEST);
        }


        $tienda->setVisible(!$tienda->isVisible());
        $entityManager->flush();

         return $this->errorsInterface->succes_message('Eliminado', Response::HTTP_OK);
    }

      
}

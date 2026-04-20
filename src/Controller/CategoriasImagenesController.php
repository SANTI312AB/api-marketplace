<?php

namespace App\Controller;

use App\Entity\Categorias;
use App\Entity\ProductosMarcas;
use App\Form\CategoriasImagenesType;
use App\Form\MarcasImagenType;
use App\Interfaces\ErrorsInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;


class CategoriasImagenesController extends AbstractController
{
    private $errorsInterface;

    public function __construct(ErrorsInterface $errorsInterface)
    {
        $this->errorsInterface = $errorsInterface;
    }

    #[Route('/categorias/{id}/banner', name: 'añadir_baner_categoria', methods:['POST'])]
    #[OA\Tag(name: 'AdminUrl')]
    #[OA\RequestBody(
        description: 'Añade banner a categoria',
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(properties: [
                    new OA\Property(
                        property: 'banner',
                        type: 'file',
                    ),
                ])
            ),
        ]
    )]
    public function banner(Request $request,$id, EntityManagerInterface $entityManager): Response
    {
        $categorias= $entityManager->getRepository(Categorias::class)->find($id);

        if(empty($categorias)){
            return $this->errorsInterface->error_message('No existe esta categoría', Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(CategoriasImagenesType::class);
        $form->handleRequest($request);

    
        if ($form->isSubmitted() && $form->isValid()) {
            $banner = $form->get('banner')->getData();

            if(empty($banner)) {
                return $this->errorsInterface->error_message('Por favor, suba una imagen.', Response::HTTP_BAD_REQUEST);
            }
    
            $slug = str_replace(' ', '-', $categorias->getNombre());
    
            // Convierte el slug a minúsculas.
            $slug = strtolower($slug);
    
            // Elimina caracteres especiales y acentos.
            $slug = preg_replace('/[^a-z0-9áéíóúüñ-]/', '', $slug);
    
            // Elimina guiones duplicados.
            $slug = preg_replace('/-+/', '-', $slug);
    
            if ($banner instanceof UploadedFile && !empty($banner)) {
                $nombreArchivo = $slug.'-'.uniqid().'.'.$banner->guessExtension();
    
                $directorioAlmacenamiento = $this->getParameter('images_category');
                $banner->move($directorioAlmacenamiento, $nombreArchivo);
                $categorias->setBanner($nombreArchivo);
            }
    
            $entityManager->flush();
    
            return $this->errorsInterface->succes_message(
                'Banner añadido'
            );
        }
    
        $errors = [];
        foreach ($form->getErrors(true, true) as $error) {
            $propertyName = $error->getOrigin()->getName();
            $errors[$propertyName][] = $error->getMessage();
        }
    

       if(empty($form->get('banner')->getData())){
           $message = 'Por favor, suba una imagen.';
       }else{
           $message = '';
       }

        return $this->errorsInterface->error_message(
            $message.' '.$errors[$propertyName][0],
            Response::HTTP_BAD_REQUEST
        );
    }

    #[Route('/categorias/{id}/slider', name: 'añadir_imagen_categoria',methods:['POST'])]
    #[OA\Tag(name: 'AdminUrl')]
    #[OA\RequestBody(
        description: 'Añade slider a categoria',
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(properties: [
                    new OA\Property(
                        property: 'slider',
                        type: 'file',
                    ),
                ])
            ),
        ]
    )]
    public function imagen(Request $request,$id,EntityManagerInterface $entityManager): Response
    {
        $categorias= $entityManager->getRepository(Categorias::class)->find($id);

        if(empty($categorias)){
            return $this->errorsInterface->error_message('No existe esta categoría', Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(CategoriasImagenesType::class);
        $form->handleRequest($request);

    
        if ($form->isSubmitted() && $form->isValid()) {
            
            $banner = $form->get('slider')->getData();

            if(empty($banner)) {
                return $this->errorsInterface->error_message('Por favor, suba una imagen.', Response::HTTP_BAD_REQUEST);
            }
    
            $slug = str_replace(' ', '-', $categorias->getNombre());
    
            // Convierte el slug a minúsculas.
            $slug = strtolower($slug);
    
            // Elimina caracteres especiales y acentos.
            $slug = preg_replace('/[^a-z0-9áéíóúüñ-]/', '', $slug);
    
            // Elimina guiones duplicados.
            $slug = preg_replace('/-+/', '-', $slug);
    
            if ($banner instanceof UploadedFile && !empty($banner)) {
                $nombreArchivo = $slug.'-'.uniqid().'.'.$banner->guessExtension();
    
                $directorioAlmacenamiento = $this->getParameter('images_category');
                $banner->move($directorioAlmacenamiento, $nombreArchivo);
                $categorias->setImg($nombreArchivo);
            }
    
            $entityManager->flush();

            return $this->errorsInterface->succes_message('Imagen añadida');
        }
    
        $errors = [];
        $propertyName = null;
        foreach ($form->getErrors(true, true) as $error) {
            $propertyName = $error->getOrigin()->getName();
            $errors[$propertyName][] = $error->getMessage();
        }
    
        $message = '';
        if (empty($form->get('slider')->getData())) {
            
            $message = 'Por favor, suba una imagen.';
        }
    
        if (isset($errors[$propertyName])) {
            $message .= ' ' . $errors[$propertyName][0];
        }

        return $this->errorsInterface->error_message($message, Response::HTTP_BAD_REQUEST);
        
    }

     #[Route('/marcas/{id}/logo', name: 'añadir_imagen_marca',methods:['POST'])]
     #[OA\Tag(name: 'AdminUrl')]
     #[OA\RequestBody(
        description: 'Añade logo a una marca',
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(properties: [
                    new OA\Property(
                        property: 'logo',
                        type: 'file',
                    ),
                ])
            ),
        ]
     )]
     public function img_marcas(Request $request,$id,EntityManagerInterface $entityManager): Response
     {
        $productosMarcas= $entityManager->getRepository(ProductosMarcas::class)->find($id);

        if(empty($productosMarcas)){
            return $this->errorsInterface->error_message('No existe esta Marca', Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(MarcasImagenType::class);
        $form->handleRequest($request);

    
        if ($form->isSubmitted() && $form->isValid()) {
            $logo = $form->get('logo')->getData();

            if(empty($logo)) {
                return $this->errorsInterface->error_message('Por favor, suba una imagen.', Response::HTTP_BAD_REQUEST);
            }
    
            $slug = str_replace(' ', '-', $productosMarcas->getNombreM());
    
            // Convierte el slug a minúsculas.
            $slug = strtolower($slug);
    
            // Elimina caracteres especiales y acentos.
            $slug = preg_replace('/[^a-z0-9áéíóúüñ-]/', '', $slug);
    
            // Elimina guiones duplicados.
            $slug = preg_replace('/-+/', '-', $slug);
    
            if ($logo instanceof UploadedFile && !empty($logo)) {
                $nombreArchivo = $slug.'-'.uniqid().'.'.$logo->guessExtension();
    
                $directorioAlmacenamiento = $this->getParameter('images_marca');
                $logo->move($directorioAlmacenamiento, $nombreArchivo);
                $productosMarcas->setLogo($nombreArchivo);
            }
    
            $entityManager->flush();

           return $this->errorsInterface->succes_message('Imagen añadida');
        }
    
        $errors = [];
        foreach ($form->getErrors(true, true) as $error) {
            $propertyName = $error->getOrigin()->getName();
            $errors[$propertyName][] = $error->getMessage();
        }
    

       if(empty($form->get('logo')->getData())){
           $message = 'Por favor, suba una imagen.';
       }else{
           $message = '';
       }

        if (isset($errors[$propertyName])) {
         // Si hay errores, concatenar el mensaje de error al mensaje principal
         $message .= ' ' . $errors[$propertyName][0];
     } else {
         // Si no hay errores, usar el mensaje principal
         $message = 'Por favor, suba una imagen.';
     }

        return $this->errorsInterface->error_message($message, Response::HTTP_BAD_REQUEST);
     }
}

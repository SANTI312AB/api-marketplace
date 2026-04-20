<?php

namespace App\Controller;

use App\Entity\Login;
use App\Entity\Productos;
use App\Entity\Usuarios;
use App\Entity\UsuariosDirecciones;
use App\Form\UsuariosDireccionesType;
use App\Repository\EstadosRepository;
use App\Repository\UsuariosDireccionesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use App\Interfaces\ErrorsInterface;

#[Route('/api/direcciones')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class UsuariosDireccionesController extends AbstractController
{
    private $errorsInterface;
    public function __construct(ErrorsInterface $errorsInterface){
        $this->errorsInterface= $errorsInterface;

    }

    #[Route('/all', name: 'app_usuarios_direcciones_full', methods: ['GET'])]
    #[OA\Tag(name: 'Login')]
    #[OA\Response(
        response: 200,
        description: 'Lista todas las direcciones del usario'
    )]
    #[Security(name: 'Bearer')]
    public function full(UsuariosDireccionesRepository $usuariosDireccionesRepository,EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if(!$user instanceof Login){
            return $this->json([
                'error'=>'No estas logueado'
            ])->setStatusCode(Response::HTTP_UNAUTHORIZED);
        }
        $id= $user->getId();
        $usuario= $entityManager->getRepository(Usuarios::class)->findOneBy(['login'=>$id]);
        $direcciones = $usuariosDireccionesRepository->findBy(['usuario'=>$usuario],['fecha_creacion' => 'DESC']);
        $direccionesArray=[];
        foreach ($direcciones as $direccion) {
            $direccionesArray[] = [

                'id' => $direccion->getId(),
                'ciudad'=>$direccion->getCiudad()->getCiudad(),
                'id_ciudad'=>$direccion->getCiudad()->getId(),
                'provincia'=>$direccion->getCiudad()->getProvincia()->getProvincia(),
                'id_provincia'=>$direccion->getCiudad()->getProvincia()->getId(),
                'direccion_p' => $direccion->getDireccionP(),
                'direccion_s'=>$direccion->getDireccionS(),
                'codigo_postal'=>$direccion->getCodigoPostal(),
                'etiqueta_direccion'=>$direccion->getEtiquetaDireccion(),
                'referencia_direccion'=>$direccion->getReferenciaDireccion(),
                'fecha_creacion'=>$direccion->getFechaCreacion(),
                'n_casa'=>$direccion->getNCasa(),
                'estado'=>$direccion->getEstado()->getId(),
                'nombre_estado'=>$direccion->getEstado()->getNobreEstado(),
                'latitud'=>$direccion->getLatitud() ? $direccion->getLatitud() :null,
                'longitud'=>$direccion->getLongitud() ? $direccion->getLongitud() :null,
                'observacion'=>$direccion->getObservacion()
            ];
        }
       
            return $this->json($direccionesArray);
        
    }

    #[Route('/filter/{id}', name: 'app_usuarios_direcciones_filter', methods: ['GET'])]
    #[OA\Tag(name: 'Login')]
    #[OA\Response(
        response: 200,
        description: 'Lista una sola direccion por id'
    )]
    #[Security(name: 'Bearer')]
    public function parametrado(UsuariosDireccionesRepository $usuariosDireccionesRepository,EntityManagerInterface $entityManager,UsuariosDirecciones $usuariosDireccione): Response
    {
        $user = $this->getUser();
        if(!$user instanceof Login){
            return $this->json([
                'error'=>'No estas logueado'
            ])->setStatusCode(Response::HTTP_UNAUTHORIZED);
        }
        $id= $user->getId();
        $usuario= $entityManager->getRepository(Usuarios::class)->findOneBy(['login'=>$id]);
        $direcciones = $usuariosDireccionesRepository->findBy(['usuario'=>$usuario],['id'=>$usuariosDireccione]);
        $direccionesArray=[];
        foreach ($direcciones as $direccion) {
            $direccionesArray[] = [

                'id' => $direccion->getId(),
                'ciudad'=>$direccion->getCiudad()->getCiudad(),
                'id_ciudad'=>$direccion->getCiudad()->getId(),
                'provincia'=>$direccion->getCiudad()->getProvincia()->getProvincia(),
                'id_provincia'=>$direccion->getCiudad()->getProvincia()->getId(),
                'direccion_p' => $direccion->getDireccionP(),
                'direccion_s'=>$direccion->getDireccionS(),
                'codigo_postal'=>$direccion->getCodigoPostal(),
                'etiqueta_direccion'=>$direccion->getEtiquetaDireccion(),
                'referencia_direccion'=>$direccion->getReferenciaDireccion(),
                'fecha_creacion'=>$direccion->getFechaCreacion(),
                'n_casa'=>$direccion->getNCasa(),
                'estado'=>$direccion->getEstado()->getId(),
                'nombre_estado'=>$direccion->getEstado()->getNobreEstado(),
                'latitud'=>$direccion->getLatitud() ? $direccion->getLatitud() :null,
                'longitud'=>$direccion->getLongitud() ? $direccion->getLongitud() :null,
                'observacion'=>$direccion->getObservacion()
            ];
        }
            return $this->json($direccionesArray);       
    }

    #[Route('/new', name: 'app_usuarios_direcciones_new', methods: ['POST'])]
    #[OA\Tag(name: 'Login')]
    #[OA\RequestBody(
        description: 'Añade una direccion para el usuario',
        content: new  Model(type: UsuariosDireccionesType::class)
    )]
    #[Security(name: 'Bearer')]
    public function new(Request $request, EntityManagerInterface $entityManager,EstadosRepository $estadosRepository): Response
    {
        $user = $this->getUser();
        if(!$user instanceof Login){
            return $this->errorsInterface->error_message('No estas logueado',Response::HTTP_UNAUTHORIZED);
            
        }
        $id= $user->getId();
        $usuario= $entityManager->getRepository(Usuarios::class)->findOneBy(['login'=>$id]);
        $estado= $estadosRepository->findOneBy(['id'=>13]);
        $direcciones= new UsuariosDirecciones();

        $form = $this->createForm(UsuariosDireccionesType::class,$direcciones);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $direcciones->setEstado($estado);
            $direcciones->setUsuario($usuario);
            $entityManager->persist($direcciones);
            $entityManager->flush();

            $data=[
                'id' => $direcciones->getId(),
                'id_ciudad'=> $direcciones->getCiudad()->getId()
            ];

            return $this->errorsInterface->succes_message('Guardado',null, $data);

            
        }

         return $this->errorsInterface->form_errors($form);
    }


    #[Route('/edit/{id}', name: 'app_usuarios_direcciones_edit', methods: ['PUT'])]
    #[OA\Tag(name: 'Login')]
    #[OA\RequestBody(
        description: 'Edita una direccion del  usuario',
        content: new  Model(type: UsuariosDireccionesType::class)
    )]
    #[Security(name: 'Bearer')]
    public function edit(Request $request, UsuariosDirecciones $usuariosDireccione, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UsuariosDireccionesType::class, $usuariosDireccione);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->flush();

            return $this->errorsInterface->succes_message('Editado','id_direccion',$usuariosDireccione->getId());
        }

        return $this->errorsInterface->form_errors($form);
    
    }

    #[Route('/delete/{id}', name: 'app_usuarios_direcciones_delete', methods: ['DELETE'])]
    #[OA\Tag(name: 'Login')]
    #[OA\Response(
        response: 200,
        description: 'Elimina una direccion del el usuario',
    )]
    #[Security(name: 'Bearer')]
    public function delete(Request $request, $id, EntityManagerInterface $entityManager): Response
    {
        $usuariosDireccione = $entityManager->getRepository(UsuariosDirecciones::class)->find($id);
        
        $producto = $entityManager->getRepository(Productos::class)->findBy(['direcciones'=>$usuariosDireccione]);

        if (!empty($producto)) {
            // Puedes agregar aquí la lógica para notificar al usuario sobre los pedidos pendientes
       
             return $this->errorsInterface->error_message('No puedes eliminar una dirección que haya sido asignada en algún producto.',Response::HTTP_BAD_REQUEST);
        }

            $entityManager->remove($usuariosDireccione);
            $entityManager->flush();
    

        return $this->errorsInterface->succes_message('Eliminado');

    }
    
}

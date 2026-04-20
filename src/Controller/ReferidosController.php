<?php

namespace App\Controller;

use App\Entity\Cupon;
use App\Entity\Login;
use App\Entity\Pedidos;
use App\Entity\Tiendas;
use App\Entity\Usuarios;
use App\Form\ReferidosType;
use App\Interfaces\ErrorsInterface;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/cupones')]
#[OA\Tag(name: 'Cupones')]
#[Security(name: 'Bearer')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
Class ReferidosController extends AbstractController
{

    private $em;
    private $request;
    private $errorsInterface;

    private $mailer;
    public function __construct(EntityManagerInterface $em,RequestStack $request, ErrorsInterface $errorsInterface,MailerInterface $mailer){
        $this->em = $em;
        $this->request = $request->getCurrentRequest();
        $this->errorsInterface = $errorsInterface;
    }
    
    #[Route('/', name: 'app_cupones',methods:['GET'])]
    #[OA\Parameter(
        name: "tipo_cupon",
        in: "query",
        description: "Buscar productos por nombre,categoria,subcategoria,tienda y marca."
    )]
    public function index(Request $request): Response
    {
        $user= $this->getUser();
        $id_tienda=null;

        if($user->getTiendas() instanceof Tiendas){
             $id_tienda= $user->getTiendas();
        }

        $allowedParams = [
            'tipo_cupon'
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

        $tipo = $request->query->get('tipo_cupon');

        $cupones= $this->em->getRepository(Cupon::class)->cupones_referido($id_tienda,$tipo);
        $data=[];
        foreach($cupones as $cupon){
            $data_email=[];
            foreach($cupon->getLogin() AS $login){
                 $data_email[]=[
                    'email'=>$login->getEmail()
                 ];
            }

            $data[]=[
                'id'=>$cupon->getId(),
                'valor_descuento'=>$cupon->getValorDescuento(),
                'codigo_cupon' => $cupon->getCodigoCupon(),
                'tipo_descuento'=>$cupon->getTipoDescuento(),
                'tipo_cupon'=>$cupon->getTipo(),
                'limite_uso'=>$cupon->getLimiteUso(),
                'descripcion'=>$cupon->getDescripcion(),
                'activo' => $cupon->isActivo(),
                'login'=>$data_email
            ];
        }
        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/', name: 'app_cupon_new', methods:['POST'])]
    #[OA\RequestBody(
        description: 'Crear un referido.',
        content: new  Model(type: ReferidosType::class)
    )]
    public function new(): Response
    {
         $user= $this->getUser();
         $valor_referido=null;
         if($user instanceof Login){
            $valor_referido= $user->getUsuarios()->getReferido();
         }

         if (!$valor_referido){
             return $this->errorsInterface->error_message('El usuario no tiene ningún valor de referido asignado.',Response::HTTP_CONFLICT);
         }

         $id_tienda=null;

         if($user->getTiendas() instanceof Tiendas){
             $id_tienda= $user->getTiendas();
         }
         $cupon= new Cupon();
         $form= $this->createForm(ReferidosType::class,$cupon);
         $form->handleRequest($this->request);
         if ($form->isSubmitted() && $form->isValid()) {

             $codigo= $id_tienda->getSlug().substr(uniqid(), 0, 5);
             $cupon->setCodigoCupon($codigo);
             $cupon->setActivo(false);
             $cupon->setFechaInicio(fecha_inicio: new DateTime());
             $cupon->setTipo('TIENDA');
             $cupon->setValorDescuento($valor_referido);
             $cupon->setTienda($$id_tienda);
             $this->em->persist($cupon);
             $this->em->flush();

              return $this->errorsInterface->succes_message('Cupón de una tienda creado.');
         }

         return $this->errorsInterface->form_errors($form);

    }

    #[Route('/referido', name: 'app_cupon_referido', methods:['POST'])]
    public function referido(): Response
    {
        $user = $this->getUser();

        $valor_referido = null;
        if ($user instanceof Login) {
            $valor_referido = $user->getUsuarios()->getReferido();
        }

        if (!$valor_referido){
             return $this->errorsInterface->error_message('El usuario no tiene ningún valor de referido asignado.',Response::HTTP_CONFLICT);
        }

        $id_tienda = null;
        if ($user->getTiendas() instanceof Tiendas) {
            $id_tienda = $user->getTiendas();
        }

        $usuario= $user->getUsuarios();

        if (!$usuario instanceof Usuarios) {
            return $this->errorsInterface->error_message('El usuario no existe.',Response::HTTP_BAD_REQUEST,'description','El usuario no existe en la base de datos.');
        }

        $nombre = $usuario->getNombre();
        $primeras_letras = mb_substr($nombre, 0, 2);
        $email = $usuario->getEmail();

        $camposFaltantes = [];

        // Verificar cada campo individualmente y agregar el nombre del campo a $camposFaltantes si está vacío
        if (!$nombre) {
            $camposFaltantes[] = 'nombre';
        }
        if (!$email) {
            $camposFaltantes[] = 'email';
        }

        // Verificar si hay campos faltantes
        if (!empty($camposFaltantes)) {
            $mensajeError = 'Completa la informacion de tu perfil para publicar un producto.';
            $error[] = [
                'description' => 'Los siguientes campos están vacíos: ' . implode(', ', $camposFaltantes),
            ];
            
            return$this->errorsInterface->error_message(
                $mensajeError,
                417, // O Response::HTTP_EXPECTATION_FAILED si está usando constantes
                null,
                $error
            );
        }

        // Verificar si ya existe un cupón de referido para esta tienda
        $cupon_referido = $this->em->getRepository(Cupon::class)->findOneBy([
            'tienda' => $id_tienda,
            'tipo' => 'REFERIDO'
        ]);
        if ($cupon_referido) {
            $data = [
            'id' => $cupon_referido->getId(),
            'valor_descuento' => $cupon_referido->getValorDescuento(),
            'codigo_cupon' => $cupon_referido->getCodigoCupon(),
            'tipo_descuento' => $cupon_referido->getTipoDescuento(),
            'tipo_cupon'=>$cupon_referido->getTipo(),
            'limite_uso' => $cupon_referido->getLimiteUso(),
            'descripcion' => $cupon_referido->getDescripcion(),
            'activo' => $cupon_referido->isActivo()
        ];

            return $this->errorsInterface->error_message('Ya existe un cupón de referido para esta tienda.', Response::HTTP_CONFLICT,'cupon',$data);
        }


        $cupon = new Cupon();
        $codigo = strtoupper($primeras_letras . substr(uniqid(), 0, 5));
        $cupon->setCodigoCupon($codigo);
        $cupon->setTipoDescuento('PORCENTAJE');
        $cupon->setActivo(true);
        $cupon->setLimiteUso(10);
        $cupon->setFechaInicio(new DateTime());
        $cupon->setTipo('REFERIDO');
        $cupon->setValorDescuento($valor_referido);
        $cupon->setTienda($id_tienda);
        $this->em->persist($cupon);
        $this->em->flush();

         $data = [
            'id' => $cupon->getId(),
            'valor_descuento' => $cupon->getValorDescuento(),
            'codigo_cupon' => $cupon->getCodigoCupon(),
            'tipo_descuento' => $cupon->getTipoDescuento(),
            'tipo_cupon'=>$cupon->getTipo(),
            'limite_uso' => $cupon->getLimiteUso(),
            'descripcion' => $cupon->getDescripcion(),
            'activo' => $cupon->isActivo()
        ];
        return $this->errorsInterface->succes_message('Cupón de referidos creado.', 'cupon',$data);
    }


    #[Route('/referido/estado/{id}', name: 'app_enable_disable_referido', methods:['POST'])]
    public function action($id=null): Response
    {   
         if(!$id){
              return $this->errorsInterface->error_message('No hay parametro.', Response::HTTP_BAD_REQUEST);
         }

        $referido = $this->em->getRepository(Cupon::class)->findOneBy(['id' => $id, 'tipo' => 'REFERIDO']);
        if(!$referido){
            return $this->errorsInterface->error_message('No existe el referido.', Response::HTTP_NOT_FOUND);
        }     
        
        $referido->setActivo(!$referido->isActivo());
        $this->em->flush();

        $estado = $referido->isActivo() ? 'habilitado' : 'deshabilitado';
        return $this->errorsInterface->succes_message("Cupón de referidos $estado.");
    }



    #[Route('/{id}', name: 'app_cupon_edit', methods: ['PATCH'])]
    #[OA\RequestBody(
        description: 'Crear un referido.',
        content: new  Model(type: ReferidosType::class)
    )]
    public function edit($id=null): Response
    {
        if (!$id) {
            return $this->errorsInterface->error_message('Parametro no proporcionado.', Response::HTTP_BAD_REQUEST);
        }

        $user= $this->getUser();
        $id_tienda=null;

        if($user->getTiendas() instanceof Tiendas){
             $id_tienda= $user->getTiendas();
        }

        $v_cupon= $this->em->getRepository(Cupon::class)->cupon_referido($id_tienda,$id);
        if (!$v_cupon) {
            return $this->errorsInterface->error_message('Cupón no encontrado', Response::HTTP_NOT_FOUND);
        }

        $pedidos_cupon= $this->em->getRepository(Pedidos::class)->findBy(['cupon'=>$v_cupon]);
        if ($pedidos_cupon) {
            return $this->errorsInterface->error_message('No se puede eliminar el cupón , que tenga pedidos relacionados.', Response::HTTP_CONFLICT);
        }

        $form = $this->createForm(ReferidosType::class, $v_cupon);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
            $v_cupon->setUpdateAt(new DateTimeImmutable());
            $this->em->flush();

             return $this->errorsInterface->succes_message('Cupón de una tienda actualizado');
        }

        return $this->errorsInterface->form_errors($form);
    }

    #[Route('/{id}', name: 'app_cupon_delete', methods: ['DELETE'])]
    public function delete($id=null): Response
    {
        if (!$id) {
            return $this->errorsInterface->error_message('Parametro no proporcionado.', Response::HTTP_BAD_REQUEST);
        }

        $user= $this->getUser();

        $id_tienda=null;

        if($user->getTiendas() instanceof Tiendas){
             $id_tienda= $user->getTiendas();
        }

        $v_cupon= $this->em->getRepository(Cupon::class)->cupon_referido($id_tienda,$id);

        $pedidos_cupon= $this->em->getRepository(Pedidos::class)->findBy(['cupon'=>$v_cupon]);
        if ($pedidos_cupon) {
            return $this->errorsInterface->error_message('No se puede eliminar el cupón, ya que hay pedidos relacionados.', Response::HTTP_CONFLICT);
        }
        if (!$v_cupon) {
            return $this->errorsInterface->error_message('Cupón no encontrado', Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($v_cupon);
        $this->em->flush();

         return $this->errorsInterface->succes_message('Cupón de referido eliminado');
    }
}

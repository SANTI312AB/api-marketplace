<?php

namespace App\Controller;

use App\Entity\Ofertas;
use App\Entity\Pedidos;
use App\Entity\Productos;
use App\Entity\Tiendas;
use App\Entity\Usuarios;
use App\Entity\UsuariosDirecciones;
use App\Interfaces\ErrorsInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;

class PedidoOfertaController extends AbstractController
{
    private $errorsInterface;

    public function __construct(ErrorsInterface $errorsInterface)
    {
        $this->errorsInterface = $errorsInterface;
    }

    #[Route('/api/pedido/oferta/{id}/placetopay', name:'pedido_oferta_placeto_pay',methods:['POST'])]
    #[OA\Tag(name: 'Ofertas')]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function ofertas_placeto_pay($id,EntityManagerInterface $entityManager): Response
    {
        $user= $this->getUser();
        $oferta = $entityManager->getRepository(Ofertas::class)->findOneBy(['id'=>$id,'login'=>$user]);
        
        if (!$oferta) {
           return $this->errorsInterface->error_message('La oferta no existe', Response::HTTP_NOT_FOUND);
        }

        $tienda= $entityManager->getRepository(Tiendas::class)->findOneBy(['id'=>$oferta->getSubasta()->getIdProducto()->getTienda()->getId()]);
       

        $usuario= $entityManager->getRepository(Usuarios::class)->findOneBy(['login'=>$oferta->getLogin()->getId()]);
        
        if (!$usuario) {
            return $this->errorsInterface->error_message('El usuario no se encontró en la base de datos.', Response::HTTP_BAD_REQUEST);
        }
            $nombre = $usuario->getNombre() ? $usuario->getNombre() : '';
            $apellido = $usuario->getApellido() ? $usuario->getApellido():'';
            $email = $usuario->getEmail();
            $documento = $usuario->getTipoDocumento();
            $telefono = $usuario->getCelular();
            $dni = $usuario->getDni();
        
            // Verificar cada campo individualmente
        
            $camposFaltantes = [];

// Verificar cada campo individualmente y agregar el nombre del campo a $camposFaltantes si está vacío
       if (!$nombre) {
          $camposFaltantes[] = 'nombre';
        }

        if (!$email) {
           $camposFaltantes[] = 'email';
        }
       if (!$documento) {
             $camposFaltantes[] = 'documento';
       }
       if (!$telefono) {
            $camposFaltantes[] = 'teléfono';
       }
      if (!$dni) {
          $camposFaltantes[] = 'DNI';
      }



     if (!empty($camposFaltantes)) {
         $mensajeError = 'Faltan los siguientes datos para proceder con la compra: ' . implode(', ', $camposFaltantes);
         return $this->errorsInterface->error_message($mensajeError, 417);
    }



       $direcciones =$entityManager->getRepository(UsuariosDirecciones::class)->findOneBy(['usuario' => $usuario], ['fecha_creacion' => 'DESC']);

       if ($direcciones !== null) {

        $ciudad_usuario = $direcciones->getCiudad() ? $direcciones->getCiudad()->getCiudad() : null;
        $provincia_usuario = $direcciones->getCiudad() ? $direcciones->getCiudad()->getProvincia()->getProvincia() : null;
        $region_usuario = $direcciones->getCiudad() ? $direcciones->getCiudad()->getProvincia()->getRegion() : null;
        $direccion_principl = $direcciones->getDireccionP() ? $direcciones->getDireccionP():'';
        $direccion_secundaria = $direcciones->getDireccionS()? $direcciones->getDireccionS():'';
        $referencia_direccion = $direcciones->getReferenciaDireccion() ? $direcciones->getReferenciaDireccion():'';
        $id_direccion= $direcciones->getCiudad()->getId();
       
      }


        $n_venta='V-'.rand(0000,9999);
        $numero_pedido='PED-001-'.rand(0000,9999);


        $pedido= new Pedidos();
        $pedido->setLogin($user);
        $pedido->setNVenta($n_venta);
        $pedido->setNumeroPedido($numero_pedido);
        $pedido->setTienda($tienda);
        $pedido->setCustomer($nombre.'-'.$apellido);
        $pedido->setDniCustomer($dni);
        $pedido->setCelularCustomer($telefono);
        $pedido->setCustomerCity($ciudad_usuario);
        $pedido->setProvincia($provincia_usuario);
        $pedido->setRegion($region_usuario);
        $pedido->setDireccionPrincipal($direccion_principl);
        $pedido->setDireccionSecundaria($direccion_secundaria);
        $pedido->setReferenciaPedido($referencia_direccion);
        $pedido->setIdDireccion($id_direccion);
        $entityManager->persist($pedido);
        $entityManager->flush();


        return $this->errorsInterface->succes_message('Success');
    }
}

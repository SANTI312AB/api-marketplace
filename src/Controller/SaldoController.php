<?php

namespace App\Controller;

use App\Entity\Cupon;
use App\Entity\Ganancia;
use App\Entity\Login;
use App\Entity\Pedidos;
use App\Entity\Productos;
use App\Entity\Recargas;
use App\Entity\Retiros;
use App\Entity\Saldo;
use App\Entity\Tiendas;
use App\Form\RecargaType;
use App\Interfaces\ErrorsInterface;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\RequestStack;

final class SaldoController extends AbstractController
{

    private $em;
    private $request;

    private $errorsInterface;

    public function __construct(EntityManagerInterface $em,RequestStack $request, ErrorsInterface $errorsInterface ){
        $this->em = $em;  // Injecting EntityManager into the controller.
        $this->request = $request->getCurrentRequest();  // Injecting RequestStack into the controller.
        $this->errorsInterface = $errorsInterface;
    }

    #[Route('/api/saldo', name: 'app_saldo', methods:['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Tag(name: 'Saldo')]
    #[OA\Response(
        response: 200,
        description: 'Devuelve el saldo del usuario actual',
    )]
    #[Security(name: "Bearer")]
    public function saldo(): Response
    {
        $user = $this->getUser();
        $saldo= $this->em->getRepository(Saldo::class)->findOneBy(['login'=>$user]);
        $data=[];
        if($saldo){
            $total_recargas= count($saldo->getRecargas());
            $total_referidos= $this->calculo_saldo_referido($user);
            $data=[
                'saldo'=>$saldo->getSaldo() ? $saldo->getSaldo():0,
                'referidos_usados'=>$total_referidos,
                'recargas'=>$total_recargas
            ];    
        }else{

            $data=[
                'saldo'=>0,
                'referidos_usados'=>0,
                'recargas'=>0
            ];
        }
    
        return $this->json($data);
    
    }

    public function calculo_saldo_referido(Login $user){

        $tienda= $this->em->getRepository(Tiendas::class)->findOneBy(['login'=>$user]);
        $pedidos_cupon_referido= $this->em->getRepository(Pedidos::class)->pedidos_cupon_referidos($tienda);
        $total_referidos= count($pedidos_cupon_referido);
        return $total_referidos;
    }

    #[Route('/api/recargas', name: 'app_obtener_recargas',methods:['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Tag(name: 'Saldo')]
    #[OA\Response(
        response: 200,
        description: 'Devuelve el historial de recargas.'
    )]
    #[Security(name: "Bearer")]
    public function recargas(): Response
    {
        $user = $this->getUser();
        $saldo= $this->em->getRepository(Saldo::class)->findOneBy(['login'=>$user]);
        if (!$saldo) {
            return $this->errorsInterface->error_message('Saldo no encontrado', Response::HTTP_NOT_FOUND);
        }
        $recargaArray=[];

        foreach($saldo->getRecargas() as $recarga){

            $valor_retiro= $recarga->getRetiro()? $recarga->getRetiro()->getRetiro():null;
            $valor_giftcard= $recarga->getProducto() ? $recarga->getProducto()->getPrecioNormalProducto():null;

            $recargaArray []= [
                'fecha' => $recarga->getFecha(),
                'valor'=>$valor_retiro ?? $valor_giftcard,
                'tipo_recarga'=> $recarga->getTipoRecarga()
            ];

        }

        return $this->json($recargaArray, Response::HTTP_OK);
    }

    #[Route('/api/recarga', name: 'app_recargar_saldo', methods:['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Tag(name: 'Saldo')]
    #[OA\RequestBody(
        description: 'Formulario de seleccion de tipo de recarga.',
        content: new  Model(type: RecargaType::class)
    )]
    #[Security(name: "Bearer")]
    public function add_recarga(): Response
    {
        $user=$this->getUser();

        if($user instanceof Login){
            $saldo= $user->getSaldo();
            $ganancia=$user->getGanancia();
    
        }
        if (!$saldo){
            $saldo =new Saldo();
            $saldo->setLogin($user);
            $this->em->persist($saldo);
            $this->em->flush();
        }

        $tienda= $this->em->getRepository(Tiendas::class)->findOneBy(['login'=>$user]);

        $gananciasVendedor = $this->em->getRepository(Ganancia::class)->findOneBy(['login' => $user]);

        $valor_retiro = $gananciasVendedor->getDisponible() ? $gananciasVendedor->getDisponible():0;

        $comision=$tienda->getComision();

        $form = $this->createForm(RecargaType::class);
        $form->handleRequest($this->request);

        if (!$form->isValid()) {

            return $this->errorsInterface->form_errors($form);

        }

        $tipo_ganancia=$form->get('tipo_recarga')->getData();

        if($tipo_ganancia == 'TRANSACCIONES'){

            $retiros_pendinetes = $this->em->getRepository(Retiros::class)->findBy(['estado' => 'PENDING', 'ganancia' => $gananciasVendedor]);

            if (!empty($retiros_pendinetes)) {
                return $this->errorsInterface->error_message('Hay retiros pendientes', Response::HTTP_BAD_REQUEST);
            }
            
            if (!$ganancia){
                return $this->errorsInterface->error_message('No se ha establecido una ganancia.', Response::HTTP_NO_CONTENT);
            }
    
            if ( $ganancia->getDisponible() <= 0) {

                return $this->errorsInterface->error_message('No tiene ventas disponibles para añadir al saldo', Response::HTTP_BAD_REQUEST);
            }


            $valor_comision= ($valor_retiro * $comision)/100;

            $v_retiro_final2=$valor_retiro -$valor_comision;
            $retiro= new Retiros();
            $retiro->setGanancia($gananciasVendedor);
            $retiro->setFecha(new DateTime());
            $retiro->setRetiro(round($valor_retiro, 2, PHP_ROUND_HALF_UP));
            $retiro->setComisionShopby(round($valor_comision, 2, PHP_ROUND_HALF_UP));
            $retiro->setRetiroFinal(round($v_retiro_final2, 2, PHP_ROUND_HALF_UP));
            $retiro->setEstado('MOVIDO_SALDO');
            $this->em->persist($retiro); 
    
            $recarga= new Recargas();
            $recarga->setRetiro($retiro);
            $recarga->setSaldo($saldo);
            $recarga->setTipoRecarga('TRANSACCIONES');
            $saldo->setSaldo($saldo->getSaldo() + $retiro->getRetiroFinal());
            $this->em->persist($recarga);
            $this->em->flush();
            $this->actualizar_disponibilidad($user,round($valor_retiro, 2, PHP_ROUND_HALF_UP), round($valor_comision, 2, PHP_ROUND_HALF_UP), round($v_retiro_final2, 2, PHP_ROUND_HALF_UP));

            return $this->errorsInterface->succes_message('Saldo recargado.');

        }

            return $this->errorsInterface->error_message('No hay metodo de recarga.', Response::HTTP_BAD_REQUEST);
    }


    private function actualizar_disponibilidad($user,$valor_retiro,$valor_comision,$v_retiro_final2)
    {
        $gananciasVendedor = $this->em->getRepository(Ganancia::class)->findOneBy(['login' => $user]);
        $gananciasVendedor->setDisponible($gananciasVendedor->getDisponible() - $valor_retiro);
        $gananciasVendedor->setTotalRetiros( $gananciasVendedor->getTotalRetiros() +  $valor_retiro);
        $gananciasVendedor->setTotalComision( $gananciasVendedor->getTotalComision() +    $valor_comision);
        $gananciasVendedor->setTotalRecibir($gananciasVendedor->getTotalRecibir() +  $v_retiro_final2);
        $this->em->flush();
    }
 
}

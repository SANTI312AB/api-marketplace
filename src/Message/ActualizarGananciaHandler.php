<?php  

namespace App\Message;

use App\Entity\Cupon;
use App\Entity\Ganancia;
use App\Entity\Login;
use App\Entity\Pedidos;
use App\Entity\Saldo;
use App\Message\ActualizarGananciaMessage;
use App\Service\FacturadorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ActualizarGananciaHandler
{
    public function __construct(private EntityManagerInterface $em,private FacturadorService $facturadorService) {}

    public function __invoke(ActualizarGananciaMessage $message)
    {
        $this->add_ganancia($message);
        if($message->getCuponId()){
          $this->add_saldo_cupon($message);  
        }  
    }

    public function add_ganancia(ActualizarGananciaMessage $message)
    {
        $login = $this->em->getRepository(Login::class)->find($message->getLoginId());
        if (!$login) {
            return;
        }

        $pedido = $this->em->getRepository(Pedidos::class)->find($message->getPedidoId());
        if (!$pedido) {
            return;
        }

        $gananciasVendedor = $this->em->getRepository(Ganancia::class)->findOneBy(['login' => $login]);
        if (!$gananciasVendedor) {
            $gananciasVendedor = new Ganancia();
            $gananciasVendedor->setLogin($login);
            $gananciasVendedor->setGanacia(0);
            $gananciasVendedor->setDisponible(0);
            $this->em->persist($gananciasVendedor);
        }

        $gananciasVendedor->setGanacia(round($gananciasVendedor->getGanacia() + $pedido->getTotal(), 2, PHP_ROUND_HALF_UP));
        $gananciasVendedor->setDisponible(round($gananciasVendedor->getDisponible() + $pedido->getTotal(), 2, PHP_ROUND_HALF_UP));

        $this->em->flush();
    }


    public function add_saldo_cupon(ActualizarGananciaMessage $message)
    {

        $cupon = $this->em->getRepository(Cupon::class)->find($message->getCuponId());
        if (!$cupon) {
            return;
        }

        $pedido= $this->em->getRepository(Pedidos::class)->find($message->getPedidoId());
        if (!$pedido) {
            return;
        }

        $user= $cupon->getTienda()->getLogin()->getId();
        if (!$user) {
            return;
        }
        $login= $this->em->getRepository(Login::class)->find($user);
        if (!$login) {
            return;
        }

        $saldo = $this->em->getRepository(Saldo::class)->findOneBy(['login' => $login]);
        if (!$saldo) {
            $saldo = new Saldo();
            $saldo->setLogin($login);
            $this->em->persist($saldo);
        }

        if($cupon->isAddSaldo() == false){
                $cupon->setAddSaldo(true);
                $saldo->setSaldo(round($saldo->getSaldo() + $pedido->getDescuentoCupon(), 2, PHP_ROUND_HALF_UP));
                $this->em->flush();
        }
    }
}

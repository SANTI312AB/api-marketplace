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
class ActualizarPagoHandler
{
    public function __construct(private EntityManagerInterface $em,private FacturadorService $facturadorService) {}

    public function __invoke(ActualizarPagoMessage $message)
    {
        $this->añadir_facturador_sri($message);
      
    }

    public function añadir_facturador_sri(ActualizarPagoMessage $message)
    {

        $pedido = $this->em->getRepository(Pedidos::class)->find($message->getPedidoId());
        if (!$pedido) {
            return;
        }
    
        $this->facturadorService->añadir_facturador($pedido);
    }

}

<?php

namespace App\Message;

use App\Entity\Pedidos;
use App\Entity\Saldo;
use App\Entity\Login;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RechazoPagoHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

    public function __invoke(RechazoPagoMessage $message): void
    {
        $id_login = $message->getLogin();
        $venta    = $message->getVenta();

        // Obtener pedidos SOLO del usuario y la venta
        $pedidos = $this->em->getRepository(Pedidos::class)->findBy([
            'login'    => $id_login,
            'n_venta'  => $venta
        ]);

        if (!$pedidos) {
            $this->logger->warning("No existen pedidos para venta {$venta} y user {$id_login}");
            return;
        }

        // Tomar el PRIMER pedido (el monto es igual en todos, por ahora)
        $pedido = $pedidos[0];

        $montoUsado = (float) $pedido->getMontoSaldo();

        if ($montoUsado <= 0) {
            $this->logger->info("La venta {$venta} del user {$id_login} no tiene monto_saldo usado");
            return;
        }

        // Obtener el usuario
        $usuario = $pedido->getLogin();
        if (!$usuario instanceof Login) {
            $this->logger->error("Pedido no tiene login asociado para venta {$venta}");
            return;
        }

        // Obtener saldo
        $saldoEntity = $usuario->getSaldo();
        if (!$saldoEntity instanceof Saldo) {
            $this->logger->error("Usuario {$id_login} no tiene entidad Saldo");
            return;
        }

        try {
            // Reembolsar solo una vez
            $saldoActual = (float) $saldoEntity->getSaldo();
            $saldoEntity->setSaldo(round($saldoActual + $montoUsado, 2));

            $this->em->persist($saldoEntity);
            $this->em->flush();

            $this->logger->info(
                "Reembolsado {$montoUsado} al usuario {$id_login} por venta {$venta}"
            );

        } catch (\Throwable $e) {
            $this->logger->error(
                "Error reembolsando al usuario {$id_login} por venta {$venta}: {$e->getMessage()}"
            );
        }
    }
}

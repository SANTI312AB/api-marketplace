<?php

namespace App\EventListener;

use App\Entity\Pedidos;
use App\Message\RechazoPagoMessage;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Psr\Log\LoggerInterface;

#[AsDoctrineListener(event: 'postUpdate', priority: 100)]
class RechazoPagoListener
{
    /**
     * @var array<string, bool> keys dispatched per-process: "<login>|<venta>"
     */
    private array $dispatched = [];

    public function __construct(
        private EntityManagerInterface $em,
        private MessageBusInterface $bus,
        private LoggerInterface $logger
    ) {}

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Pedidos) {
            return;
        }

        $uow = $this->em->getUnitOfWork();
        $changes = $uow->getEntityChangeSet($entity);

        if (!isset($changes['estado'])) {
            return;
        }

        [, $newEstado] = $changes['estado'];

        if (trim(strtoupper((string)$newEstado)) !== 'REJECTED') {
            return;
        }

        $login = $entity->getLogin()?->getId();
        $venta = (string) $entity->getNVenta();

        if (!$login) {
            $this->logger->warning('RechazoPagoListener: pedido sin login al pasar a REJECTED. Pedido id: ' . (string)$entity->getId());
            return;
        }

        if ($venta === '' || $venta === null) {
            $this->logger->warning("RechazoPagoListener: pedido sin n_venta válido para login {$login}");
            return;
        }

        // clave única por venta+login
        $key = $login . '|' . $venta;

        // ya despachado en este proceso? evitar duplicados
        if (isset($this->dispatched[$key])) {
            $this->logger->info("RechazoPagoListener: mensaje ya despachado para key={$key}, skip.");
            return;
        }

        // marcar como despachado (in-memory)
        $this->dispatched[$key] = true;

        // despachar mensaje con login (int) y venta (string)
        $this->bus->dispatch(new RechazoPagoMessage($login, $venta));

        $this->logger->info("RechazoPagoListener: dispatch RechazoPagoMessage for login={$login} venta={$venta}");
    }
}


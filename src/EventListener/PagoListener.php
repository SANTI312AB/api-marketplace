<?php

namespace App\EventListener;

use App\Entity\Pedidos;
use App\Message\ActualizarPagoMessage;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsDoctrineListener(event: 'postPersist', priority: 100)]
#[AsDoctrineListener(event: 'postUpdate', priority: 100)]
#[AsDoctrineListener(event: 'postFlush', priority: 100)]
class PagoListener
{
    private array $pending = [];

    public function __construct(
        private EntityManagerInterface $em,
        private MessageBusInterface $bus
    ) {}

    /**
     * postPersist → cuando se crea un pedido.
     * Si ya nace en APPROVED (método 4), lo encolamos.
     */
    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Pedidos) {
            return;
        }

        if ($entity->getEstado() === 'APPROVED') {
            $this->queue($entity);
        }
    }

    /**
     * postUpdate → cuando se actualiza un pedido.
     * Si cambia de PENDING → APPROVED, lo encolamos.
     */
    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Pedidos) {
            return;
        }

        $uow = $this->em->getUnitOfWork();
        $changes = $uow->getEntityChangeSet($entity);

        if (isset($changes['estado']) && $changes['estado'][1] === 'APPROVED') {
            $this->queue($entity);
        }
    }

    /**
     * postFlush → cuando todos los INSERT/UPDATE ya ocurrieron.
     * Aquí ya existen los detalles en base de datos.
     */
    public function postFlush(PostFlushEventArgs $args): void
    {
        if (!$this->pending) {
            return;
        }

        $ids = $this->pending;
        $this->pending = [];

        foreach ($ids as $id) {
            $pedido = $this->em->getRepository(Pedidos::class)->find($id);
            if (!$pedido) {
                continue;
            }

            if ($pedido->getEstado() !== 'APPROVED') {
                continue;
            }

            if ($pedido->getDetallePedidos()->isEmpty()) {
                // aún no tiene detalles, puedes reintentar según tu lógica
                continue;
            }

            // Ahora sí se puede facturar
            $this->bus->dispatch(new ActualizarPagoMessage($id));
        }
    }

    /**
     * Encola el pedido para su verificación en postFlush.
     */
    private function queue(Pedidos $pedido): void
    {
        $id = (int) $pedido->getId();

        if ($id > 0 && !in_array($id, $this->pending, true)) {
            $this->pending[] = $id;
        }
    }
}

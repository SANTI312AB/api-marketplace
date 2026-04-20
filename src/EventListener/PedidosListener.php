<?php

namespace App\EventListener;

use App\Entity\Pedidos;
use App\Entity\Estados;
use App\Message\ActualizarGananciaMessage;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsDoctrineListener(event: 'postUpdate', priority: 0)]
class PedidosListener
{
    public function __construct(private EntityManagerInterface $em, private MessageBusInterface $bus) {}

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Pedidos) {
            return;
        }

        // Solo continuar si el pedido ya está aprobado
        if ($entity->getEstado() !== 'APPROVED') {
            return;
        }

        // Obtener cambios de campos
        $uow = $this->em->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($entity);

        $tipoEnvio = $entity->getTipoEnvio();
        $crearLog = false;

        if ($tipoEnvio === 'A DOMICILIO' && isset($changeSet['estado_envio'])) {
            [$old, $new] = $changeSet['estado_envio'];
            if (
                $old instanceof Estados && $new instanceof Estados &&
                $old->getId() !== 22 && $new->getId() === 22
            ) {
                $crearLog = true;
            }
        }

        if ($tipoEnvio === 'RETIRO EN TIENDA FISICA' && isset($changeSet['estado_retiro'])) {
            [$old, $new] = $changeSet['estado_retiro'];
            if (
                $old instanceof Estados && $new instanceof Estados &&
                $old->getId() !== 22 && $new->getId() === 22
            ) {
                $crearLog = true;
            }
        }

        if (
            $tipoEnvio === 'AMBOS' &&
            isset($changeSet['estado_envio'], $changeSet['estado_retiro'])
        ) {
            [$oldEnvio, $newEnvio] = $changeSet['estado_envio'];
            [$oldRetiro, $newRetiro] = $changeSet['estado_retiro'];

            if (
                $oldEnvio instanceof Estados && $newEnvio instanceof Estados &&
                $oldRetiro instanceof Estados && $newRetiro instanceof Estados &&
                $oldEnvio->getId() !== 22 && $newEnvio->getId() === 22 &&
                $oldRetiro->getId() !== 22 && $newRetiro->getId() === 22
            ) {
                $crearLog = true;
            }
        }

        if (!$crearLog) {
            return;
        }

        $user= $entity->getTienda()->getLogin();

        $cuponId = $entity->getCupon() ? $entity->getCupon()->getId() : null;

         $this->bus->dispatch(
            new ActualizarGananciaMessage($user->getId(), $entity->getId(), $cuponId)
        );
        
    }
}



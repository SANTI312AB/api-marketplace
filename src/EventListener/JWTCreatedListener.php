<?php

namespace App\EventListener;

use App\Entity\Login;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;


class JWTCreatedListener
{

    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();

        if (!$user) {
            throw new \LogicException('No se encontró ningún usuario autenticado.');
        }


        if (!$user instanceof Login){
            throw new \LogicException('El usuario autenticado no es válido.');// Cambio aquí

        }

        $payload = $event->getData();

        // Personalizar el token con datos adicionales
        $payload['user_version'] = $user->getVersion() ? $user->getVersion() : null;

        $event->setData($payload);
    }
}

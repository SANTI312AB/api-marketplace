<?php  

// src/Security/BasicAuthLogoutSubscriber.php
namespace App\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class BasicAuthLogoutSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout'
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $response = new Response('', 401, [
            'WWW-Authenticate' => 'Basic realm="Secured Area"'
        ]);

        $event->setResponse($response);
    }
}
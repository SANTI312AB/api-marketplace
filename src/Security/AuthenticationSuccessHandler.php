<?php

namespace App\Security;

use App\Interfaces\ErrorsInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AuthenticationSuccessHandler implements EventSubscriberInterface
{
    private $errorsInterface;

    public function __construct(ErrorsInterface $errorsInterface)
    {
        $this->errorsInterface = $errorsInterface;
    }
    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();
        $token = $data['token'] ?? null;

        // Utiliza la interfaz para construir la respuesta
      /* $response = $this->errorsInterface->succes_message(
            'Sesión iniciada con éxito.',
            null,
            $token
        );

        // Extrae el contenido de la respuesta JSON y lo establece en el evento
       $event->setData(json_decode($response->getContent(), true));*/

       $m=[
          'message'=>'Sesión iniciada con éxito.',
          'token'=>$token
       ];

        $event->setData($m);
    }
}
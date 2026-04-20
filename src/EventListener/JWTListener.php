<?php

namespace App\EventListener;

use App\Exception\InvalidTokenException;
use App\Interfaces\ErrorsInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

class JWTListener
{
    private $errorsInterface;

    public function __construct(ErrorsInterface $errorsInterface)
    {
        $this->errorsInterface = $errorsInterface;
    }
    // Personalizar la respuesta al fallar la autenticación (credenciales incorrectas o cuenta bloqueada/no verificada)
    public function onAuthenticationFailure(AuthenticationFailureEvent $event)
    {
        $exception = $event->getException();

        // Verifica si la excepción es una instancia de CustomUserMessageAccountStatusException
        if ($exception instanceof CustomUserMessageAccountStatusException) {
            // Obtener el mensaje personalizado desde la excepción lanzada por el UserChecker
            $response = $this->errorsInterface->error_message(
            $exception->getMessageKey(),
            Response::HTTP_FORBIDDEN
            );

        } else {
            // Caso por defecto: credenciales inválidas
            $response = $this->errorsInterface->error_message(
                'Credenciales inválidas.',
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Establecer la respuesta personalizada en el evento
        $event->setResponse($response);
    }

    // Personalizar la respuesta cuando el token es inválido
    public function onJWTInvalid(JWTInvalidEvent $event): void
    {
        $exception = $event->getException();

        if ($exception instanceof InvalidTokenException) {
            $messages = [
                'user_not_found' => 'Usuario no registrado en el sistema.',
                'user_blocked' => 'Cuenta bloqueada. Contacte al administrador.',
                'version_mismatch' => 'Credenciales desactualizadas. Inicie sesión nuevamente.',
                'invalid_payload' => 'Token inválido: Estructura incorrecta.'
            ];
            
            $message = $messages[$exception->getReason()] ?? 'Token inválido';
            $response = $this->errorsInterface->error_message(
                $message,
                Response::HTTP_UNAUTHORIZED
            );
            $event->setResponse($response);
            return;
        }

        // Respuesta genérica para otros casos
        $response = $this->errorsInterface->error_message(
            'Autenticación inválida, por favor inicie sesión nuevamente',
            Response::HTTP_UNAUTHORIZED
        );
        $event->setResponse($response);
    }

    // Personalizar la respuesta cuando no se encuentra el token
    public function onJWTNotFound(JWTNotFoundEvent $event)
    {
        $response = $this->errorsInterface->error_message(
            'No está autenticado, por favor, inicie sesión.',
            Response::HTTP_UNAUTHORIZED
        );
        $event->setResponse($response);
    }

    // Personalizar la respuesta cuando el token ha expirado
    public function onJWTExpired(JWTExpiredEvent $event)
    {
        
        $response = $this->errorsInterface->error_message(
            'La sesión ha expirado. Por favor, inicie sesión nuevamente.',
            Response::HTTP_UNAUTHORIZED
        );
        $event->setResponse($response);
    }
}

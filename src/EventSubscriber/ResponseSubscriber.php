<?php
namespace App\EventSubscriber;

use App\Service\LoggerService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ResponseSubscriber implements EventSubscriberInterface
{
    private $loggerService;

    public function __construct(LoggerService $loggerService)
    {
        $this->loggerService = $loggerService;
    }

    public function onResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $statusCode = $response->getStatusCode();
        
        // 🛑 EVITAR DOBLE LOG: Si es un error 500 o mayor, onException ya lo guardó. 
        // No necesitamos registrar el JSON genérico de "Internal Server Error".
        if ($statusCode >= 500) {
            return;
        }

        $content = $response->getContent();
        $data = json_decode($content, true);
        $message = '';

        if (isset($data['message'])) {
            $message .= 'Message: ' . (is_array($data['message']) ? json_encode($data['message']) : (string)$data['message']);
        }

        if (isset($data['error'])) {
            $message .= (!empty($message) ? ' | ' : '') . 'Error: ' . (is_array($data['error']) ? json_encode($data['error']) : (string)$data['error']);
        }

        if (isset($data['errors'])) {
            $message .= (!empty($message) ? ' | ' : '') . 'Error: ' . (is_array($data['errors']) ? json_encode($data['errors']) : (string)$data['errors']);
        }

        if (isset($data['data'])) {
            $message .= (!empty($message) ? ' | ' : '') . 'Data: ' . (is_array($data['data']) ? json_encode($data['data']) : (string)$data['data']);
        }

        if (!empty($message)) {
            $this->loggerService->logAction($statusCode, $message);
        }
    }

    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

        $message = sprintf(
            "Exception: %s | File: %s | Line: %d",
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );

        // 🔥 BLINDAJE: Si el logger falla por falta de DB o Firewall, la app NO debe morir.
        try {
            $this->loggerService->logAction($statusCode, $message);
        } catch (\Throwable $t) {
            // Fallo silencioso del log. Permite que Symfony siga y muestre el ErrorController.
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Le damos prioridad a la excepción para que se registre primero
            KernelEvents::EXCEPTION => ['onException', 10], 
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }
}
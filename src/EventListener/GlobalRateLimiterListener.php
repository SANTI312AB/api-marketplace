<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class GlobalRateLimiterListener
{
    private $globalLimiter;

    public function __construct(RateLimiterFactory $globalLimiter)
    {
        $this->globalLimiter = $globalLimiter;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Solo aplica a requests principales (no sub-requests)
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        
        // Crea una clave única por IP (puedes personalizar esto)
        $limiterKey = $request->getClientIp();
        
        // Obtiene el limitador
        $limiter = $this->globalLimiter->create($limiterKey);
        
        // Consume un token del limitador
        $limit = $limiter->consume();
        
        // Si se excede el límite, lanza excepción
        if (!$limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter();
            $retryAfterSeconds = $retryAfter instanceof \DateTimeImmutable ? $retryAfter->getTimestamp() - time() : null;
            throw new TooManyRequestsHttpException(
                $retryAfterSeconds,
                'Too many requests. Try again later.'
            );
        }
        
        // Agrega headers de información (opcional)
        $event->getResponse()?->headers->add([
            'X-RateLimit-Limit' => $limit->getLimit(),
            'X-RateLimit-Remaining' => $limit->getRemainingTokens(),
            'X-RateLimit-Reset' => time() + $limit->getRetryAfter(),
        ]);
    }
}
<?php

namespace App\EventListener;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Contracts\Cache\ItemInterface;

class CacheResponseListener
{
    private FilesystemAdapter $cache;

    public function __construct()
    {
        $this->cache = new FilesystemAdapter(); // Adaptador de caché
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        // Verificamos si es la petición principal (antes: isMasterRequest())
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        // Creamos una clave de caché basada en la URL de la petición
        $cacheKey = md5($request->getUri());

        // Intentamos obtener una respuesta cacheada
        $cachedResponse = $this->cache->get($cacheKey, function (ItemInterface $item) use ($response) {
            $item->expiresAfter(3600); // Cachear durante 1 hora
            return $response; // Devolvemos la respuesta original
        });

        // Reemplazamos la respuesta original con la respuesta cacheada
        $event->setResponse($cachedResponse);
    }
}

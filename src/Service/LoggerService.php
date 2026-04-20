<?php

namespace App\Service;

use App\Entity\LogsApi;
use App\Entity\Login;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class LoggerService
{
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;
    private Security $security;
    private TokenStorageInterface $tokenStorage;
    private JWTTokenManagerInterface $jwtManager;
    private ManagerRegistry $managerRegistry;

    public function __construct(
        ManagerRegistry $managerRegistry,
        EntityManagerInterface $entityManager,
        Security $security,
        RequestStack $requestStack,
        TokenStorageInterface $tokenStorage,
        JWTTokenManagerInterface $jwtManager
    ){
        $this->managerRegistry = $managerRegistry;
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
        $this->jwtManager = $jwtManager;
    }

     public function logAction($statusCode, $message)
    {
        if (empty($message)) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            $method = $request->getMethod();

            // Ignorar GET/OPTIONS SOLO si no es un error (ej. status 200)
            if ($statusCode < 400 && in_array($method, ['GET','OPTIONS'], true)) {
                return;
            }
        }

        switch ($statusCode) {
            case 404:
            case 405:
                return;
            default:
                $user = null;
                $token = null;

                // 🔥 BLINDAJE 1: Obtener el usuario de forma segura sin romper el firewall
                try {
                    if ($this->tokenStorage && $this->tokenStorage->getToken()) {
                        $token = $this->tokenStorage->getToken();
                        if (method_exists($token, 'getUser')) {
                            $user = $token->getUser();
                        }
                    }
                } catch (\Throwable $e) {
                    // El firewall no está listo (ej. error en la base de datos muy temprano). Lo ignoramos silenciosamente.
                }

                $logEntry = new LogsApi();
                
                if ($user instanceof Login) {
                    $logEntry->setLogin($user);
                    
                    if ($token !== null) {
                        // 🔥 BLINDAJE 2: Si falla la regeneración del JWT, no matamos la petición
                        try {
                            $jwtToken = $this->jwtManager->create($user);
                            $logEntry->setToken($jwtToken);
                        } catch (\Throwable $e) {
                            // Ignoramos el error
                        }
                    }
                }

                $logEntry->setFechaLog(new DateTime());
                
                // 🔥 BLINDAJE 3: Evitar crash si $request es null (ej. errores de inicio o consola)
                $route = $request ? $request->get('_route', 'N/A') : 'UNKNOWN';
                $reqMethod = $request ? $request->getMethod() : 'UNKNOWN';
                $ip = $request ? $request->getClientIp() : '127.0.0.1';

                $logEntry->setAcctionLog($route);
                $logEntry->setMethod($reqMethod);
                $logEntry->setIp($ip);
                $logEntry->setResponseLog($statusCode);
                $logEntry->setMessage($message);

                try {
                    $this->entityManager->persist($logEntry);
                    $this->entityManager->flush();
                } catch (\Exception $e) {
                    if ($this->entityManager->getConnection()->isTransactionActive()) {
                        $this->entityManager->rollback();
                    }
                    $this->managerRegistry->resetManager();
                    return $e->getMessage();
                }
                break;
        }
    }
}
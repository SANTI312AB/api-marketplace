<?php

// src/EventListener/JWTDecodedListener.php
namespace App\EventListener;

use App\Exception\InvalidTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Login;
use Psr\Log\LoggerInterface;

class JWTDecodedListener
{
    // ... (constructor y propiedades existentes)
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function onJWTDecoded(JWTDecodedEvent $event): void
    {
        $payload = $event->getPayload();

        if (!isset($payload['username'], $payload['user_version'])) {
            $this->logger->info('JWTDecodedListener: Missing username or user_version in payload');
            throw new InvalidTokenException('invalid_payload', 'Token structure is invalid');
        }

        $username = $payload['username'];
        $userVersion = $payload['user_version'];

        $user = $this->entityManager->getRepository(Login::class)
                     ->findOneBy(['username' => $username]);

        if (!$user) {
            $this->logger->info("JWTDecodedListener: User '{$username}' not found");
            throw new InvalidTokenException('user_not_found', "User not found");
        }

        $estado = $user->getEstados();
        if (method_exists($estado, 'getId') && $estado->getId() == 2) {
            $this->logger->info("JWTDecodedListener: User '{$username}' is blocked");
            throw new InvalidTokenException('user_blocked', "User account is blocked");
        }

        if ($user->getVersion() !== $userVersion) {
            $this->logger->info("JWTDecodedListener: Token version mismatch for '{$username}'");
            throw new InvalidTokenException('version_mismatch', "Token version mismatch");
        }
    }
}
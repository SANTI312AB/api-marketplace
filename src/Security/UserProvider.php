<?php

namespace App\Security;

use App\Entity\Login;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $userRepository = $this->entityManager->getRepository(Login::class);

        $user = $userRepository->findOneBy(['username' => $identifier]);

        if (!$user) {
            $user = $userRepository->findOneBy(['email' => $identifier]);
        }

        if (!$user) {
            throw new UserNotFoundException('No se encontró un usuario con este nombre de usuario o correo electrónico.'); // Cambio aquí
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface{
        if (!$user instanceof Login) {
            throw new UnsupportedUserException('...');
        }
        return $this->loadUserByIdentifier($user->getUserIdentifier()); // Usar getUserIdentifier
    }

    public function supportsClass($class): bool
    {
        return Login::class === $class;
    }
}
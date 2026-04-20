<?php

namespace App\Security;

use App\Entity\Estados;
use App\Entity\Login;
use App\Entity\Pais;
use App\Entity\Tiendas;
use App\Entity\Usuarios;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\FacebookUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class FacebookUserProvider implements UserProviderInterface
{
    private $entityManager;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    public function loadUserByIdentifier(string $identifier, ?FacebookUser $facebookUser = null): UserInterface
    {
        $userRepository = $this->entityManager->getRepository(Login::class);

        // Buscar usuario por email
        $login = $userRepository->findOneBy(['email' => $identifier]);

        if (!$login) {
            if (!$facebookUser) {
                throw new \LogicException("No se puede crear el usuario porque FacebookUser no fue proporcionado.");
            }

            // Crear el usuario si no existe
            $login = $this->createOrUpdateUserFromFacebookUser($facebookUser);
        }
        return $login;
    }

    public function createOrUpdateUserFromFacebookUser(FacebookUser $facebookUser): Login
    {
        // Obtener repositorios y estados necesarios
        $pais = $this->entityManager->getRepository(Pais::class)->findOneBy(['id' => 1]);
        $enable = $this->entityManager->getRepository(Estados::class)->findOneBy(['id' => 1]);
        $verificado = $this->entityManager->getRepository(Estados::class)->findOneBy(['id' => 7]);
        $estadoBiometrico = $this->entityManager->getRepository(Estados::class)->findOneBy(['id' => 16]);
        $estadoTienda = $this->entityManager->getRepository(Estados::class)->findOneBy(['id' => 4]);
        $userRepository = $this->entityManager->getRepository(Login::class);

        // Buscar usuario por email
        $login = $userRepository->findOneBy(['email' => $facebookUser->getEmail()]);

        if (!$login) {
            // Crear un nuevo usuario si no existe
            $login = new Login();
            $login->setEmail($facebookUser->getEmail());
            $login->setUsername($facebookUser->getName() ?? $facebookUser->getId());
            $login->setFacebookId($facebookUser->getId());

            // Generar y encriptar la contraseña
            $rawPassword = ($facebookUser->getName() ?? 'user') . $facebookUser->getId();
            $hashedPassword = $this->passwordHasher->hashPassword($login, $rawPassword);
            $login->setPassword($hashedPassword);
            $login->setEstados($enable);
            $login->setVericacion($verificado);

            // Crear la entidad Usuarios asociada
            $usuario = new Usuarios();
            $usuario->setLogin($login);
            $usuario->setEmail($facebookUser->getEmail());
            $usuario->setNombre($facebookUser->getFirstName());
            $usuario->setApellido($facebookUser->getLastName());
            $usuario->setPais($pais);
            $usuario->setEstados($estadoBiometrico);

            // Generar slug para la tienda
            $slug = str_replace(' ', '-', $facebookUser->getName());
            $slug = strtolower($slug);
            $slug = preg_replace('/[^a-z0-9áéíóúüñ-]/', '', $slug);

            $tienda = new Tiendas();
            $tienda->setLogin($login);
            $tienda->setSlug($slug);
            $tienda->setNombreTienda($facebookUser->getName());
            $tienda->setEstado($estadoTienda);
            $tienda->setComision(15);

            // Persistir entidades
            $this->entityManager->persist($login);
            $this->entityManager->persist($usuario);
            $this->entityManager->persist($tienda);
        } else {
            // Actualizar datos si el usuario ya existe
            if (!$login->getFacebookId()) {
                $login->setFacebookId($facebookUser->getId());
            }
        }

        $this->entityManager->flush();

        return $login;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return Login::class === $class;
    }
}

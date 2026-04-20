<?php

namespace App\Security;

use App\Entity\Estados;
use App\Entity\Login;
use App\Entity\Pais;
use App\Entity\Tiendas;
use App\Entity\Usuarios;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class GoogleUserProvider implements UserProviderInterface
{
    private  $entityManager;
    private  $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    public function loadUserByIdentifier(string $identifier, ?GoogleUser $googleUser = null): UserInterface
    {
        $userRepository = $this->entityManager->getRepository(Login::class);

        // Buscar usuario por email
        $login = $userRepository->findOneBy(['email' => $identifier]);

        if (!$login) {
            if (!$googleUser) {
                throw new \LogicException("No se puede crear el usuario porque GoogleUser no fue proporcionado.");
            }

            // Crear el usuario si no existe
            $login = $this->createOrUpdateUserFromGoogleUser($googleUser);
        }
        return $login;
    }

    public function createOrUpdateUserFromGoogleUser(GoogleUser $googleUser): Login
    {
        $pais= $this->entityManager->getRepository(Pais::class)->findOneBy(['id'=>1]);
        $enable=  $this->entityManager->getRepository(Estados::class)->findOneBy(['id'=>1]);
        $verificado= $this->entityManager->getRepository(Estados::class)->findOneBy(['id'=>7]);
        $estado_biometrico= $this->entityManager->getRepository(Estados::class)->findOneBy(['id'=>16]);
        $estado_tienda= $this->entityManager->getRepository(Estados::class)->findOneBy(['id'=>4]);
        $userRepository = $this->entityManager->getRepository(Login::class);
    
        // Buscar usuario por email
        $login = $userRepository->findOneBy(['email' => $googleUser->getEmail()]);

        $email= $googleUser->getEmail();
        if ($email) {
            $username = explode('@', $email)[0];
            // Ahora $username contiene el texto antes del @
        } else {
            $username = null; // Manejo del caso en que el email no esté disponible
        }


        $fullName = trim($googleUser->getName());
        $parts = explode(' ', $fullName);
        
        if (count($parts) === 1) {
            $firstName = $fullName;
            $lastName = '';
        } else {
            $firstName = array_shift($parts);
            $lastName = implode(' ', $parts);
        }
        
        // Now, $firstName and $lastName can be used as needed

        
        $slug = str_replace(' ', '-',$username );


        // Convierte el slug a minúsculas.
        $slug = strtolower($slug);

        // Elimina caracteres especiales y acentos.
        $slug = preg_replace('/[^a-z0-9áéíóúüñ-]/', '', subject: $slug);

    
        if (!$login) {
            // Crear un nuevo usuario si no existe
            $login = new Login();
            $login->setEmail($googleUser->getEmail());
            $login->setUsername($username.'-'.uniqid());
            $login->setGoogleId($googleUser->getId());
    
            // Generar y encriptar la contraseña
            $rawPassword = ($slug.uniqid()) . $googleUser->getId();
            $hashedPassword = $this->passwordHasher->hashPassword($login, $rawPassword);
            $login->setPassword($hashedPassword);
            $login->setEstados($enable);
            $login->setVericacion($verificado);

            // Crear la entidad Usuarios asociada
            $usuario = new Usuarios();
            $usuario->setLogin($login);
            $usuario->setUsername($username.'-'.uniqid());
            $usuario->setEmail($googleUser->getEmail());
            $usuario->setNombre($firstName);
            $usuario->setApellido($lastName);
            $usuario->setPais($pais);
            $usuario->setEstados($estado_biometrico);

            $tienda= new Tiendas();
            $tienda->setLogin($login);
            $tienda->setSlug($slug.uniqid());
            $tienda->setNombreTienda($username.'-'.uniqid());
            $tienda->setEstado($estado_tienda);
            $tienda->setComision(15);
    
            $this->entityManager->persist($login);
            $this->entityManager->persist($usuario);
            $this->entityManager->persist($tienda);
        } else {

                $usuario= $this->entityManager->getRepository(Usuarios::class)->findOneBy(['login'=>$login]);
                $login->setGoogleId($googleUser->getId());
                $login->setEmail($googleUser->getEmail());
                $login->setUsername($username.'-'.uniqid());
                $usuario->setEmail($googleUser->getEmail());
                $usuario->setUsername($username.'-'.uniqid());
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

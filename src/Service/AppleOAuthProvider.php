<?php

namespace App\Service;

use App\Entity\Estados;
use App\Entity\GeneralesApp;
use App\Entity\Login;
use App\Entity\Pais;
use App\Entity\Tiendas;
use App\Entity\Usuarios;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use League\OAuth2\Client\Provider\Apple;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppleOAuthProvider
{    private Apple $provider;
    private ParameterBagInterface $parameters;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private array $configApp = [];

    public function __construct(
        ParameterBagInterface $parameters,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->parameters = $parameters;
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;

        $generales= $this->entityManager->getRepository(GeneralesApp::class)->findBy(['nombre'=>'apple']);
        foreach ($generales as $parametro) {
        $this->configApp[$parametro->getAtributoGeneral()] = $parametro->getValorGeneral();
        }

        $this->provider = new Apple([
            'clientId' => $this->configApp['Login'], // Service ID
            'teamId' => $this->configApp['Team'],     // Team ID
            'keyFileId' => $this->configApp['SecretKey'],   // Key ID
            'keyFilePath' => $parameters->get('apple_key_path'), // Ruta al AuthKey.p8
            'redirectUri' => $this->configApp['Url'], // URL registrada en Return URLs
        ]);
    }

    public function generateJWT(): string
    {
        $keyPath = $this->parameters->get('apple_key_path');
        $teamId = $this->configApp['Team'];
        $clientId = $this->configApp['Login'];
        $keyId = $this->configApp['SecretKey'];

        $keyContent = file_get_contents($keyPath);
        if (!$keyContent) {
            throw new \RuntimeException("No se pudo leer el archivo de clave privada en: $keyPath");
        }

        return JWT::encode([
            'iss' => $teamId,
            'iat' => time(),
            'exp' => time() + 3600,
            'aud' => 'https://appleid.apple.com',
            'sub' => $clientId,
        ], $keyContent, 'ES256', $keyId);
    }

    public function getAuthorizationUrl($state): string
    {
        return $this->provider->getAuthorizationUrl([
            'state' => $state, // Incluye el estado codificado
            'scope' => ['name', 'email'],
            'response_type' => 'code'
        ]);
    }

    public function getAccessToken(string $code)
    {
        return $this->provider->getAccessToken('authorization_code', [
            'code' => $code
        ]);
    }

    public function getUserFromToken($token): ResourceOwnerInterface
    {
        return $this->provider->getResourceOwner($token);
    }

    public function createOrUpdateUserFromAppleUser(ResourceOwnerInterface $appleUser): Login
    {
        $pais = $this->entityManager->getRepository(Pais::class)->findOneBy(['id' => 1]);
        $enable = $this->entityManager->getRepository(Estados::class)->findOneBy(['id' => 1]);
        $verificado = $this->entityManager->getRepository(Estados::class)->findOneBy(['id' => 7]);
        $estado_biometrico = $this->entityManager->getRepository(Estados::class)->findOneBy(['id' => 16]);
        $estado_tienda = $this->entityManager->getRepository(Estados::class)->findOneBy(['id' => 4]);
        $userRepository = $this->entityManager->getRepository(Login::class);

    
        $user= $appleUser->toArray();
       
        $email = $user['email']?? null;
        $appleId = $user['sub'] ?? null;
        $login = $userRepository->findOneBy(['email' => $email]);

        if ($email) {
            $username = explode('@', $email)[0];
            // Ahora $username contiene el texto antes del @
        } else {
            $username = null; // Manejo del caso en que el email no esté disponible
        }

        $slug = strtolower(str_replace(' ', '-', $username));
        $slug = preg_replace('/[^a-z0-9áéíóúüñ-]/', '', $slug);

        if (!$login) {
            $login = new Login();
            $login->setEmail($email);
            $login->setUsername($username.'-'.uniqid());
            $login->setAppleId($appleId);

            $rawPassword = ($slug.uniqid()) . $appleId;
            $hashedPassword = $this->passwordHasher->hashPassword($login, $rawPassword);
            $login->setPassword($hashedPassword);
            $login->setEstados($enable);
            $login->setVericacion($verificado);

            $usuario = new Usuarios();
            $usuario->setLogin($login);
            $usuario->setEmail($email);
            $usuario->setUsername($username.'-'.uniqid());
            $usuario->setNombre($slug);
            $usuario->setPais($pais);
            $usuario->setEstados($estado_biometrico);

            $tienda = new Tiendas();
            $tienda->setLogin($login);
            $tienda->setSlug($slug.uniqid());
            $tienda->setNombreTienda($slug);
            $tienda->setEstado($estado_tienda);
            $tienda->setComision(15);

            $this->entityManager->persist($login);
            $this->entityManager->persist($usuario);
            $this->entityManager->persist($tienda);
        } else {
                
            $usuario= $this->entityManager->getRepository(Usuarios::class)->findOneBy(['login'=>$login]);
            $login->setAppleId($appleId);
            $login->setEmail($email);
            $login->setUsername($username.'-'.$slug.uniqid());
            $usuario->setEmail($email);
            $usuario->setUsername($username.'-'.$slug.uniqid());
        }

        $this->entityManager->flush();

        return $login;
    }
}

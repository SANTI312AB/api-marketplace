<?php

namespace App\Service;

use App\Entity\GeneralesApp;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use KnpU\OAuth2ClientBundle\Client\Provider\FacebookClient;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\Facebook;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class OAuthClientFactory
{
    private EntityManagerInterface $em;
    private RouterInterface $router;
    private RequestStack $requestStack;

    public function __construct(
        EntityManagerInterface $em,
        RouterInterface $router,
        RequestStack $requestStack
    ) {
        $this->em = $em;
        $this->router = $router;
        $this->requestStack = $requestStack;
    }

    public function getGoogleClient(): GoogleClient
    {
        $credenciales = $this->em->getRepository(GeneralesApp::class)->findBy(['nombre' => 'google']);

        if (!$credenciales || count($credenciales) === 0) {
            throw new \RuntimeException('No se encontraron parámetros de configuración para Google');
        }

        $config = [];

        foreach ($credenciales as $parametro) {
            $config[$parametro->getAtributoGeneral()] = $parametro->getValorGeneral();
        }

        foreach (['Login', 'SecretKey', 'Url'] as $clave) {
            if (empty($config[$clave])) {
                throw new \RuntimeException("Falta el parámetro '$clave' en la configuración de Google");
            }
        }

        return new GoogleClient(
            new Google([
                'clientId'     => $config['Login'],
                'clientSecret' => $config['SecretKey'],
                'redirectUri'  => $config['Url'],
            ]),
            $this->requestStack
        );
    }


     public function getFacebookClient(): FacebookClient
    {
        $credenciales = $this->em->getRepository(GeneralesApp::class)->findBy(['nombre' => 'facebook']);

        if (!$credenciales || count($credenciales) === 0) {
            throw new \RuntimeException('No se encontraron parámetros de configuración para Google');
        }

        $config = [];

        foreach ($credenciales as $parametro) {
            $config[$parametro->getAtributoGeneral()] = $parametro->getValorGeneral();
        }

        foreach (['Login', 'SecretKey', 'Url'] as $clave) {
            if (empty($config[$clave])) {
                throw new \RuntimeException("Falta el parámetro '$clave' en la configuración de Google");
            }
        }

        return new FacebookClient(
            new Facebook([
                'clientId'        => $config['Login'],
                'clientSecret'    => $config['SecretKey'],
                'redirectUri'     => $config['Url'],
                'graphApiVersion' => $config['Api_Version'] ?? 'v21.0', // Valor por defecto
            ]),
            $this->requestStack
        );
    }
}

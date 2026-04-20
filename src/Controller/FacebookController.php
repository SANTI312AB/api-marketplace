<?php

namespace App\Controller;

use App\Interfaces\ErrorsInterface;
use App\Service\OAuthClientFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use League\OAuth2\Client\Provider\Facebook;
use App\Security\FacebookUserProvider;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

class FacebookController extends AbstractController
{
    private $facebookProvider;
    private $jwtManager;
    private $facebookUserProvider;
    private $em;

    private $errorInterface;

    public function __construct(
        OAuthClientFactory $facebookProvider,
        JWTTokenManagerInterface $jwtManager,
        FacebookUserProvider $facebookUserProvider,
        EntityManagerInterface $em,
        ErrorsInterface $errorInterface
    ) {
        $this->facebookProvider = $facebookProvider;
        $this->jwtManager = $jwtManager;
        $this->facebookUserProvider = $facebookUserProvider;
        $this->em = $em;
        $this->errorInterface = $errorInterface;
    }

    #[Route('/connect/facebook', name: 'connect_facebook_start', methods: ['GET'])]
    #[OA\Tag(name: 'Facebook')]
    #[OA\Response(
        response: 200,
        description: 'Retorna link login Facebook'
    )]
    public function connectAction(): Response
    {
        $facebookProvider = $this->facebookProvider->getFacebookClient()->getOAuth2Provider();
        $authorizationUrl = $facebookProvider->getAuthorizationUrl([
            'scope' => ['email', 'public_profile'],
        ]);

        return $this->json([
            'url' => $authorizationUrl,
        ]);
    }

    #[Route('/connect/facebook/check', name: 'connect_facebook_check', methods: ['GET'])]
    #[OA\Tag(name: 'Facebook')]
    #[OA\Response(
        response: 200,
        description: 'Valida sesión con Facebook y retorna token de inicio de sesión'
    )]
    public function connectCheckAction(Request $request): Response
    {
        try {
            $code = $request->query->get('code');

            if (!$code) {
                return $this->errorInterface->error_message(
                    'Código de autorización no proporcionado.',
                    Response::HTTP_BAD_REQUEST
                );
            }

            $facebookProvider = $this->facebookProvider->getFacebookClient()->getOAuth2Provider();

            // Intercambiar el código de autorización por un token de acceso
            $token = $facebookProvider->getAccessToken('authorization_code', [
                'code' => $code,
            ]);

            // Obtener la información del usuario desde Facebook
            $facebookUser = $facebookProvider->getResourceOwner($token);

            // Crear o actualizar el usuario
            $login = $this->facebookUserProvider->loadUserByIdentifier(
                $facebookUser->getEmail(),
                $facebookUser
            );

            // Generar un JWT para el usuario autenticado
            $jwt = $this->jwtManager->create($login);

            // Respuesta exitosa
            return $this->json(['token' => $jwt]);
        } catch (Exception $e) {
            return $this->errorInterface->error_message(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}


<?php

namespace App\Controller;

use App\Entity\GeneralesApp;
use App\Entity\Login;
use App\Entity\MetodosLogeo;
use App\Interfaces\ErrorsInterface;
use App\Service\AppleOAuthProvider;
use Exception;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;

class AppleController extends AbstractController
{
    private  $appleProvider;

    private  $jwtManager;

    private  $em;

    private $errorInterface;


    public function __construct(AppleOAuthProvider $appleProvider, JWTTokenManagerInterface $jwtManager,EntityManagerInterface $em, ErrorsInterface $errorInterface)
    {
        $this->appleProvider = $appleProvider;
        $this->jwtManager = $jwtManager;
        $this->em = $em;
        $this->errorInterface = $errorInterface;
    }

    #[Route('/connect/apple', name: 'connect_apple_start', methods: ['GET'])]
    #[OA\Tag(name: 'Apple')]
    #[OA\Response(
        response: 200,
        description: 'Retorna link login APPLE'
    )]
    #[OA\Parameter(
        name: "redirect_to",
        in: "query",
        description: "carga path de redireccion en la url que retorna apple."
    )]
    public function connectAction(Request $request): Response
    {
        $allowedParams = [
            'redirect_to'
        ];

        // Obtener todos los parámetros enviados
        $queryParams = array_keys($request->query->all());

        // Detectar parámetros no permitidos
        $invalidParams = array_diff($queryParams, $allowedParams);

        if (!empty($invalidParams)) {
            return $this->errorInterface->error_message(
                'Parámetros no permitidos',
                Response::HTTP_BAD_REQUEST
            );
        }
        $apple= $this->em->getRepository(MetodosLogeo::class)->findOneBy(['id' => 2]);
        
        if (!$apple || !$apple->isEnable()) {
            return $this->errorInterface->error_message(
                'El método de inicio de sesión con Apple no está habilitado.',
                Response::HTTP_BAD_REQUEST
            );
        }
        
        $code = $request->query->get('redirect_to','/');
        try{
        $path = $this->encodeURIComponent($code);
        $authorizationUrl = $this->appleProvider->getAuthorizationUrl($path);
         
        return $this->errorInterface->succes_message(
            'Link retornado con éxito.',
            null,
            ['url' => $authorizationUrl]
        );

        }catch(Exception $e) {
            return $this->errorInterface->error_message(
                $e->getMessage(),
                Response::HTTP_BAD_REQUEST,
                null,
                null
            );
        }
        
    }

    #[Route('/connect/apple/callback', name: 'connect_apple_callback', methods: ['POST'])]
    #[OA\Tag(name: 'Apple')]
    #[OA\Response(
        response: 200,
        description: 'Verifica login de usuario Apple',
    )]
    #[OA\RequestBody(
        description: 'Inicio de Session para obtener token',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'code', type: 'string')
            ]
        )
    )]
    public function callbackAction(Request $request): Response
    {
        // Recuperar el código de autorización desde la solicitud
        $code = $request->request->get('code');
        $state = $request->request->get('state');
        $jwt='';
    
        if (!$code) {
            return $this->errorInterface->error_message(
                'Authorization code is missing.',
                Response::HTTP_BAD_REQUEST
            );
        }
    
        try {
            // Intercambiar el código por un token de acceso
            $token = $this->appleProvider->getAccessToken(code: $code);
    
            // Recuperar los datos del usuario con el token
            $appleUser = $this->appleProvider->getUserFromToken($token);
    
            // Guardar o actualizar el usuario en la base de datos
            $login = $this->appleProvider->createOrUpdateUserFromAppleUser($appleUser);

            if (!$login instanceof UserInterface) {
                return $this->errorInterface->error_message(
                    'El objeto proporcionado no es una instancia de UserInterface.',
                    Response::HTTP_BAD_REQUEST
                );
            }

            $jwt = $this->jwtManager->create($login);
    
              // Generar un JWT para el usuario autenticado
            $this->log_login($login);

            $data_url= $this->em->getRepository(GeneralesApp::class)->findOneBy(['nombre'=>'front','atributoGeneral'=>'Url']);

            $url = $data_url->getValorGeneral().'/auth/callback?token='.$jwt.'&redirect_to='.$state;
            return $this->json($url);
            //return $this->redirect($url);
            
            
        } catch (IdentityProviderException $e) {
            return $this->errorInterface->error_message(
                $e->getMessage(),
                Response::HTTP_BAD_REQUEST
            );
        } catch (Exception $e) {
            return $this->errorInterface->error_message(
                'Error al validar usuario.',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                null,
                $e->getMessage()
            );
        }
    }


    private function encodeURIComponent($str) {
        $revert = array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')');
        return strtr(rawurlencode($str), $revert);
    }

    private function log_login(Login $login) {
        $login->setLastLogin(new DateTime());
        $this->em->persist($login);
        $this->em->flush();
    }

      
}

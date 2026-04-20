<?php

namespace App\Controller;

use App\Entity\GeneralesApp;
use App\Entity\Login;
use App\Entity\MetodosLogeo;
use App\Interfaces\ErrorsInterface;
use App\Service\OAuthClientFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use App\Security\GoogleUserProvider;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;
use Symfony\Component\Security\Core\User\UserInterface;
use DateTime;


class GoogleController extends AbstractController
{
    private  $googleProvider;
    private  $jwtManager;
    private  $googleUserProvider;
    private  $em;
    private $parameters;

    private $errorInterface;
  
    public function __construct(
        OAuthClientFactory $googleProvider,
        JWTTokenManagerInterface $jwtManager,
        GoogleUserProvider $googleUserProvider,
        EntityManagerInterface $em,
        ErrorsInterface $errorInterface

    ) {
        $this->googleProvider = $googleProvider;
        $this->jwtManager = $jwtManager;
        $this->googleUserProvider = $googleUserProvider;
        $this->em = $em;
        $this->errorInterface = $errorInterface;
    }

    #[Route('/connect/google', name: 'connect_google_start', methods: ['GET'])]
    #[OA\Tag(name: 'Google')]
    #[OA\Response(
        response: 200,
        description: 'Retorna link login google'
    )]
    #[OA\Parameter(
        name: "redirect_to",
        in: "query",
        description: "carga path de redireccion en la url que retorna google."
    )]
    public function connectAction(Request $request): Response
    {
        $google= $this->em->getRepository(MetodosLogeo::class)->findOneBy(['id' => 1]);
        
        if (!$google || !$google->isEnable()) {
            return $this->errorInterface->error_message(
                'El método de inicio de sesión con Apple no está habilitado.',
                Response::HTTP_BAD_REQUEST
            );
        }
        
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

       // $session = $request->getSession();
        $code = $request->query->get('redirect_to','/');        
        try{
            $googleProvider = $this->googleProvider->getGoogleClient()->getOAuth2Provider();
            $path = $this->encodeURIComponent($code);
            $authorizationUrl = $googleProvider->getAuthorizationUrl([
                'scope' => ['openid', 'email', 'profile'],
                'state' => $path,
            ]);

          /*  $appRedirect = $this->getParameter('app_google_redirect_url');
            $state = $this->googleProvider->getState();

            $session->set('oauth2state',    $state);
            $session->set('oauth2redirect', $appRedirect);*/
    
            return $this->errorInterface->succes_message(
                'Link retornado',
                null,
                ['url' => $authorizationUrl]
            );

            //return new RedirectResponse($authorizationUrl);
        }
        catch (Exception $e){
            return $this->errorInterface->error_message(
            $e->getMessage(),
            Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
        
       
    }

    #[Route('/connect/google/check', name: 'connect_google_check', methods: ['GET'])]
    #[OA\Tag(name: 'Google')]
    #[OA\Response(
        response: 200,
        description: 'Valida session con google y retorna token de inicio de session'
    )]
    #[OA\Parameter(
        name: "code",
        in: "query",
        description: "Dato para validar usario de google."
    )]
    #[OA\Parameter(
        name: "state",
        in: "query",
        description: "Dato para validar usario de google."
    )]
    public function connectCheckAction(Request $request): Response
    {
        try {

          //  $session = $request->getSession();
            $code = $request->query->get('code');
            $state = $request->query->get('state');
            $jwt='';

            if (!$code || !$state) {
                return $this->errorInterface->error_message(
                    'Falta código o estado',
                    Response::HTTP_NO_CONTENT
                );
            }
            

              // 2) Recuperar de sesión lo que guardamos
   /* $storedState    = $session->get('oauth2state');
    $storedRedirect = $session->get('oauth2redirect');
    // Destruirlos para que no se reutilicen
    $session->remove('oauth2state');
    $session->remove('oauth2redirect');

    if (!$storedState || !$storedRedirect) {
        return $this->json(['message'=>'Sesión no válida'], Response::HTTP_FORBIDDEN);
    }

    // 3) Validar el estado CSRF
    if ($state !== $storedState) {
        return $this->json([
            'message'       => 'Error validación de state',
            'expected_state'=> $storedState,
            'received_state'=> $state,
        ], Response::HTTP_FORBIDDEN);
    }

    // 4) Construir la URL absoluta que define tu ruta de callback
     $expectedCallback = $this->generateUrl(
        'connect_google_check',
        [], 
        UrlGeneratorInterface::ABSOLUTE_URL
     );

    // 5) Validar que coincida dominio + ruta
    if (strpos($storedRedirect, $expectedCallback) !== 0) {
        return $this->json([
            'message'            => 'Redirect URL no coincide',
            'expected_callback'  => $expectedCallback,
            'stored_redirect_to' => $storedRedirect,
        ], Response::HTTP_FORBIDDEN);
    }*/
            // Intercambiar el código de autorización por un token de acceso
            $googleProvider = $this->googleProvider->getGoogleClient()->getOAuth2Provider();

            $token = $googleProvider->getAccessToken('authorization_code', [
                'code' => $code,
            ]);

            $googleUser = $googleProvider->getResourceOwner($token);

            // Crear o actualizar el usuario
            $login = $this->googleUserProvider->loadUserByIdentifier(
                $googleUser->getEmail(),
                $googleUser
            );

            if (!$login instanceof UserInterface) {
                return $this->errorInterface->error_message(
                    'El objeto proporcionado no es una instancia de UserInterface.',
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Generar un JWT para el usuario autenticado
             $jwt = $this->jwtManager->create($login);
             $this->log_login($login);
             $data_url= $this->em->getRepository(GeneralesApp::class)->findOneBy(['nombre'=>'front','atributoGeneral'=>'Url']);
             $url = $data_url->getValorGeneral().'/auth/callback?token='.$jwt.'&redirect_to='.$state;
             //return $this->redirect($url);
             return $this->json($url);
        } catch (Exception $e) {
            // Manejo de errores generales
            return $this->errorInterface->error_message(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                null,
                [
                    'code' => $code,
                    'token' => $jwt
                ]
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

<?php

namespace App\Controller;

use App\Entity\Cupon;
use App\Entity\GeneralesApp;
use App\Entity\Login;
use App\Entity\Prospecto;
use App\Entity\Tiendas;
use App\Entity\Usuarios;
use App\Form\LoginType;
use App\Interfaces\ErrorsInterface;
use App\Repository\EstadosRepository;
use App\Form\ResetPasswordRequestFormType;
use App\Repository\LoginRepository;
use App\Repository\PaisRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;

class RegistrationController extends AbstractController
{
    private EmailVerifier $emailVerifier;

    private  $jwtManager;

    private $errorsInterface;

    public function __construct(EmailVerifier $emailVerifier,JWTTokenManagerInterface $jwtManager, ErrorsInterface $errorsInterface )
    {
        $this->emailVerifier = $emailVerifier;
        $this->jwtManager = $jwtManager;
        $this->errorsInterface = $errorsInterface;
    }

    #[Route('/register', name: 'app_register',  methods: ['POST'])]
    #[OA\Tag(name: 'Login')]
    #[OA\RequestBody(
        description: 'Registrar usario',
        content: new  Model(type: LoginType::class)
    )]
    #[OA\Parameter(
        name: "redirect_to",
        in: "query",
        description: "carga path de redireccion en la url que retorna google."
    )]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager,EstadosRepository $estadosRepository,PaisRepository $paisRepository): Response
    {
        
        $allowedParams = [
            'redirect_to'
        ];

        // Obtener todos los parámetros enviados
        $queryParams = array_keys($request->query->all());

        // Detectar parámetros no permitidos
        $invalidParams = array_diff($queryParams, $allowedParams);

         if (!empty($invalidParams)) {
            return $this->errorsInterface->error_message(
                'Parámetros no permitidos',
                Response::HTTP_BAD_REQUEST
            );
        }
        $code = $request->query->get('redirect_to');
        if (!$code){
            $code = '/';
        }

        $path = $this->encodeURIComponent($code);
        $estado= $estadosRepository->findOneBy(['id'=>1]);
        $verificado= $estadosRepository->findOneBy((['id'=>8]));
        $estado_tienda= $estadosRepository->findOneBy(['id'=>4]);
        $estado_biometrico= $estadosRepository->findOneBy(['id'=>16]);
        $pais= $paisRepository->findOneBy(['id'=>1]);
        $content= json_decode($request->getContent());
        $form = $this->createForm(LoginType::class);
        $form->submit((array)$content);

        if (!$form->isValid()) { 
            return $this->errorsInterface->form_errors($form);
        }

      /*  if (!$emailVerifier->verifyEmail($content->email)) {
            return $this->json([
                'message' => 'El correo electrónico no es válido.',
            ])->setStatusCode(Response::HTTP_BAD_REQUEST);
        }*/


        $prospecto = $entityManager->getRepository(Prospecto::class)->findOneBy(['email' => $content->email]);
          
        if ($prospecto) {

            $login = new Login();
            $login->setEmail($content->email);
            $login->setVericacion($verificado);
            $login->setUsername($content->username);
            $login->setEstados($estado);
            $login->setPassword(
                $userPasswordHasher->hashPassword(
                        $login,
                        $content->password
                    )
                );
            
            $usuario= new Usuarios();
            $usuario->setLogin($login);
            $usuario->setEmail($content->email);
            $usuario->setNombre($content->nombre);
            $usuario->setApellido($content->apellido);
            $usuario->setPais($pais);
            $usuario->setEstados($estado_biometrico);
            $usuario->setUsername($content->username);
            $tienda= new Tiendas();
            $tienda->setLogin($login);
            $tienda->setSlug($content->username);
            $tienda->setNombreTienda($content->nombre.' '.$content->apellido);
            $tienda->setEstado($estado_tienda);
            $tienda->setComision(15);
    
            $entityManager->persist($login);
            $entityManager->persist($usuario);
            $entityManager->persist($tienda);
            
            
            $entityManager->flush();

            $id_cupones= $prospecto->getCupon();

            foreach ($id_cupones as $id_cupo) {
                $cupon = $entityManager->getRepository(Cupon::class)->find($id_cupo); // Supongamos que tienes el ID del cupón
                if ($cupon) {
                    $login->addCupon($cupon);
                    $entityManager->flush();
                } else {
                    return $this->errorsInterface->error_message('No se encontró el cupón especificado', Response::HTTP_NOT_FOUND);
                }
            }

            $entityManager->remove($prospecto);
            $entityManager->flush();

             // generate a signed url and email it to the user
             $this->emailVerifier->sendEmailConfirmation('app_verify_email', $login,
             (new TemplatedEmail())
                 ->to($login->getEmail())
                 ->subject('Por favor, activa tu cuenta')
                 ->context([
                     'nombre'=>$content->nombre.' '.$content->apellido,
                 ])
                 ->htmlTemplate('registration/confirmation_email.html.twig'),
                $path
         );
            
            $userData = [
                'email' => $login->getEmail(),
                'estado' => $login->getEstados()->getNobreEstado(),
                'verificacion' =>$login->getVericacion()->getNobreEstado(),   
            ];

            return $this->errorsInterface->succes_message(
                ['text' => 'Guardado', 'level' => 'success'],
                'user',
                $userData
            );


          
        }else{

            $login = new Login();
            $login->setEmail($content->email);
            $login->setVericacion($verificado);
            $login->setUsername($content->username);
            $login->setEstados($estado);
            $login->setPassword(
                $userPasswordHasher->hashPassword(
                        $login,
                        $content->password
                    )
                );
            
            $usuario= new Usuarios();
            $usuario->setLogin($login);
            $usuario->setEmail($content->email);
            $usuario->setNombre($content->nombre);
            $usuario->setApellido($content->apellido);
            $usuario->setPais($pais);
            $usuario->setEstados($estado_biometrico);
            $usuario->setUsername($content->username);
            $tienda= new Tiendas();
            $tienda->setLogin($login);
            $tienda->setSlug($content->username);
            $tienda->setNombreTienda($content->nombre.' '.$content->apellido);
            $tienda->setEstado($estado_tienda);
            $tienda->setComision(15);
            $entityManager->persist($login);
            $entityManager->persist($usuario);
            $entityManager->persist($tienda);
            

            
            $entityManager->flush();

            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $login,
            (new TemplatedEmail())
                ->to($login->getEmail())
                ->subject('Por favor, activa tu cuenta')
                ->context([
                    'nombre'=>$content->nombre.' '.$content->apellido,
                ])
                ->htmlTemplate('registration/confirmation_email.html.twig'),
                $path
                
        );
           
        
            $userData = [
               'email' => $login->getEmail(),
               'estado' => $login->getEstados()->getNobreEstado(),
               'verificacion' =>$login->getVericacion()->getNobreEstado(),   
            ];

            return $this->errorsInterface->succes_message(
                'Guardado',
                'user',
                $userData
            );

        }  
    }


    #[Route('/retry/active/account', name: 'reactivar_cuenta', methods:['POST'])]
    #[OA\Tag(name: 'Login')]
    #[OA\RequestBody(
        description: 'Enviar usario para reactivar cuenta',
        content: new  Model(type: ResetPasswordRequestFormType::class)
    )]
    public function action(Request $request, EntityManagerInterface $entityManager, EmailVerifier $emailVerifier): Response
    {
        $allowedParams = [
            'redirect_to'
        ];

        // Obtener todos los parámetros enviados
        $queryParams = array_keys($request->query->all());

        // Detectar parámetros no permitidos
        $invalidParams = array_diff($queryParams, $allowedParams);

         if (!empty($invalidParams)) {
            return $this->errorsInterface->error_message(
                'Parámetros no permitidos',
                Response::HTTP_BAD_REQUEST
            );
        }
        $code = $request->query->get('redirect_to');
        if (!$code){
            $code = '/';
        }

        $path = $this->encodeURIComponent($code);
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $login = $entityManager->getRepository(Login::class)->findOneBy(['email' => $email,'estados'=>1 ,'vericacion' =>8]);
    
            if (!$login) {
                return $this->errorsInterface->error_message('El email no está registrado o ya está verificado', Response::HTTP_NOT_FOUND);
            }
    
            $emailVerifier->sendEmailConfirmation('app_verify_email', $login,
                (new TemplatedEmail())
                    ->to($login->getEmail())
                    ->subject('Por favor, activa tu cuenta')
                    ->htmlTemplate('registration/retry_email_confirmation.html.twig')
                    ->context([
                        'nombre' => $login->getUsuarios()->getNombre() . ' ' . $login->getUsuarios()->getApellido(),
                    ]),
                $path
            );

            return $this->errorsInterface->succes_message('Correo de reactivacion de cuenta enviado', Response::HTTP_OK);
        }
    
         return $this->errorsInterface->form_errors($form);
    }

    #[Route('/verify/email', name: 'app_verify_email', methods: [ 'GET'])]
    #[OA\Tag(name: 'Login')]
    #[OA\Response(
        response: 200,
        description: 'Verifica el usario con el token de correo electronico',
    )]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator, LoginRepository $userRepository,EntityManagerInterface $entityManager): Response
    {
        $id = $request->get('id');
        $state = $request->get('state', '/');

        if (!$id || $id == '') {
           return $this->errorsInterface->error_message('Datos de usario no proporcionados', Response::HTTP_NOT_FOUND);
        }

        $user = $userRepository->find($id);

        if (!$user) {
            return $this->errorsInterface->error_message('Usuario no encontrado.', Response::HTTP_NOT_FOUND);
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $errorMessage = $translator->trans($exception->getReason(), [], 'VerifyEmailBundle');

          return $this->errorsInterface->error_message($errorMessage, Response::HTTP_CONFLICT);
        }

        if (!$user instanceof UserInterface) {
            return $this->errorsInterface->error_message('El objeto proporcionado no es una instancia de UserInterface.', Response::HTTP_BAD_REQUEST);
        }

        // Generar un JWT para el usuario autenticado
         $jwt = $this->jwtManager->create($user);

        $data_url= $entityManager->getRepository(GeneralesApp::class)->findOneBy(['nombre'=>'front','atributoGeneral'=>'Url']);

         $full_url= $data_url->getValorGeneral().'/auth/callback?token='.$jwt.'&redirect_to='.$state;

         return  $this->redirect($full_url);
    }


    private function encodeURIComponent($str) {
        $revert = array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')');
        return strtr(rawurlencode($str), $revert);
    }
}

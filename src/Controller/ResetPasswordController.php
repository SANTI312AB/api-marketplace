<?php

namespace App\Controller;

use App\Entity\GeneralesApp;
use App\Entity\Login;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Interfaces\ErrorsInterface;
use App\Service\DynamicMailerFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use SymfonyCasts\Bundle\ResetPassword\Persistence\ResetPasswordRequestRepositoryInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;
    private $repository;

    private $errorsInterface;

    private $mailer;

    public function __construct(private ResetPasswordHelperInterface $resetPasswordHelper,private EntityManagerInterface $entityManager,ResetPasswordRequestRepositoryInterface $repository, ErrorsInterface $errorsInterface, DynamicMailerFactory $mailer) 
    {
        $this->repository = $repository;
        $this->errorsInterface = $errorsInterface;
        $this->mailer = $mailer;
    }

    /**
     * Envia correo de recuperacion de contraseña
     */
    #[Route('', name: 'app_forgot_password_request',methods:['POST'])]
    #[OA\Tag(name: 'Login')]
    #[OA\RequestBody(
        description: 'Enviar email para recuperar contraseña',
        content: new  Model(type: ResetPasswordRequestFormType::class)
    )]
    public function request(Request $request, MailerInterface $mailer, TranslatorInterface $translator): Response
    {
        
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            return $this->processSendingPasswordResetEmail(
                $form->get('email')->getData(), 
                $mailer,
                $translator
            );
        }

         return $this->errorsInterface->form_errors($form);
    }

    /*Confirmation page after a user has requested a password reset.
    
    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        // Generate a fake token if the user does not exist or someone hit this page directly.
        // This prevents exposing whether or not a user was found with the given email address or not
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }*/


    #[Route('/reset', name: 'app_reset_password', methods:['POST'])]
    #[OA\Tag(name: 'Login')]
    #[OA\RequestBody(
        description: 'Actualizar contraseña con token de correo enviado',
        content: new  Model(type: ChangePasswordFormType::class)
    )]
    public function reset(Request $request, UserPasswordHasherInterface $passwordHasher, TranslatorInterface $translator, string $token = null,MailerInterface $mailer): Response
    {
    $form = $this->createForm(ChangePasswordFormType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $token = $form->get('token')->getData();
        $password = $form->get('password')->getData();

        if ($token === null) {
            return $this->errorsInterface->error_message('El token de restablecimiento de contraseña no se ha proporcionado.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $errorMessage = sprintf(
                '%s - %s',
                $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, [], 'ResetPasswordBundle'),
                $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            );

             return $this->errorsInterface->error_message($errorMessage,498);
        }

        // Validar si la nueva contraseña es diferente a la anterior
        if ($passwordHasher->isPasswordValid($user, $password)) {
            // La nueva contraseña es igual a la anterior, mostrar mensaje de error
            return $this->errorsInterface->error_message('La nueva contraseña no puede ser igual a la anterior.', Response::HTTP_CONFLICT);
        }

        

        // Encode(hash) the plain password, and set it.
        $encodedPassword = $passwordHasher->hashPassword(
            $user,
            $password
        );

        $user->setPassword($encodedPassword);
        $this->entityManager->flush();


        $this->resetPasswordHelper->removeResetRequest($token);

        $this->cleanSessionAfterReset();

        $email = (new TemplatedEmail())
            ->to($user->getEmail())
            ->subject('Tu contraseña ha sido actualizada')
            ->htmlTemplate('reset_password/email_update.html.twig')
            ->context([
                'nombre' => $user->getUsuarios()->getNombre() . ' ' . $user->getUsuarios()->getApellido(),
            ]);

        $this->mailer->send($email);

         return $this->errorsInterface->succes_message('Contraseña actualizada', Response::HTTP_OK);
    }

         return $this->errorsInterface->form_errors($form);

    }

    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer, TranslatorInterface $translator): Response
    {
       
        $user = $this->entityManager->getRepository(Login::class)->findOneBy([
            'email' => $emailFormData,'estados'=>1, 'vericacion'=>7
        ]);

        if (!$user) {
            return $this->errorsInterface->error_message('Este correo no está registrado', Response::HTTP_NOT_FOUND);
        }
      
        
        $data= $this->repository->getMostRecentNonExpiredRequestDate($user);

        if($data){
            return $this->errorsInterface->error_message('Ya has solicitado un correo de restablecimiento de contraseña, espera 30 minutos para reintentar otra solicitud', Response::HTTP_TOO_MANY_REQUESTS);
        }
        

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            
            $errorMessage = sprintf(
                '%s - %s',
                $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_HANDLE, [], 'ResetPasswordBundle'),
                $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            );
            return $this->errorsInterface->error_message($errorMessage, Response::HTTP_TOO_MANY_REQUESTS);
        }
        $data_url= $this->entityManager->getRepository(GeneralesApp::class)->findOneBy(['nombre'=>'front','atributoGeneral'=>'Url']);

        $front_url= $data_url->getValorGeneral();

        $email = (new TemplatedEmail())
            ->to($user->getEmail())
            ->subject('Tu link para recuperar la contraseña')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'nombre'=>$user->getUsuarios()->getNombre().' '.$user->getUsuarios()->getApellido(),
                'resetToken' => $resetToken,
                'url'=>$front_url
            ]);

        $this->mailer->send($email);

        // Store the token object in session for retrieval in check-email route.
        $this->setTokenObjectInSession($resetToken);
        
        return $this->errorsInterface->succes_message('Email enviado con exito', Response::HTTP_OK);
    }
}

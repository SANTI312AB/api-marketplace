<?php
namespace App\Controller;

use App\Entity\Login;
use App\Interfaces\ErrorsInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Form\ChangePasswordType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;

class ForgotController extends AbstractController
{
    private $errorsInterface;
    public function __construct(ErrorsInterface $errorsInterface)
    {
        $this->errorsInterface = $errorsInterface;
    }

    #[Route('/api/change_password', name: 'change_password', methods: ['POST'])]
    #[OA\Tag(name: 'Login')]
    #[OA\RequestBody(
        description: 'Editar contraseña autenticado',
        content: new Model(type: ChangePasswordType::class)
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(Request $request, UserPasswordHasherInterface $encoder, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if(!$user instanceof Login){
            return $this->errorsInterface->error_message('El usuario no es un Login.', Response::HTTP_UNAUTHORIZED);
        }
        $userInfo = ['password' => null];
        $form = $this->createForm(ChangePasswordType::class, $userInfo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userInfo = $form->getData();
            $oldPassword = $form->get('old_password')->getData();
            $newPassword = $userInfo['password'];

            // Verificar que la contraseña antigua es válida
            if (!$encoder->isPasswordValid($user, $oldPassword)) {
                return $this->errorsInterface->error_message('La contraseña actual es incorrecta.', Response::HTTP_UNAUTHORIZED);
            }

            // Verificar que la nueva contraseña no sea igual a la actual
            if ($encoder->isPasswordValid($user, $newPassword)) {
                return $this->errorsInterface->error_message('La nueva contraseña no puede ser igual a la anterior.', Response::HTTP_CONFLICT);
            }

            // Actualizar la contraseña
            $user->setPassword($encoder->hashPassword($user, $newPassword));
            $entityManager->flush();

            return $this->errorsInterface->succes_message('Contraseña actualizada correctamente.');
        }

        // Manejo de errores
        return $this->errorsInterface->form_errors($form);
    }
}

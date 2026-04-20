<?php   

namespace App\Controller;

use App\Entity\Login;
use App\Interfaces\ErrorsInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;


class LogoutController extends AbstractController
{
    private $entityManager;
    private $errorInterface;

    public function __construct(EntityManagerInterface $entityManager, ErrorsInterface $errorInterface)
    {
        $this->entityManager = $entityManager;
        $this->errorInterface = $errorInterface;
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    #[OA\Tag(name: 'Login')]
    #[Security(name: 'Bearer')]
    #[OA\Response(
        response: 200,
        description: 'Cierra session del usuario.'
    )]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function api_logout(): Response
    {
        $user = $this->getUser();

        if(!$user instanceof Login){
            return $this->errorInterface->error_message(
                'Usuario no válido',
                Response::HTTP_UNAUTHORIZED
            );
        }

        $user->incrementVersion();
        $this->entityManager->flush();


        return $this->errorInterface->succes_message('Sesión cerrada correctamente');
    }

    
    #[Route('/logout', name: 'logout', methods: ['GET'])]
    #[OA\Tag(name: 'Basic Auth')]
    #[OA\Response(
        response: 302,
        description: 'Redirige al login'
    )]
    public function logout()
    {
         // controller can be blank: it will never be called!
        return $this->redirectToRoute('app_inicio');
        
    }

}

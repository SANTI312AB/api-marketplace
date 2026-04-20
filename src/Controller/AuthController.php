<?php

namespace App\Controller;

use App\Form\AuthForm;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;

class AuthController extends AbstractController
{

    #[Route('/authentication_token',name:'app_login', methods:['POST'])]
    #[OA\Tag(name: 'Login')]
    #[OA\RequestBody(
        description: 'Formulario de autenticación',
        content: new Model(type: AuthForm::class)
    )]
    public function loginCheck(Request $request, JWTTokenManagerInterface $jwtManager)
    {
        // Normalmente, LexikJWTAuthenticationBundle maneja este endpoint automáticamente.
        // No necesitas implementar el método si solo estás usando el bundle de Lexik.
        throw new AuthenticationException('This endpoint should be handled automatically by LexikJWTAuthenticationBundle');
    }
}
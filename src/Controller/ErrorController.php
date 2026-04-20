<?php
namespace App\Controller;

use App\Interfaces\ErrorsInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;

class ErrorController extends AbstractController
{
    private $errorInterface;

    public function __construct(ErrorsInterface $errorInterface)
    {
        $this->errorInterface = $errorInterface;
    }

    #[Route('/error', name: 'error', methods: ['GET', 'POST', 'PUT', 'DELETE'])]
    public function index(\Throwable $exception): JsonResponse
    {
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
        $isProd = !$this->getParameter('kernel.debug');

        // Solo preparamos la respuesta segura para el usuario
        $mensaje = $isProd && $statusCode >= 500 ? 'Internal Server Error' : $exception->getMessage();
        $data = !$isProd ? ['trace' => $exception->getTrace()] : null;

        return $this->errorInterface->error_message($mensaje, $statusCode, null, $data);
    }
}
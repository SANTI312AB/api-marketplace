<?php

namespace App\Controller;

use App\Entity\Pedidos;
use App\Form\LogoType;
use App\Interfaces\ErrorsInterface;
use App\Service\FacturadorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;

final class FacturadorController extends AbstractController
{
    private $facturadorService;
    private $errorInterface;

    private $em;

    private $request;

    public function __construct(FacturadorService $facturadorService, ErrorsInterface $errorInterface, EntityManagerInterface $em, RequestStack $requestStack)
    {
        $this->facturadorService = $facturadorService;
        $this->errorInterface = $errorInterface;
        $this->em = $em;
        $this->request = $requestStack->getCurrentRequest();
    }


    #[Route('/facturador/config/logo', name: 'facturador_config_logo', methods: ['POST'])]
    #[OA\Tag(name: 'Factura')]
    #[OA\RequestBody(
        description: 'Añade una imagen de logo al facturador.',
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(
                            property: 'ImageFile', 
                            type: 'file',
                            description: 'Imagen de logo del facturador (JPEG, PNG).'
                        ),
                    ]
                )
            ),
        ]
    )]
    public function configurarLogo()
    {
        $form= $this->createForm(LogoType::class);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('ImageFile')->getData();
            return $this->facturadorService->logo_facturador($file);
        }

        return $this->errorInterface->form_errors($form);
    }

    #[Route('/facturador/config/sign', name: 'facturador_config_sign', methods: ['POST'])]
    #[OA\Tag(name: 'Factura')]
    #[OA\RequestBody(
        description: 'Añade firma del facturador.',
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(
                            property: 'SingFile', 
                            type: 'file',
                            description: 'Archivo de firma del facturador (p12).'
                        ),
                    ]
                )
            ),
        ]
    )]
    public function configurarFirma()
    {
        $form= $this->createForm(LogoType::class);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('SingFile')->getData();
            return $this->facturadorService->config_sing($file);
        }

        return $this->errorInterface->form_errors($form);
    }

    #[Route('/facturador/{pedido}', name: 'app_facturador', methods:['POST'])]
    #[OA\Tag(name: 'Factura')]
    #[OA\Response(
        response: 200,
        description: 'Muestra si se añadió correctamente el pedido al facturador.'
    )]
    public function index($pedido = null): Response
    {
        if (!$pedido) {
            return $this->errorInterface->error_message('Parametro no encontrado', Response::HTTP_BAD_REQUEST);
        }

        $p = $this->em->getRepository(Pedidos::class)->findOneBy(['numero_pedido' => $pedido, 'estado'=>'APPROVED']);
        if (!$p) {
            return $this->errorInterface->error_message('Pedido no encontrado', Response::HTTP_BAD_REQUEST);
        }

      
        return $this->facturadorService->añadir_facturador($p);
        
    }

    #[Route('/facturador/verificar/{claveAcceso}', name: 'app_facturador_verificar', methods: ['GET'])]
    #[OA\Tag(name: 'Factura')]
    #[OA\Response(
        response: 200,
        description: 'Muestra si la factura fue verificada correctamente.'
    )]
    public function verificar_factura(string $claveAcceso = null): Response
    {
        if (empty($claveAcceso)) {
            return $this->errorInterface->error_message(
                'Parametro no encontrado',
                Response::HTTP_BAD_REQUEST
            );
        }

        $resultado = $this->facturadorService->verificar_factura($claveAcceso);

        // Si el servicio falló
        if (isset($resultado['success']) && $resultado['success'] === false) {
            $status = $resultado['status'] ?? Response::HTTP_BAD_REQUEST;
            return $this->json($resultado, $status);
        }

        // Caso exitoso
        return $this->json($resultado, Response::HTTP_OK);
    }

}

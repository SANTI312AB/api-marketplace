<?php

namespace App\Controller;

use App\Entity\Banco;
use App\Form\BancoType;
use App\Interfaces\ErrorsInterface;
use App\Repository\BancoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;

#[Route('/api/cuentas_bancarias')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class BancoController extends AbstractController
{
    private $errorInterface;

    public function __construct(ErrorsInterface $errorInterface)
    {
        $this->errorInterface = $errorInterface;
    }

    #[Route('/', name: 'app_cuentas_index', methods: ['GET'])]
    #[OA\Tag(name: 'Banco')]
    #[OA\Response(
        response: 200,
        description: 'Lista de cuentas bancarias'
    )]
    #[Security(name: 'Bearer')]
    public function index(BancoRepository $bancoRepository): Response
    {
        $user = $this->getUser();
        $bancos = $bancoRepository->findBy(['login' => $user]);
    
        $bancoArray = [];
        foreach ($bancos as $banco) {
            $numeroCuenta = $banco->getNumeroCuenta();
    
            // Solo enmascara si la longitud es >= 4
            if (strlen($numeroCuenta) >= 4) {
                $numeroCuentaOculto = str_repeat('x', strlen($numeroCuenta) - 4) . substr($numeroCuenta, -4);
            } else {
                // No procesar el enmascaramiento (usar el número original)
                $numeroCuentaOculto = $numeroCuenta;
            }
    
            $bancoArray[] = [
                'id' => $banco->getId(),
                'nombre_cuenta' => $banco->getNombreCuenta(),
                'numero_cuenta' => $numeroCuentaOculto,
                'tipo_cuenta' => $banco->getTipoCuenta(),
                'banco' => $banco->getBanco(),
            ];
        }
    
        return $this->json($bancoArray);
    }
    

    #[Route('/new', name: 'app_cuenta_new', methods: ['POST'])]
    #[OA\Tag(name: 'Banco')]
    #[OA\RequestBody(
        description: 'Añadir informacion bancaria para solicitar transacciones',
        content: new  Model(type: BancoType::class)

    )]
    #[Security(name: 'Bearer')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user=$this->getUser();
        $banco = new Banco();
        $form = $this->createForm(BancoType::class, $banco);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
        
            $banco->setLogin($user);
            $entityManager->persist($banco);
            $entityManager->flush();

            return $this->errorInterface->succes_message(
                'Guardado',
                'id_cuenta_banco',
                $banco->getId()
            );
        }

        return $this->errorInterface->form_errors($form); 
    }

    #[Route('/edit/{id}', name: 'app_cuenta_edit', methods: ['PUT'])]
    #[OA\Tag(name: 'Banco')]
    #[OA\RequestBody(
        description: 'Editar informacion bancaria para solicitar transacciones',
        content: new  Model(type: BancoType::class)
    )]
    #[Security(name: 'Bearer')]
    public function edit(Request $request, ?Banco $banco, EntityManagerInterface $entityManager): Response
    {

        if (!$banco) {
            return $this->errorInterface->error_message(
                'No existe el registro',
                Response::HTTP_NOT_FOUND
            );
        }

        $form = $this->createForm(BancoType::class, $banco);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->errorInterface->succes_message(
                'Actualizado',
                'id_cuenta_banco',
                $banco->getId()
            );

        }

        return $this->errorInterface->form_errors($form);
         
    }

     #[Route('/show/{id}', name: 'mirar_cuenta_bancaria',methods:['GET'])]
     #[OA\Tag(name: 'Banco')]
     #[OA\Response(
         response: 200,
         description: 'Mirar un solo registro'
     )]
     #[Security(name: 'Bearer')]
     public function show($id,EntityManagerInterface $entityManager): Response
     {
        $banco= $entityManager->getRepository(Banco::class)->find($id);

        if(!$banco){
            return $this->errorInterface->error_message(
                'No existe el registro',
                Response::HTTP_NOT_FOUND
            );
        }

        $numeroCuenta = $banco->getNumeroCuenta();
            $numeroCuentaOculto = str_repeat('x', strlen($numeroCuenta) - 4) . substr($numeroCuenta, -4);

        $data=[
            'id'=>$banco->getId(),
                'nombre_cuenta'=>$banco->getNombreCuenta(),
                'numero_cuenta'=>$numeroCuentaOculto,
                'tipo_cuenta'=>$banco->getTipoCuenta(),
                'banco'=>$banco->getBanco(),
        ];

        return $this->json($data);
     }

    #[Route('/delete/{id}', name: 'app_cuenta_delete', methods: ['DELETE'])]
    #[OA\Tag(name: 'Banco')]
    #[OA\Response(
        response: 200,
        description: 'Elimina un registro de informacion bancaria'
    )]
    #[Security(name: 'Bearer')]
    public function delete(Request $request, ?Banco $banco, EntityManagerInterface $entityManager): Response
    {   
            if(!$banco) {
                return $this->errorInterface->error_message(
                    'No existe el registro',
                    Response::HTTP_NOT_FOUND
                );
            }

            $entityManager->remove($banco);
            $entityManager->flush();

        return $this->errorInterface->succes_message(
            'Eliminado'
        );

    }

             
}

<?php

namespace App\Controller;

use App\Entity\GeneralesApp;
use App\Entity\Login;
use App\Entity\Productos;
use App\Entity\Tiendas;
use App\Entity\Usuarios;
use App\Form\BiometricoType;
use App\Form\DocumentosType;
use App\Form\UsuariosType;
use App\Interfaces\ErrorsInterface;
use App\Interfaces\ProfileInterface;
use App\Repository\DetallePedidoRepository;
use App\Repository\EstadosRepository;
use App\Repository\ProductosComentariosRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use App\Entity\Factura;

class LoginController extends AbstractController
{
    private $errorInterface;

    private $profileInterface;

    public function __construct( ErrorsInterface $errorInterface, ProfileInterface $profileInterface)
    {
        $this->errorInterface = $errorInterface;
        $this->profileInterface = $profileInterface;
    }   
   
    
    #[Route('/api/login/show', name: 'show_login', methods: ['GET'])]
    #[OA\Tag(name: 'Login')]
    #[OA\Response(
        response: 200,
        description: 'Datos de usuario'
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function show(): Response
    {
         $user= $this->getUser();
         return $this->profileInterface->private_profile($user);
    }


    #[Route('/vendedor/{username}', name: 'show_vendedor',methods:['GET'])]
    #[OA\Tag(name: 'Tienda')]
    #[OA\Response(
        response: 200,
        description: 'Vista de tienda del vendedor por username',
    )]
    public function show_vendedor(EntityManagerInterface $em,$username=null): Response
    {
        if(!$username){
            return $this->errorInterface->error_message('No hay parametro.',Response::HTTP_BAD_REQUEST);
        }

        $user= $em->getRepository(Login::class)->findOneBy(['username'=>$username]);

        if(!$user){
             return $this->errorInterface->error_message('Usuario no encontrado.',Response::HTTP_NOT_FOUND);
        }

        if($user->getEstados()->getId() !== 1){
             return $this->errorInterface->error_message('Usuario bloqueado.',Response::HTTP_CONFLICT);
        }

        if($user->getVericacion()->getId() !== 7){
             return $this->errorInterface->error_message('Usuario no verificado.',Response::HTTP_CONFLICT);
        }

        return $this->profileInterface->public_profile($user);

    }

    


    #[Route('/api/login/edit', name: 'app_login_edit', methods: ['PATCH'])]
    #[OA\Tag(name: 'Login')]
    #[OA\RequestBody(
        description: 'Edita informacion del usuario',
        content: new  Model(type: UsuariosType::class)
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $login = $entityManager->getRepository(Login::class)->find($user);
        $tienda= $entityManager->getRepository(Tiendas::class)->findOneBy(['login'=>$user]);
        $usuario= $entityManager->getRepository(Usuarios::class)->findOneBy(['login'=>$login]);
    
        
        $content = json_decode($request->getContent());

        $form = $this->createForm(UsuariosType::class);
    
        $form->submit((array)$content);

        if ($form->isValid()) {
            $login->setEmail($content->email);
            $login->setUsername($content->username);
            $tienda->setSlug($content->username);
            $usuario->setEmail($content->email);
            $usuario->setUsername($content->username);
            $usuario->setTipoDocumento($content->tipo_documento);
            $usuario->setDni($content->dni);
            $usuario->setNombre($content->nombre);
            if($content->apellido !==''){
                $usuario->setApellido($content->apellido);
            }
            $usuario->setCelular($content->celular);

            if($content->genero !== ''){
                $usuario->setGenero($content->genero);
            }

            if($content->fecha_nacimiento !== '' ){

                 $fechaNacimiento = new DateTime($content->fecha_nacimiento);
                 $usuario->setFechaNacimiento($fechaNacimiento);
            }
           
    
            try {
                $entityManager->flush();

                 $this->add_factura($entityManager,$content->nombre,$content->apellido,$content->email,$content->celular,$content->dni,$user  );

                return $this->errorInterface->succes_message(
                    'Usuario actualizado correctamente'
                );
            } catch (\Exception $e) {
                return $this->errorInterface->error_message(
                    'Error al actualizar los datos',
                    $e->getMessage(),Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    
        // Form is not valid, handle form validation errors
         return $this->errorInterface->form_errors($form);

    }


    private function add_factura(EntityManagerInterface $entityManager, $nombre, $apellido, $email, $celular,$dni, Login $user ){

        $factura= $entityManager->getRepository(Factura::class)->findOneBy(['login' => $user]);
        
        // Si no se ha proporcionado un $facturaID y no existe ninguna factura, crear una nueva
        if ( $factura === null) {
            $factura = new Factura();
            $factura->setEmail($email); // Variable original
            $factura->setLogin($user); // Variable original
            $factura->setNombre($nombre); // Variable original
            $factura->setApellido($apellido); // Variable original
            $factura->setTelefono($celular); // Variable original
            $factura->setDni($dni); // Variable original
            
            $entityManager->persist($factura); // Persistir la nueva factura
            $entityManager->flush(); // Guardar cambios en la base de datos
    
        }else{

            $factura = $entityManager->getRepository(Factura::class)->findOneBy(['login' => $user],['fecha'=>'ASC']);
            
            $factura->setEmail($email); 
            $factura->setNombre($nombre); // Variable original
            $factura->setApellido($apellido); // Variable original
            $factura->setTelefono($celular); // Variable original
            $factura->setDni($dni); // Variable original
            $entityManager->flush(); // Guardar cambios en la base de datos
        }

    }


    #[Route('/api/login/add_documents', name: 'app_login_documents', methods: ['POST'])]
    #[OA\Tag(name: 'Login')]
    #[OA\RequestBody(
        description: 'Añade documentos de usuario al perfil.',
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(properties: [
                    new OA\Property(
                        property: 'selfie',
                        type: 'file'
                    ),
                    new OA\Property(
                        property: 'foto_documento',
                        type: 'file'
                    ),
                ])
            ),
        ]
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function add_documentos(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
    
        $login = $entityManager->getRepository(Login::class)->find($user);
        $usuario= $entityManager->getRepository(Usuarios::class)->findOneBy(['login'=>$user]);

        if (!$login) {
           return $this->json([
               'message' => 'Login no encontrado',
           ])->setStatusCode(Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(DocumentosType::class);
        $form->handleRequest($request);

       


        if($form->isSubmitted() && $form->isValid()){

        
             $selfie= $form->get('selfie')->getData();

             $foto_documento= $form->get('foto_documento')->getData();


             $slug = str_replace(' ', '-', $usuario->getNombre());

             // Convierte el slug a minúsculas.
             $slug = strtolower($slug);
 
             // Elimina caracteres especiales y acentos.
             $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
 
              // Elimina guiones duplicados.
             $slug = preg_replace('/-+/', '-', $slug);

            

             if ($selfie instanceof UploadedFile) {
                // Genera un nombre único para el archivo
                $nombreArchivo =  $slug.'-'.uniqid() .'.'. $selfie->guessExtension();
                
                // Mueve el archivo al directorio de almacenamiento
                $directorioAlmacenamiento = $this->getParameter('images_selfie');
                $selfie->move($directorioAlmacenamiento, $nombreArchivo);
                $usuario->setSelfie($nombreArchivo);     
               }  
               
              if ($foto_documento instanceof UploadedFile) {
                $nombreArchivo = $slug.'-'.uniqid() .'-cedula.'. $foto_documento->guessExtension();      
                $directorioAlmacenamiento = $this->getParameter('images_selfie');
                $foto_documento->move($directorioAlmacenamiento, $nombreArchivo);
                $usuario->setFotoDocumento($nombreArchivo);     

               } 

              
             $entityManager->flush();

             return $this->errorInterface->succes_message(
                 'Documentos añadidos correctamente'
             );
             
        }

        return $this->errorInterface->form_errors($form);

    }


    #[Route('/api/login/add_avatar', name: 'app_login_avatar', methods: ['POST'])]
    #[OA\Tag(name: 'Login')]
    #[OA\RequestBody(
        description: 'Añade una imagen de perfil de usuario.',
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(
                            property: 'avatar', 
                            type: 'file',
                            description: 'Imagen avatar de usuario'
                        ),
                    ]
                )
            ),
        ]
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function add_avatar(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $login = $entityManager->getRepository(Login::class)->find($user);
        $usuario = $entityManager->getRepository(Usuarios::class)->findOneBy(['login' => $login]);
    
        if (!$login) {
            return $this->errorInterface->error_message(
                'Login no encontrado',
                Response::HTTP_NOT_FOUND
            );
        }
    
        $form = $this->createForm(DocumentosType::class, $usuario);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Verificar si el campo avatar está establecido como null
            if ($form->get('avatar')->getData() === null) {
                $usuario->setAvatar(null);
            } else {
                $avatar = $form->get('avatar')->getData();
    
                $slug = str_replace(' ', '-', $usuario->getNombre());
                $slug = strtolower($slug);
                $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
                $slug = preg_replace('/-+/', '-', $slug);
    
                $nombreArchivo = $slug . '-' . uniqid() . '.' . $avatar->guessExtension();
                $directorioAlmacenamiento = $this->getParameter('images_selfie');
    
                // Si el usuario ya tenía un avatar, elimina el archivo anterior
                if ($usuario->getAvatar()) {
                    $archivoAnterior = $directorioAlmacenamiento . '/' . $usuario->getAvatar();
                    if (file_exists($archivoAnterior)) {
                        unlink($archivoAnterior);
                    }
                }
    
                $avatar->move($directorioAlmacenamiento, $nombreArchivo);
                $usuario->setAvatar($nombreArchivo);
            }
    
            $entityManager->flush();
    
              return $this->errorInterface->succes_message(
                'Avatar actualizado correctamente'
             );
        }
    
         return $this->errorInterface->form_errors($form);
    }
    

    #[Route('/api/biometrico', name: 'conectar_biometrico', methods:['POST'])]
    #[OA\Tag(name: 'Biometrico')]
    #[OA\Response(
        response: 200,
        description: 'Retorna url para validacion biometrica del usario',
    )]
    #[OA\Parameter(
        name:"origen",
        in:"query",
        description:"Especifica a qué URL retornar para la API de biométrico"
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function action(Request $request, EntityManagerInterface $entityManager): Response
    {
        $allowedParams = [
            'origen'
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
        $generales= $entityManager->getRepository(GeneralesApp::class);
        $data_url=$generales->findOneBy(['nombre'=>'front','atributoGeneral'=>'Url']);
         // Para obtener el cliente
        $datosBiometrico = $generales->findBy(['nombre' => 'biometrico']);

        // Inicializas las variables
        $cliente = null;
        $password = null;
        $apiUrl = null;

        foreach ($datosBiometrico as $dato) {
            switch ($dato->getAtributoGeneral()) {
                case 'Login':
                    $cliente = $dato->getValorGeneral();
                    break;
                case 'SecretKey':
                    $password = $dato->getValorGeneral();
                    break;
                case 'Url':
                    $apiUrl = $dato->getValorGeneral();
                    break;
            }
        }
        $origen = $request->query->get('origen','/');
        $user = $this->getUser();
        $usuario = $entityManager->getRepository(Usuarios::class)->findOneBy(['login' => $user]);
    
        if (!$usuario->getDni()) {
            return $this->errorInterface->error_message(
                'Para validar su información, es necesario que complete su información de perfil con todos sus datos personales.',
                Response::HTTP_BAD_REQUEST
            );
        }

        if($usuario->getTipoDocumento() !== 'CI'){
            return $this->errorInterface->error_message(
                'Especifique el tipo de DNI como cedula para utilizar el biométrico.',
                Response::HTTP_BAD_REQUEST
            );
        }
    
        if (!$this->isValidCedula($usuario->getDni())) {

            return $this->errorInterface->error_message(
                'Su número de identificación ' . $usuario->getDni() . ' no es válido, por favor actualice su información e intente de nuevo.',
                Response::HTTP_BAD_REQUEST
            );
        }
    
        $intentos = $usuario->getLimiteBiometrico();
        if ($intentos >= 3) {
            return $this->errorInterface->error_message(
                'Límite de intentos alcanzado al verificar su identidad, por favor intente luego de 24 horas.',
                Response::HTTP_BAD_REQUEST
            );
        }
    
        $dni = $usuario->getDni();
        
    
        // Iniciar la petición cURL
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); 
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$cliente:$password");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            "identificacion" => $dni,
            "url_retorno" => $data_url->getValorGeneral() . $origen
        ]));
    

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
        // Manejo de errores de cURL
        if (curl_errno($ch)) {
            curl_close($ch);
            return $this->json([
                'message' => 'Error cURL: ' . curl_error($ch), 
                'code' => $httpCode
            ])->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    
        curl_close($ch);
    
        // Si la respuesta no es 200, manejar el error
        if ($httpCode != 200) {
            return $this->errorInterface->error_message(
                'Respuesta cURL ' . $httpCode . ': ' . $response,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    
        // Intentar decodificar la respuesta JSON
        $api_response = json_decode($response, true);
    
        // Verificar si la respuesta es un JSON válido
        
    
        // Actualizar el intento de biometría
        $usuario->setHasVerified(true);
        $usuario->setLimiteBiometrico($intentos + 1);
        $usuario->setFechaBiometrico(new DateTime());
        $entityManager->flush();
    

        return $this->json($api_response);
    }
    

    #[Route('/biometrico_validacion', name: 'biometrico_validacion', methods:['POST'])]
    #[OA\Tag(name: 'Biometrico')]
    #[OA\RequestBody(
        description: 'Actualizar usuario despues de validacion de biometrico',
        content: new  Model(type: BiometricoType::class)
    )]
    public function biometrico(Request $request,EntityManagerInterface $entityManager,EstadosRepository $estadosRepository): Response
    {
               
    $form = $this->createForm(BiometricoType::class);
    $form->handleRequest($request);


    // Check if form is submitted and valid
    if ($form->isSubmitted() && $form->isValid()) {
        
        // Get values from the form
        $dni = $form->get('dni')->getData();

        if (!$this->isValidCedula($dni)) {
            return $this->errorInterface->error_message(
                'Su número de identificación ' . $dni . ' no es válido, por favor actualice su información e intente de nuevo.',
                Response::HTTP_BAD_REQUEST
            );
        }

        $valor = $form->get('verificado')->getData() ? $form->get('verificado')->getData():"0";

        $v = filter_var($valor, FILTER_VALIDATE_BOOLEAN) ? filter_var($valor, FILTER_VALIDATE_BOOLEAN):false ;

        if($v === null){
            return $this->errorInterface->error_message(
                'Faltan datos en los campos.',
                Response::HTTP_BAD_REQUEST,
                'verificado',
                'El campo Validación está vacío.'
            );
        } else if ($dni === null) {
            return $this->errorInterface->error_message(
                'Faltan datos en los campos.',
                Response::HTTP_BAD_REQUEST,
                'dni',
                'El campo dni está vacío.'
            );
        }
        
        // If both fields are filled, proceed with your logic
        $usuario = $entityManager->getRepository(Usuarios::class)->findOneBy(['dni' => $dni]);

        if (!$usuario) {
            return $this->errorInterface->error_message(
                'Usuario no encontrado',
                Response::HTTP_NOT_FOUND
            );
        }

        if ($v) {
            $estadoActual = $usuario->getEstados();
            
            if ($estadoActual->getId() === 16) {
                $estado_biometrico = $estadosRepository->findOneBy(['id' => 15]);
                $usuario->setEstados($estado_biometrico);
                $usuario->setRequiereBiometrico(false);
            
                $entityManager->flush();

                return $this->errorInterface->succes_message(
                    'Usuario verificado correctamente'
                );
            }

            return $this->errorInterface->error_message(
                'El usuario ya está verificado',
                Response::HTTP_BAD_REQUEST
            );
        } else {
            $estado_biometrico = $estadosRepository->findOneBy(['id' => 16]);
            $usuario->setEstados($estado_biometrico);
            $entityManager->flush();

            return $this->errorInterface->error_message(
                "Usuario no verificado",
                Response::HTTP_BAD_REQUEST
            );
        }
    }

      return $this->errorInterface->form_errors($form);

    }

    private function isValidCedula($value): bool
    {
        // Eliminar caracteres no numéricos de la cédula
        $cedula = preg_replace('/[^0-9]/', '', $value);

        if (strlen($cedula) !== 10) {
            return false;
        }

        $provinceDigits = (int) substr($cedula, 0, 2);
        $thirdDigit = (int) $cedula[2];
        $checkDigit = (int) $cedula[9];

        if ($provinceDigits < 0 || $provinceDigits > 24 || $thirdDigit >= 6) {
            return false;
        }

        $sum = 0;
        $coefficients = [2, 1, 2, 1, 2, 1, 2, 1, 2];

        for ($i = 0; $i < 9; $i++) {
            $digit = (int) $cedula[$i];
            $product = $digit * $coefficients[$i];
            $sum += ($product > 9) ? $product - 9 : $product;
        }

        $totalSum = $sum % 10;
    
        // Comprobar si la cédula es válida

        if (($totalSum === 0 && $checkDigit === 0) || $totalSum === 10 - $checkDigit){
            return true;
        }

        return false;
    }
    
}

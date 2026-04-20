<?php

namespace App\Controller;


use App\Entity\Estados;
use App\Entity\Factura;
use App\Entity\GeneralesApp;
use App\Entity\Login;
use App\Entity\MetodosEnvio;
use App\Entity\MetodosPago;
use App\Entity\Pedidos;
use App\Entity\Productos;
use App\Entity\Regateos;
use App\Entity\Tiendas;
use App\Entity\Usuarios;
use App\Entity\UsuariosDirecciones;
use App\Entity\Variaciones;
use App\Form\PayMetodType;
use App\Form\RegateoPType;
use App\Form\RegateoType;
use App\Interfaces\ErrorsInterface;
use App\Service\DynamicMailerFactory;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use App\Service\PaypalService;
use App\Service\PlacetoPayService;
use App\Service\CarritoService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use App\Service\GuardarPedidoService;


final class RegateoController extends AbstractController
{
    private $em;
    private $request;

    private $placetoPayService;

    private $paypalService;  

    private $carritoService;

    private $mailer;

    private $guardar_pedido;

    private $errorsInterface;


    public function __construct(EntityManagerInterface $em, RequestStack $request,PlacetoPayService $placetoPayService, PaypalService $paypalService,CarritoService $carritoService,DynamicMailerFactory $mailer,GuardarPedidoService $guardar_pedido,ErrorsInterface $errorsInterface)
    {
        $this->em = $em;
        $this->request = $request->getCurrentRequest();
        $this->placetoPayService = $placetoPayService;  // Injecting PlacetoPayService into the controller.
        $this->paypalService = $paypalService;  // Injecting PaypalService into the controller.
        $this->carritoService = $carritoService;  // Injecting CarritoService into the controller.
        $this->mailer = $mailer;  // Injecting MailerInterface into the controller.
        $this->guardar_pedido = $guardar_pedido;  // Injecting GuardarPedidoService into the controller.
        $this->errorsInterface= $errorsInterface;
    }

    #[Route('/api/mis_regateos', name: 'app_mis_regateos',methods: ['GET'])]
    #[OA\Tag(name: 'Regateo')]
    #[OA\Response(
        response: 200,
        description: 'Lista de regateos de un cliente.',
    )]
    public function index(): Response
    {
        $user= $this->getUser();
        $regateos = $this->em->getRepository(Regateos::class)->findBy(['login' => $user]);

        $data=[];

        foreach ($regateos as $regateo){     
            
            $agrupadosPorAtributo = [];
            $variacion= $regateo->getVariacion();
   
    
            if ($variacion) {
            
                foreach ($variacion->getTerminos() as $termino) {
                    $atributoId = $termino->getAtributos()->getId();

                    $variacionData = [
                        'id' => $variacion->getId(),
                        'sku' => $variacion->getSku(),
                        'cantidad' => $variacion->getCantidad(),
                        'descripcion' => $variacion->getDescripcion(),
                        'variacionesGalerias' => ''
                    ];

                    $terminoData = [
                        'id' => $termino->getId(),
                        'nombre' => $termino->getNombre(),
                        'codigo' => $termino->getCodigo(),
                        'variaciones' => [$variacionData]
                    ];

                    if (!isset($agrupadosPorAtributo[$atributoId])) {
                        $agrupadosPorAtributo[$atributoId] = [
                            'id_atributo' => $atributoId,
                            'nombre_atributo' => $termino->getAtributos()->getNombre(),
                            'terminos' => []
                        ];
                    }

                    $termExists = false;
                    foreach ($agrupadosPorAtributo[$atributoId]['terminos'] as &$existingTermino) {
                        if ($existingTermino['id'] === $terminoData['id']) {
                            $termExists = true;
                            $existingTermino['variaciones'][] = $variacionData;
                        }
                    }

                    if (!$termExists) {
                        $agrupadosPorAtributo[$atributoId]['terminos'][] = $terminoData;
                    }
                }
            }

            $agrupadosPorAtributo = array_values($agrupadosPorAtributo);
            $data[] = [
                'id' => $regateo->getId(),
                'id_producto' => $regateo->getProducto()->getId(),
                'id_variacion' => $regateo->getVariacion() ? $regateo->getVariacion()->getId() : '',
                'nombre_producto' => $regateo->getProducto()->getNombreProducto(),  
                'atributos' => $agrupadosPorAtributo,
                'fecha_registro' => $regateo->getFecha()->format('Y-m-d H:i:s'),
                'fecha_edicion' => $regateo->getFechaEdicion() ? $regateo->getFechaEdicion()->format('Y-m-d H:i:s') : '',
                'estado' => $regateo->getEstado(),
                'codigo'=> $regateo->getNRegateo(),
                'entregas_tipo'=>$regateo->getProducto()->getEntrgasTipo()->getTipo()
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/regateo/{regateo}', name: 'app_ver_regateo',methods: ['GET'])]
    #[OA\Tag(name: 'Regateo')]
    #[OA\Response(
        response: 200,
        description: 'Detalle de un regateo.',
    )]
    public function show(Regateos $regateo = null): Response
    {
        $user= $this->getUser();
        if (!$regateo  ||($regateo &&  $regateo->getLogin() !== $user) ) {
           return $this->errorsInterface->error_message('Regateo no encontrado.', Response::HTTP_NOT_FOUND);
        }

        $agrupadosPorAtributo = [];
        $variacion= $regateo->getVariacion();


        if ($variacion) {
        
            foreach ($variacion->getTerminos() as $termino) {
                $atributoId = $termino->getAtributos()->getId();

                $variacionData = [
                    'id' => $variacion->getId(),
                    'sku' => $variacion->getSku(),
                    'cantidad' => $variacion->getCantidad(),
                    'descripcion' => $variacion->getDescripcion(),
                    'variacionesGalerias' => ''
                ];

                $terminoData = [
                    'id' => $termino->getId(),
                    'nombre' => $termino->getNombre(),
                    'codigo' => $termino->getCodigo(),
                    'variaciones' => [$variacionData]
                ];

                if (!isset($agrupadosPorAtributo[$atributoId])) {
                    $agrupadosPorAtributo[$atributoId] = [
                        'id_atributo' => $atributoId,
                        'nombre_atributo' => $termino->getAtributos()->getNombre(),
                        'terminos' => []
                    ];
                }

                $termExists = false;
                foreach ($agrupadosPorAtributo[$atributoId]['terminos'] as &$existingTermino) {
                    if ($existingTermino['id'] === $terminoData['id']) {
                        $termExists = true;
                        $existingTermino['variaciones'][] = $variacionData;
                    }
                }

                if (!$termExists) {
                    $agrupadosPorAtributo[$atributoId]['terminos'][] = $terminoData;
                }
            }
        }

        $agrupadosPorAtributo = array_values($agrupadosPorAtributo);
        $data = [
            'id' => $regateo->getId(),
            'id_producto' => $regateo->getProducto()->getId(),
            'id_variacion' => $regateo->getVariacion() ? $regateo->getVariacion()->getId() : '',
            'nombre_producto' => $regateo->getProducto()->getNombreProducto(),  
            'atributos' => $agrupadosPorAtributo,
            'fecha_registro' => $regateo->getFecha()->format('Y-m-d H:i:s'),
            'fecha_edicion' => $regateo->getFechaEdicion() ? $regateo->getFechaEdicion()->format('Y-m-d H:i:s') : '',
            'estado' => $regateo->getEstado(),
            'regateo' => $regateo->getRegateo(),
        ];

        return $this->json($data);
    }

    #[Route('/api/regateo', name: 'app_crear_regateo', methods: ['POST'])]
    #[OA\Tag(name: 'Regateo')]
    #[OA\RequestBody(
        description:  'Crear un regateo.',
        content: new  Model(type: RegateoType::class)
    )]
    public function create(): Response
    {
        $user = $this->getUser();
        if($user instanceof Login){
          $tienda = $user->getTiendas()->getId();
          $nombre_cliente= $user->getUsuarios()->getNombre();
          $apellido_cliente= $user->getUsuarios()->getApellido();
        }
        
        $regateo= new Regateos();
        $form = $this->createForm(RegateoType::class, $regateo,[
            'current_tienda_id' => $tienda,
        ]);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {

            $producto= $form->get('producto')->getData();
            $variacion= $form->get('variacion')->getData();
    
            if($producto instanceof Productos){
                $email_vendor= $producto->getTienda()->getLogin()->getEmail();
                $nombre_vendedor= $producto->getTienda()->getLogin()->getUsuarios()->getNombre();
            }
            
            $arrayterminos=[];
    
            if($variacion && $variacion instanceof Variaciones){
                foreach ($variacion->getTerminos() as $termino){
                    $arrayterminos[]=[
                        'nombre'=>$termino->getNombre(),
                    ];
                }
            }

            $regateo->setLogin($user);
            $regateo->setNRegateo('RGT-'.rand(0000,9999));
            $regateo->setEstado('PENDING');
            $this->em->persist($regateo);
            $this->em->flush();

            $eml = (new TemplatedEmail())
            ->to($email_vendor)
            ->subject('Un cliente quiere regatear un producto.')
            ->htmlTemplate('regateo/regateo_notificacion_vendedor.html.twig')
            ->context([
                'nombre_vendedor'=> $nombre_vendedor,
                'producto'=>$producto,
                'terminos'=>$arrayterminos,
                'cliente'=>sprintf('%s %s', $nombre_cliente, $apellido_cliente),
                'valor'=>$regateo->getRegateo(),
                'codigo'=>$regateo->getNRegateo()
            ]);

            $this->mailer->send($eml);


              return $this->errorsInterface->succes_message('Regateo creado exitosamente.');
        }

        return $this->errorsInterface->form_errors($form);

    }


    #[Route('/api/regateo/{regateo}', name: 'app_editar_regateo', methods: ['PUT'])]
    #[OA\Tag(name: 'Regateo')]
    #[OA\RequestBody(
        description: 'Editar un regateo.',
        content: new  Model(type: RegateoType::class)
    )]
    public function edit(Regateos $regateo = null): Response
    {
        $user = $this->getUser();
        $tienda = $user->getTiendas()->getId();
        if (!$regateo  ||($regateo &&  $regateo->getLogin() !== $user) ) {
            return $this->errorsInterface->error_message('Regateo no encontrado.', Response::HTTP_NOT_FOUND);
        }

        if($regateo->getFecha()->diff(new DateTime())->h > 12){
            return $this->errorsInterface->error_message('No se puede editar un regateo después de 12 horas.', Response::HTTP_BAD_REQUEST);
        }

        if($regateo->getEstado() === 'APPROVED' || $regateo->getEstado() === 'REJECTED' || $regateo->getEstado() === 'USED'){
            return $this->errorsInterface->error_message('No se puede editar un regateo aprobado o rechazado.', Response::HTTP_BAD_REQUEST);
        }

        $form = $this->createForm(RegateoType::class, $regateo,[
            'current_tienda_id' => $tienda,
        ]);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

             return $this->errorsInterface->succes_message('Regateo actualizado exitosamente.');
        }

          return $this->errorsInterface->form_errors($form); 
    }


    #[Route('/api/regateo/{regateo}', name: 'app_eliminar_regateo', methods: ['DELETE'])]
    #[OA\Tag(name: 'Regateo')]
    #[OA\Response(
        response: 200,
        description: 'Regateo eliminado exitosamente.',
    )]
    public function delete(Regateos $regateo = null): Response
    {
        $user = $this->getUser();
        if (!$regateo  ||($regateo &&  $regateo->getLogin() !== $user) ) {
            return $this->errorsInterface->error_message('Regateo no encontrado.', Response::HTTP_NOT_FOUND);
        }

        if($regateo->getFecha()->diff(new DateTime())->h > 12){
            return $this->errorsInterface->error_message('No se puede eliminar un regateo después de 12 horas.', Response::HTTP_BAD_REQUEST);
        }

        if($regateo->getEstado() === 'APPROVED' || $regateo->getEstado() === 'REJECTED' || $regateo->getEstado() === 'USED'){
            return $this->errorsInterface->error_message('No se puede eliminar un regateo aprobado o rechazado.', Response::HTTP_BAD_REQUEST);
        }

        $this->em->remove($regateo);
        $this->em->flush();

         return $this->errorsInterface->succes_message('Regateo eliminado exitosamente.');
    }

    #[Route('/api/regateo/{regateo}/aprobar', name: 'app_aprobar_regateo', methods: ['PUT'])]
    #[OA\Tag(name: 'Regateo')]
    #[OA\RequestBody(
        description: 'Aprovar un regateo.',
        content: new  Model(type: RegateoPType::class)
    )]
    public function approve(Regateos $regateo = null): Response
    {
        $user = $this->getUser();
        $tienda = $user->getTiendas();

        if (!$regateo  ||($regateo &&  $regateo->getProducto()->getTienda() !== $tienda) ) {
            return $this->errorsInterface->error_message('Regateo no encontrado.', Response::HTTP_NOT_FOUND);
        }

        if($regateo->getEstado() === 'APPROVED' || $regateo->getEstado() === 'REJECTED' ||  $regateo->getEstado() === 'USED'){
            return $this->errorsInterface->error_message('No se puede actualizar un regateo aprobado o rechazado.', Response::HTTP_BAD_REQUEST);
        }

        if ($regateo->getLogin() instanceof Login){
            $email_cliente= $regateo->getLogin()->getEmail();
            $nombre_cliente= $regateo->getLogin()->getUsuarios()->getNombre();
            $apellido_cliente= $regateo->getLogin()->getUsuarios()->getApellido();
        }

        $form= $this->createForm(RegateoPType::class, $regateo);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
            $regateo->setFechaEdicion(new DateTime());
            $this->em->flush();

            $eml = (new TemplatedEmail())
            ->to($email_cliente)
            ->subject('Tu regateo fue aceptado.')
            ->htmlTemplate('regateo/regateo_notificacion_cliente.html.twig')
            ->context([
                'nombre_cliente'=> $nombre_cliente.'' .$apellido_cliente,
                'estado'=>$regateo->getEstado(),
                'codigo'=>$regateo->getNRegateo()
            ]);

            $this->mailer->send($eml);
            return $this->errorsInterface->succes_message('Estado de regateo actualizado.');
        }

        return $this->errorsInterface->form_errors($form);

    }


    #[Route('/api/regateo/orden/{regateo}', name: 'app_regateo_orden', methods: ['POST'])]
    #[OA\Tag(name: 'Regateo')]
    #[OA\Response(
        response: 200,
        description: 'Generar venta en Shopby',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Venta generada con éxito.'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'url', type: 'string'),
             
                ]),
            ]
        )
    )]
    #[OA\Response(
        response: 409,
        description: 'Error al generar la venta.(para ventas en estados pedientes).',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Error en la validación de los datos'),
                new OA\Property(property: 'errors', type: 'array', items: new OA\Items(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'description', type: 'string'),
                        new OA\Property(property: 'action', type: 'string' , example: 'Url de redireccion pago pendiente.'),
                    ]
                )),
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Error al generar la venta.(todo el resto de errores).',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Error en la validación de los datos'),
                new OA\Property(property: 'errors', type: 'array', items: new OA\Items(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'description', type: 'string')
                    ]
                )),
            ]
        )
    )]
    #[OA\RequestBody(
        description: 'Crear una orden a partir de un regateo.',
        content: new  Model(type: PayMetodType::class)
    )]
    public function order(Regateos $regateo = null): Response
    {
        $user = $this->getUser();
        $regateo_user = $regateo->getLogin();

        if (!$regateo  ||($regateo &&  $regateo->getLogin() !== $regateo_user) ) {
            return $this->errorsInterface->error_message('Regateo no encontrado.',Response::HTTP_NOT_FOUND);
        }

        if($regateo->getEstado() === 'REJECTED' || $regateo->getEstado() === 'PENDING' || $regateo->getEstado() === 'USED'){
           return $this->errorsInterface->error_message('No se puede crear un pedido de regateo pendiente o rechazado.',Response::HTTP_BAD_REQUEST);
        }

        $form= $this->createForm(PayMetodType::class);
        $form->handleRequest($this->request);

        if (!$form->isValid()) {
            return $this->errorsInterface->form_error($form, 'Error en la validación de los datos del pago.');
        }

        $ingresado= $this->em->getRepository(Estados::class)->findOneBy(['id'=>19]);

        $metodo_pago=$form->get('metodo_pago')->getData();
        $facturaID = $form->get('factura_id')->getData();
        $direccion= $form->get('direccion_id')->getData();
        $metodo_envio=$form->get('metodo_envio')->getData();


         
        if ($metodo_pago instanceof MetodosPago) {
            // Trabaja con el objeto directamente
            $idMetodo = $metodo_pago->getId();
        } else {
            
             return $this->errorsInterface->error_message('Error metodo de pago.',400,'description','Método de pago inválido' );
        }


        if ($metodo_envio){
            if ($metodo_envio instanceof MetodosEnvio) {
                // Trabaja con el objeto directamente
                $nombre_metodo_envio = $metodo_envio->getNombre();
            }
        }else{
             $metodo_envio=null;

             $nombre_metodo_envio= 'Sin Metodo de envio.';
        }

        $pedidos_regateo= $this->em->getRepository(Pedidos::class)->findBy(['regateo' => $regateo,'estado'=>'APPROVED']);

        if (!empty($pedidos_regateo)) {
              return $this->errorsInterface->error_message('Ya se ha creado un pedido para este regateo.',Response::HTTP_CONFLICT);
        }

        $data_url= $this->em->getRepository(GeneralesApp::class)->findOneBy(['nombre'=>'front','atributoGeneral'=>'Url']);

        $pedidosPendientes = $this->em->getRepository(Pedidos::class)
        ->findPedidosPendientesConPrefijo($user,$metodo_pago);
    
        if (!empty($pedidosPendientes)) {

            $nVentasPendientes = array_map(function($pedido) {
                return [
                    'n_venta' => $pedido->getNVenta(),
                    'tipo_pago' => $pedido->getMetodoPago() ? $pedido->getMetodoPago()->getId() : '',
                    'url_pago' => $pedido->getUrlPago() ?: null,
                    'fecha_pedido' => $pedido->getFechaPedido()->format('Y-m-d H:i:s'),
                    'metodo_pago' => $pedido->getMetodoPago() ? $pedido->getMetodoPago()->getNombre() : '',
                ];
            }, $pedidosPendientes);
        
            $errorMessage = 'Tienes compras en estado pendiente.';
            $uniqueNVenta = $nVentasPendientes[0];
            $url_retorno = ($uniqueNVenta['url_pago'] ?? null) 
                ?? $data_url->getValorGeneral() . '/checkout/resumen/' . ($uniqueNVenta['n_venta'] ?? '');
        
            $error = [];
        
            if ($uniqueNVenta['tipo_pago'] == 3) {
                $url = $this->ver_orden2($uniqueNVenta['n_venta']);
                $data_url = json_decode($url->getContent(), true);
                if ($data_url) {
                    $url_retorno = $data_url;
                }
                $error[] = [
                    'description' => 'Ya tienes una compra pendiente con el número de venta: ' . $uniqueNVenta['n_venta'] . ' con ' . $uniqueNVenta['metodo_pago'],
                    'action' => $url_retorno,
                ];
            } elseif ($uniqueNVenta['tipo_pago'] == 1) {
                $error[] = [
                    'description' => 'Ya tienes una compra pendiente con el número de venta: ' . $uniqueNVenta['n_venta'] . ' con ' . $uniqueNVenta['metodo_pago'],
                    'action' => $data_url->getValorGeneral() . '/checkout/deposito/' . $uniqueNVenta['n_venta'],
                ];
            } elseif ($uniqueNVenta['tipo_pago'] == 2) {
                // ... lógica de validación de tiempo ...
                $error[] = [
                    'description' => 'Ya tienes una compra pendiente con el número de venta: ' . ($uniqueNVenta['n_venta'] ?? 'N/A') . ' con ' . $uniqueNVenta['metodo_pago'],
                    'action' => $url_retorno,
                ];
            } else {
                $error[] = [
                    'description' => 'Ya tienes una compra pendiente con el número de venta: ' . $uniqueNVenta['n_venta'],
                    'action' => $data_url->getValorGeneral(). '/checkout/resumen/' . $uniqueNVenta['n_venta'],
                ];
            }
        
            return $this->errorsInterface->error_message($errorMessage, Response::HTTP_CONFLICT, null, $error);
        }

        $factura = $this->em->getRepository(Factura::class)->findOneBy(['login' => $user]);

        // Si no se ha proporcionado un $facturaID y no existe ninguna factura, crear una nueva
        if (($facturaID === null && $factura === null)) {
            $factura = new Factura();
            $factura->setEmail($user->getEmail()); // Variable original
            $factura->setLogin($user); // Variable original
            $factura->setNombre($user->getUsuarios() ? $user->getUsuarios()->getNombre() : ''); // Variable original
            $factura->setApellido($user->getUsuarios() ? $user->getUsuarios()->getApellido() : ''); // Variable original
            $factura->setTelefono($user->getUsuarios() ? $user->getUsuarios()->getCelular() : ''); // Variable original
            $factura->setDni($user->getUsuarios() ? $user->getUsuarios()->getDni() : ''); // Variable original
            $this->em->persist($factura); // Persistir la nueva factura
            $this->em->flush(); // Guardar cambios en la base de datos
        }

        $usuario = $this->em->getRepository(Usuarios::class)->findOneBy(['login' => $user]);

        if (!$usuario) {
            return $this->json(['message' => 'El usuario no se encontró en la base de datos.'])->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        $nombre = $usuario->getNombre();
        $apellido = $usuario->getApellido();
        $email = $usuario->getEmail();
        $documento = $usuario->getTipoDocumento();
        $telefono = $usuario->getCelular();
        $dni = $usuario->getDni();

        // Verificar cada campo individualmente

        $camposFaltantes = [];

        // Verificar cada campo individualmente y agregar el nombre del campo a $camposFaltantes si está vacío
        if (!$nombre) {
            $camposFaltantes[] = 'nombre';
        }
        if (!$email) {
            $camposFaltantes[] = 'email';
        }
        if (!$documento) {
            $camposFaltantes[] = 'documento';
        }
        if (!$telefono) {
            $camposFaltantes[] = 'teléfono';
        }
        if (!$dni) {
            $camposFaltantes[] = 'DNI';
        }

        $customer= $nombre.'-'.$apellido;

        // Verificar si hay campos faltantes
        if (!empty($camposFaltantes)) {
            $mensajeError = 'Faltan los siguientes datos para proceder con la compra.';
            $error[] = [
                'description' => 'Los siguientes campos están vacíos: ' . implode(', ', $camposFaltantes),
            ];
            
            return$this->errorsInterface->error_message(
                $mensajeError,
                417, // O Response::HTTP_EXPECTATION_FAILED si está usando constantes
                null,
                $error
            );
        }


        $direcciones = $this->em->getRepository(UsuariosDirecciones::class)->findOneBy(['id' => $direccion, 'usuario'=>$usuario]);

        $ciudad_usuario = null;
        $provincia_usuario = null;
        $region_usuario = null;
        $latitud_usuario = null;
        $longitud_usuario = null;
        $direccion_principal_usuario = ''; // Nombre corregido
        $direccion_secundaria_usuario = ''; // Nombre corregido
        $referencia_usuario = '';
        $id_servientrega = null;
        $codigo_postal_customer = '';

        if ($direcciones !== null) {
            $ciudad_usuario = $direcciones->getCiudad() ? $direcciones->getCiudad()->getCiudad() : null;
            $provincia_usuario = $direcciones->getCiudad() ? $direcciones->getCiudad()->getProvincia()->getProvincia() : null;
            $region_usuario = $direcciones->getCiudad() ? $direcciones->getCiudad()->getProvincia()->getRegion() : null;
            
            // Corregir conversión de tipos para latitud y longitud
            $latitud_usuario= $direcciones->getLatitud() ? $direcciones->getLatitud():null;
            $longitud_usuario= $direcciones->getLongitud() ? $direcciones->getLongitud():null;
            
            // Corregir typo en nombres de variables (direcion -> direccion)
            $direccion_principal_usuario = $direcciones->getDireccionP() ? $direcciones->getDireccionP() : '';
            $direccion_secundaria_usuario = $direcciones->getDireccionS() ? $direcciones->getDireccionS() : ''; 
            $referencia_usuario = $direcciones->getReferenciaDireccion() ? $direcciones->getReferenciaDireccion() : ''; 
            $id_servientrega = $direcciones->getCiudad()->getIdServientrega();
            $codigo_postal_customer = $direcciones->getCodigoPostal() ? $direcciones->getCodigoPostal() : '';
        }

         try{

            $carrito= $this->carritoService->retiros_aprovados($user,$regateo,$metodo_pago,$direccion,$metodo_envio);
              
            if ($carrito->getStatusCode() !== Response::HTTP_OK) {
                return $carrito; // Retorna directamente la respuesta de error
            }
    
            $data = json_decode($carrito->getContent(),true);
            $api_response =null;
            $request_id=null;
            $c_descuento= 0;
            $url=null;
            $subtotal_origina= $data['subtotal_original'];
            $subtotal= $data['subtotal'];
            $subtotal_mas_iva= $data['subtotal_mas_iva'];
            $iva= $data['iva'];
            $iva_aplicado= $data['iva_aplicado'];
            $subtotal_envio= $data['subtotal_envio'];
            $iva_envio= $data['iva_envio'];
            $costo_envio= $data['costo_envio'];
            $calculo_paypal= $data['calculo_paypal'];
            $total= $data['total'];
            $detalle= $data['detalle'];
         }catch(Exception $e){
            return $this->errorsInterface->error_message('Error al obtener los datos del carito.',Response::HTTP_INTERNAL_SERVER_ERROR,'description',$e->getMessage());
        }


        $tienda = $this->em->getReference(Tiendas::class,$detalle['tienda']);

        $tipo_envio = $detalle['tipo_entrega'];
        $tipoEnvioEnCarrito = is_array($tipo_envio) ? $tipo_envio : [$tipo_envio];
        
        // Verificar si se necesita método de envío
        if ($metodo_envio == null && (in_array('A DOMICILIO', $tipoEnvioEnCarrito) || in_array('AMBOS', $tipoEnvioEnCarrito))) {
            return $this->errorsInterface->error_message('Seleccione un método de envío.', 400);
        }
        
        // Verificar si se necesita dirección
        if ($direcciones == null && (in_array('A DOMICILIO', $tipoEnvioEnCarrito) || in_array('AMBOS', $tipoEnvioEnCarrito))) {
            return $this->errorsInterface->error_message('Seleccione una dirección de entrega.', 400);
        }
        
        // Validar direcciones si existen
        if ($direcciones && (in_array('A DOMICILIO', $tipoEnvioEnCarrito) || in_array('AMBOS', $tipoEnvioEnCarrito))) {
            // Corregido nombre de variables (direcion -> direccion)
            if ($direccion_principal_usuario == null || $direccion_principal_usuario == '') {
                return $this->errorsInterface->error_message('Error en la dirección.', 400, 'description', 'La dirección principal no puede estar vacía.');   
            }
            
            if ($direccion_secundaria_usuario == null || $direccion_secundaria_usuario == '') {
                return $this->errorsInterface->error_message('Error en la dirección.', 400, 'description', 'La dirección secundaria no puede estar vacía.');   
            }
        }


        $p=null;
        $n_venta = 'V-' . rand(0000, 9999);
        $return_url = $data_url->getValorGeneral() . "/checkout/resumen/" . $n_venta;

        if($idMetodo == 2){

            try{
                $response= $this->placetoPayService->processPayment($nombre, $apellido, $email,$dni,$documento,$telefono,$n_venta,$total,$subtotal,$costo_envio,$iva,$return_url);

                $api_response= json_decode($response);
    
                $request_id= $api_response->requestId;
    
                $url= $api_response->processUrl;

            }catch(Exception $e){
                return $this->errorsInterface->error_message('Error al procesar la transacción PlaceToPay.',Response::HTTP_INTERNAL_SERVER_ERROR,'description',$e->getMessage());
            }

        
        }elseif($idMetodo == 3){

            try{
                $response= $this->paypalService->createOrder($n_venta, $total, $subtotal, $iva, $costo_envio);
            
                $api_response= json_decode($response);
                $request_id= $api_response->id;
                $url= $api_response->links[1]->href;
            }catch(Exception $e){
                return $this->errorsInterface->error_message('Error al procesar la transacción PayPal.',Response::HTTP_INTERNAL_SERVER_ERROR,'description',$e->getMessage());
            }
    
        }

        $numero_pedido=NULL;

        

        try{           
            $p = $this->guardar_pedido->guardarPedido($user, $n_venta, $factura, $numero_pedido, $tienda, $metodo_envio, $ingresado, $metodo_pago, null, $direccion_principal_usuario, $direccion_secundaria_usuario, $referencia_usuario, $customer, $dni, $telefono, $codigo_postal_customer, $ciudad_usuario, $id_servientrega, $provincia_usuario, $region_usuario, $latitud_usuario, $longitud_usuario, $request_id, $iva_aplicado, $subtotal, $iva, $subtotal_mas_iva, $subtotal_envio, $iva_envio, $costo_envio, $calculo_paypal, $total, 0, $subtotal_origina);
        }catch(Exception $e){
            return $this->errorsInterface->error_message('Error al crear el pedido.',Response::HTTP_INTERNAL_SERVER_ERROR,'description',$e->getMessage());
        }
        try{

            switch (true) {
                case in_array('A DOMICILIO', $tipoEnvioEnCarrito) && in_array('RETIRO EN TIENDA FISICA', $tipoEnvioEnCarrito):
                    $tipoEnvioPedido = 'AMBOS';
                    break;
            
                case in_array('A DOMICILIO', $tipoEnvioEnCarrito):
                    $tipoEnvioPedido = 'A DOMICILIO';
                    break;
            
                case in_array('RETIRO EN TIENDA FISICA', $tipoEnvioEnCarrito):
                    $tipoEnvioPedido = 'RETIRO EN TIENDA FISICA';
                    break;
            
                default:
                    $tipoEnvioPedido = 'SIN TIPO DE ENVIO';
                    break;
            }

            if( $tipoEnvioPedido == 'RETIRO EN TIENDA FISICA'){
                $p->setMetodoEnvio(null);
            }

            $p->setTipoEnvio($tipoEnvioPedido);

            $p->setUrlPago($url);

            $p->setRegateo($regateo);

            $regateo->setEstado('USED');

            $p_detalle= $this->em->getReference(Productos::class,$detalle['id_producto']);
           
            $v_variacion= $this->em->getRepository(Variaciones::class)->find($detalle['id_variacion']);
          
            $this->guardar_pedido->guardarDetallePedido( $p,$p_detalle,$v_variacion,$detalle['nombre_producto'], $detalle['cantidad'],$tienda, $detalle['subtotal'], $detalle['iva'], $detalle['total'],$detalle['ciudad'],$detalle['direccion'],$detalle['id_direccion'],$detalle['provincia'],$detalle['region'],$detalle['peso'],$detalle['latitud'],$detalle['longitud'],$detalle['celular_producto'],$detalle['referencia']? $detalle['referencia']:null,$detalle['usario_producto'],$detalle['total_unitario'],$detalle['subtotal_unitario'],$detalle['iva_unitario'],null,null,0,null);

        }catch(Exception $e){
            return $this->errorsInterface->error_message('Error al crear el detalle del pedido.',Response::HTTP_INTERNAL_SERVER_ERROR,'description',$e->getMessage());
        }



        if(!$url){
            $url= $data_url->getValorGeneral().'/checkout/deposito/'.$n_venta;
        }

        $data=[
            'url'=>$url
        ];
        return $this->errorsInterface->succes_message('Venta por regateo generada con éxito.', null, $data);
    }

    
   private function ver_orden2($pedido)
   {
      $pedidos = $this->em->getRepository(Pedidos::class)->findBy(['n_venta' => $pedido]);
      
      if (!$pedidos) {
        return $this->errorsInterface->error_message('Venta no encontrada.', Response::HTTP_NOT_FOUND);
      }
   
      foreach ($pedidos as $pedido) {
   
      $id = $pedido->getReferenciaPedido();
      $url = $this->getParameter('paypal_url')."/v2/checkout/orders/".$id;
   
      $auth = $this->paypalService->getToken();
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
              "Content-Type: application/json",
              "Authorization: Bearer " . $auth
      ]);
   
      $result = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      
      $rest= $result;
   
      $json = json_decode($rest);
   
      if (curl_errno($ch)) {
         return $this->errorsInterface->error_message('Error Paypal: ' . curl_error($ch), $httpCode);
      }
      if ($httpCode !== 200){

       return $this->errorsInterface->error_message('Error desconocido', $httpCode);
      }
   
   
     }
   
      return  $this->json($json->links[1]->href);
   
   }

}

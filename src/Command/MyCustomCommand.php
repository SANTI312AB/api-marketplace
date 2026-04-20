<?php

namespace App\Command;

use App\Entity\Carrito;
use App\Entity\Cupon;
use App\Entity\Estados;
use App\Entity\FuncionesEspeciales;
use App\Entity\GeneralesApp;
use App\Entity\Pedidos;
use App\Service\DynamicMailerFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Entity\CommandLog;
use App\Service\EmailPedidoService;
use App\Service\QrCodeGenerator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
// ✅ AÑADE ESTA LÍNEA
use Symfony\Component\Console\Attribute\AsCommand;


#[AsCommand(
    name: 'app:actualizar-pedidos',
    description: 'Actualiza el estado de los pedidos pendientes desde la API de PlaceToPay'
)]
class MyCustomCommand extends Command
{

    private $container;
    private $parameters;
    private $mailer;

    private $entityManager;

    private $emailPedidoService;

    private $qrCodeGenerator;
    private $request;

    private $router;


    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container, ParameterBagInterface $parameters, DynamicMailerFactory $mailer, EmailPedidoService $emailPedidoService, QrCodeGenerator $qrCodeGenerator, RequestStack $request, UrlGeneratorInterface $router)
    {
        $this->container = $container;
        $this->parameters = $parameters;
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
        $this->emailPedidoService = $emailPedidoService;
        $this->qrCodeGenerator = $qrCodeGenerator;
        $this->request = $request->getCurrentRequest();  // Injecting RequestStack into the controller.
        $this->router = $router;  // Injecting UrlGeneratorInterface into the controller.
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Actualiza el estado de los pedidos pendientes desde la API de PlaceToPay');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $domain = 'https://rest.shopby.com.ec/';
        $host = $this->router->getContext()->getBaseUrl();
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $pedidos = $entityManager->getRepository(Pedidos::class)->findBy(['estado' => 'PENDING', 'metodo_pago' => 2]);

        if (!$pedidos) {
            $io = new SymfonyStyle($input, $output);
            $this->logCommandOutput('No hay pedidos(place to pay) pendientes  para actualizar', Command::FAILURE);
            $io->warning('No hay pedidos(place to pay) pendientes  para actualizar');
            return Command::FAILURE;
        }
        $ingresado = $entityManager->getRepository(Estados::class)->findOneBy(['id' => 19]);
        $cancelado = $entityManager->getRepository(Estados::class)->findOneBy(['id' => 24]);

        $estadoPago = null;

        $cartsToDelete = [];

        $cuponToUpdate = [];

        $cliente = [];
        $total_Subtotal = 0;
        $total_iva = 0;
        $total_costo_envio = 0;
        $total_final = 0;
        $data = [];

        foreach ($pedidos as $pedido) {

            $id = $pedido->getReferenciaPedido();
            $user = $pedido->getLogin();
            $user = $pedido->getLogin();
            $id_cupon = $pedido->getCupon() ? $pedido->getCupon()->getId() : null;
            $carrito = $entityManager->getRepository(Carrito::class)->findOneBy(['login' => $user]);
            if ($id_cupon) {
                $cupon = $entityManager->getRepository(Cupon::class)->findOneBy(['id' => $id_cupon]);
            } else {
                $cupon = null;
            }

            $cliente[] = $pedido;

            $rest = '';
            if (!empty($id)) {
                $generales = $entityManager->getRepository(GeneralesApp::class);
                $auth = $generales->getLoginPTP();
                $referencia = $id;

                $apiUrl = $generales->data_url() . "/session/" . $referencia;
                $ch = curl_init($apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array_merge($auth)));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $rest = $response;
                if (curl_errno($ch)) {

                    $errorMsg = curl_error($ch);

                    $io = new SymfonyStyle($input, $output);
                    $this->logCommandOutput('No se puede conectar con la api' . $errorMsg, Command::FAILURE);
                    $io->error('No se puede conectar con la api' . $errorMsg);
                    return Command::FAILURE;

                }

                if ($httpCode != 200) {

                    $io = new SymfonyStyle($input, $output);
                    $this->logCommandOutput('Error: ' . json_encode(json_decode($response), JSON_PRETTY_PRINT), Command::FAILURE);
                    $io->error('Error en: ' . $pedido->getNumeroPedido() . json_encode(json_decode($response), JSON_PRETTY_PRINT));
                    return Command::FAILURE;
                } else {
                    $responseData = json_decode($response, true);

                    if (isset($responseData['payment']) && is_array($responseData['payment']) && count($responseData['payment']) > 0) {
                        $authorization = $responseData['payment'][0]['authorization'];
                        $internalReference = $responseData['payment'][0]['internalReference'];
                        $fecha_pago = $responseData['payment'][0]['status']['date'] ?? null;
                        $pedido->setAutorizacion($authorization);
                        $pedido->setReferenciaInterna($internalReference);
                        $pedido->setFechaPago(new \DateTime($fecha_pago));
                    }

                    if (isset($responseData['payment'][0]['status']['status'])) {
                        $estadoPago = $responseData['payment'][0]['status']['status'];
                    } elseif (isset($responseData['status']['status'])) {
                        $estadoPago = $responseData['status']['status'];
                    } else {
                        $estadoPago = null; // or some default value
                    }


                    $pedido->setEstado($estadoPago);

                    if ($estadoPago === 'APPROVED') {
                        if ($carrito !== null) {
                            $entityManager->remove($carrito);

                        }
                    }


                    switch ($estadoPago) {
                        case 'APPROVED':
                            $pedido->setEstadoEnvio($ingresado);
                            if ($carrito !== null) {

                                $cartsToDelete[] = $carrito;

                            }

                            if ($cupon !== null) {

                                $cuponToUpdate[] = $cupon;

                            }
                            break;
                        case 'REJECTED':
                            $pedido->setEstadoEnvio($cancelado);
                            $pedido->setEstadoRetiro($cancelado);
                            $pedido->setFechaRechazo(new \DateTime());
                            break;
                    }
                    //consulta correcta, se actualiza el estado del pedido, no siempre quiere decir que el pago se proceso con exito
                    // Los estados de pagos solo pueden ser : APROBADO, RECHAZADO, PENDIENTE, CANCELADO, DEVUELTO
                }

                $entityManager->flush();

                $data = $this->emailPedidoService->sendemail_pedido($pedido, $estadoPago);
                $total_Subtotal += $pedido->getSubtotal();
                $total_iva += $pedido->getIva();
                $total_costo_envio += $pedido->getCostoEnvio();
                $total_final = $total_Subtotal + $total_iva + $total_costo_envio;
            }
        }


        foreach ($cuponToUpdate as $cupon) {
            $uso_cupon = $cupon->getUsoCupon() ?? 0;
            $cupon->setUsoCupon($uso_cupon + 1);
            $entityManager->flush();
        }

        foreach ($cartsToDelete as $cartToDelete) {
            $entityManager->remove($cartToDelete);
            $entityManager->flush();
        }


        foreach ($cliente as $c) {

            $email_cliente = $c->getLogin()->getEmail();
            $nombre_cliente = $c->getLogin()->getUsuarios()->getNombre() . ' ' . $pedido->getLogin()->getUsuarios()->getApellido();
            $n_venta = $c->getNVenta();
            $estado = $c->getEstado();
            $metodo_pago = $c->getMetodoPago()->getNombre();
            $direccion_cliente = $c->getDireccionPrincipal() . 'y' . $c->getDireccionSecundaria() . ' ' . $c->getCustomerCity();
            $estado_pago = $c->getEstado();
            $n_pedido = $c->getNumeroPedido();
        }

        $data_url = $this->entityManager->getRepository(GeneralesApp::class)->findOneBy(['nombre' => 'front', 'atributoGeneral' => 'Url']);
        $url = $data_url->getValorGeneral() . "/cuenta/compras/{$n_venta}";

        $qrFilename = $this->qrCodeGenerator->generateAndSave(
            $url,
            "venta_{$n_venta}.png"
        );


        switch ($estado) {
            case 'APPROVED':
                $admin_email_pedidos = $entityManager->getRepository(FuncionesEspeciales::class)->findOneBy(['id' => 2]);
                $email_facturacion = $entityManager->getRepository(FuncionesEspeciales::class)->findOneBy(['id' => 5]);

                $emu = null;
                if ($email_facturacion->isActivo() && $admin_email_pedidos->isActivo()) {
                    $emu = [
                        $admin_email_pedidos->getDescripcion(),
                        $email_facturacion->getDescripcion()
                    ];
                } elseif ($email_facturacion->isActivo()) {
                    $emu = [$email_facturacion->getDescripcion()];
                } elseif ($admin_email_pedidos->isActivo()) {
                    $emu = [$admin_email_pedidos->getDescripcion()];
                }

                if ($emu !== null) {
                    try {
                        $eml = (new TemplatedEmail())
                            ->to(...$emu)
                            ->subject('La venta ' . $n_venta . ' ha sido ')
                            ->htmlTemplate('pedidos/estado_pedido_cliente.html.twig')
                            ->context([
                                'qr' => $domain . $host . '/public/qr_codes/' . $qrFilename,
                                'n_venta' => $n_venta,
                                'nombre' => $nombre_cliente,
                                'metodo_pago' => $metodo_pago,
                                'detalle' => $data,
                                'subtotal' => $total_Subtotal,
                                'impuestos' => $total_iva,
                                'costo_envio' => $total_costo_envio,
                                'direccion_cliente' => $direccion_cliente,
                                'total' => $total_final,
                                'estado_pago' => $estado_pago
                            ]);

                        $this->mailer->send($eml);
                    } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
                        // Log the error or handle it as needed
                        error_log('Error enviando el correo: ' . $e->getMessage());
                    }
                }
                break;
        }




        $eml = (new TemplatedEmail())
            ->to($email_cliente)
            ->subject('El estado de la venta' . ' ' . $n_venta . ' ha sido ')
            ->htmlTemplate('pedidos/estado_pedido_cliente.html.twig') // Especifica la plantilla Twig para el cuerpo HTML
            ->context([
                'qr' => $domain . $host . '/public/qr_codes/' . $qrFilename,
                'n_venta' => $n_venta,
                'nombre' => $nombre_cliente,
                'metodo_pago' => $metodo_pago,
                'detalle' => $data,
                'subtotal' => $total_Subtotal,
                'impuestos' => $total_iva,
                'costo_envio' => $total_costo_envio,
                'direccion_cliente' => $direccion_cliente,
                'total' => $total_final,
                'estado_pago' => $estado_pago
            ]);

        $this->mailer->send($eml);


        // Lógica de tu función aquí
        $io = new SymfonyStyle($input, $output);
        $this->logCommandOutput('Pedidos pendientes actualziados con place to pay', Command::SUCCESS);
        $io->success('Pedidos pendientes actualziados con place to pay');
        return Command::SUCCESS;
    }


    private function logCommandOutput(string $errorMessage, int $exitCode): void
    {
        $logEntry = new CommandLog();
        $logEntry->setCommandName($this->getName());
        $logEntry->setArguments(json_encode([])); // Puedes ajustar esto según los argumentos reales
        $logEntry->setErrorMessage($errorMessage);
        $logEntry->setExitCode($exitCode);
        $logEntry->setStartTime(new \DateTime());
        $logEntry->setEndTime(new \DateTime());

        $this->entityManager->persist($logEntry);
        $this->entityManager->flush();
    }

}
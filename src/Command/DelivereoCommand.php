<?php

namespace App\Command;

use App\Entity\Estados;
use App\Entity\Pedidos;
use App\Entity\Servientrega;
use App\Service\DelivereoService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Repository\DetallePedidoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Entity\CommandLog;
// ✅ AÑADE ESTA LÍNEA
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:actualizar-pedidos-delivereo',
    description: 'Actualiza el estado de envio de  los pedidos que se realizaron con delivereo'
)]
class DelivereoCommand extends Command
{
   
    private $container;
    private $parameters;

    private $detallePedidoRepository;
    private $entityManager;

    private $delivereoService;


    public function __construct(ContainerInterface $container,ParameterBagInterface $parameters,DetallePedidoRepository $detallePedidoRepository, EntityManagerInterface $entityManager,
     DelivereoService $delivereoService
    )
    {
        $this->container = $container;
        $this->parameters = $parameters;
        $this->detallePedidoRepository = $detallePedidoRepository;
        $this->entityManager = $entityManager;
        $this->delivereoService= $delivereoService;
        parent::__construct();
    }
     


    protected function configure(): void
    {
        $this->setDescription('Actualiza el estado de envio de  los pedidos que se realizaron con delivereo');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $pedidos = $entityManager->getRepository(Pedidos::class)->findBy(['estado' => 'APPROVED', 'metodo_envio' => 3]);
        if (!$pedidos) {
            $io = new SymfonyStyle($input, $output);
            $this->logCommandOutput('No hay pedidos delivereo para actualizar', Command::FAILURE);
            $io->warning('No hay pedidos delivereo para actualizar');
            return Command::FAILURE;
        }
        $entregado = $entityManager->getRepository(Estados::class)->findOneBy(['id' => 22]);
        $EN_CAMINO = $entityManager->getRepository(Estados::class)->findOneBy(['id' => 21]);
        $cancelado = $entityManager->getRepository(Estados::class)->findOneBy(['id' => 24]);


        foreach ($pedidos as $pedido) {

            if ($pedido->getEstadoEnvio()->getId() !== 22) {
                $guias = $entityManager->getRepository(Servientrega::class)->findOneBy(['n_pedido' => $pedido->getNumeroPedido(), 'anulado' => false]);


                $data = [
                    "bookingId" => $guias->getCodigoServientrega(),
                    "lang" => "en"
                ];

                $url = $this->delivereoService->url_delivereo() . '/api/private/business-bookings/detail-full';

                $curl = curl_init();

                curl_setopt_array($curl, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => json_encode(array_merge($data)),
                    CURLOPT_HTTPHEADER => [
                        "Accept: application/json",
                        "Authorization: Bearer " . $this->delivereoService->getJwtToken(),
                        "Content-Type: application/json"
                    ]
                ]);

                $response = curl_exec($curl);
                $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                curl_close($curl);

                if ($httpcode !== 200) {
                    $io = new SymfonyStyle($input, $output);
                    $this->logCommandOutput('Error al conectar api delivereo', Command::FAILURE);
                    $io->error('Error al conectar api delivereo');
                    return Command::FAILURE;
                }

                if ($httpcode == 200) {


                    $mp = json_decode($response);

                    if ($mp->bookingStatus === 'FINISHED') {
                        $fecha_entrega = null;
                        try {
                            $fecha_entrega = new \DateTime($mp->lastPointArrivalTime);
                        } catch (\Exception $e) {
                            $fecha_entrega = new \DateTime(); // Fallback en caso de error
                        }
                        $pedido->setEstadoEnvio($entregado);
                        $pedido->setFechaEntregaAdomicilio($fecha_entrega);
                        $entityManager->flush();
                        /*$user = $pedido->getTienda()->getLogin(); 
                        $this->gestionarTransacciones->calcularTransacciones($user);*/
                    } elseif ($mp->bookingStatus === 'ARRIVED_FIRST_POSITION') {
                        $fecha_en_camino = null;
                        try {
                            $fecha_en_camino = new \DateTime($mp->firstPointInitialTime);
                        } catch (\Exception $e) {
                            $fecha_en_camino = new \DateTime(); // Fallback
                        }
                        $pedido->setEstadoEnvio($EN_CAMINO);
                        $pedido->setFechaEnCamino($fecha_en_camino);
                        $entityManager->flush();
                    }

                }

            }
        }

        $io = new SymfonyStyle($input, $output);
        $this->logCommandOutput('Pedidos delivereo actualizados.', Command::SUCCESS);
        $io->success('La tarea se ejecutó con éxito.');

        return Command::SUCCESS;
    }


    private function logCommandOutput(string $errorMessage, int $exitCode): void
    {
     $logEntry = new CommandLog();
     $logEntry->setCommandName($this->getName());
     $logEntry->setArguments(arguments: json_encode([])); // Puedes ajustar esto según los argumentos reales
     $logEntry->setErrorMessage($errorMessage);
     $logEntry->setExitCode($exitCode);
     $logEntry->setStartTime(new \DateTime());
     $logEntry->setEndTime(new \DateTime());
 
     $this->entityManager->persist($logEntry);
     $this->entityManager->flush();
    }
 
}
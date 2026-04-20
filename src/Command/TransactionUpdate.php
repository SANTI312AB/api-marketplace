<?php

namespace App\Command;

use App\Service\GestionarTransacciones;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Entity\CommandLog;
use App\Entity\Tiendas;
use App\Repository\DetallePedidoRepository;
// ✅ AÑADE ESTA LÍNEA
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:update-transactions',
    description: 'Actualizar ganancias de todas las tiendas'
)]
class TransactionUpdate extends Command
{
    private $container;

    private $detallePedidoRepository;
     
    private  $entityManager;

    private $gestionarTransacciones;
   

    public function __construct(DetallePedidoRepository $detallePedidoRepository,EntityManagerInterface $entityManager,ContainerInterface $container,GestionarTransacciones $gestionarTransacciones)
    {
        $this->container = $container;
        $this->entityManager = $entityManager;
        $this->detallePedidoRepository = $detallePedidoRepository;
        $this->gestionarTransacciones= $gestionarTransacciones;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Actualizar ganancias de todas las tiendas');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $tiendas= $em->getRepository(Tiendas::class)->findAll();

        foreach ($tiendas as $tienda){
            $user = $tienda->getLogin();
            $this->gestionarTransacciones->calcularTransacciones($user);
        }

        $io = new SymfonyStyle($input, $output);
        $this->logCommandOutput('Transacciones de todas las tiendas actualizadas', Command::SUCCESS);
        $io->success('Transacciones de todas las tiendas actualizadas');
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

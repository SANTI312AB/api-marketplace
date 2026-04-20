<?php

namespace App\Command;

use App\Entity\CommandLog;
use App\Entity\Pedidos;
use App\Service\FacturadorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:añadir-pedidos-facturador',
    description: 'Añade pedidos al facturador'
)]
class AñadirFacturadorCommand extends Command
{
    private $em;
    private $facturadorService;

    public function __construct(EntityManagerInterface $em, FacturadorService $facturadorService)
    {
        $this->em = $em;
        $this->facturadorService = $facturadorService;
        parent::__construct();
    }

    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //añadir pedidos por fecha hacia adelante.
        $io = new SymfonyStyle($input, $output);
        $pedidos = $this->em->getRepository(Pedidos::class)->pedidos_por_facturar();
        if(!$pedidos){
            $this->logCommandOutput('No hay pedidos para añadir al facturador.', Command::FAILURE);
            $io->error('No hay pedidos para añadir al facturador.');
            return Command::FAILURE;
        }

        foreach ($pedidos as $pedido) {
            sleep(1); 
            try {
                $this->facturadorService->añadir_facturador_command($pedido);
                $this->logCommandOutput('Añadido al facturador: ' . $pedido->getNumeroPedido(), Command::SUCCESS);
                $io->success('Añadido al facturador: ' . $pedido->getNumeroPedido());
            } catch (\Exception $e) {
                $errorMsg = 'Error en Pedido:' . $pedido->getNumeroPedido() . ' - ' . $e->getMessage();
                $this->logCommandOutput($errorMsg, Command::FAILURE);
                $io->error($errorMsg);
                continue;
            }
        }
        
        return Command::SUCCESS; 
    }

    private function logCommandOutput(string $errorMessage, int $exitCode): void
    {
        $logEntry = new CommandLog();
        $logEntry->setCommandName($this->getName());
        $logEntry->setArguments(json_encode([]));
        $logEntry->setErrorMessage($errorMessage);
        $logEntry->setExitCode($exitCode);
        $logEntry->setStartTime(new \DateTime());
        $logEntry->setEndTime(new \DateTime());

        $this->em->persist($logEntry);
        $this->em->flush();
    }
}

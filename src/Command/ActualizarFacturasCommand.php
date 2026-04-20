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
    name: 'app:actualizar-facturas', // <-- Pon aquí el nombre que tenía tu comando antes
    description: 'Actualiza el estado de las facturas de pedidos aprobados'
)]
class ActualizarFacturasCommand extends Command
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
        $io = new SymfonyStyle($input, $output);
        $pedidos = $this->em->getRepository(Pedidos::class)->findBy(['estado' => 'APPROVED']);

        if (!$pedidos) {
            $this->logCommandOutput('No hay pedidos para actualizar.', Command::FAILURE);
            $io->error('No hay pedidos para actualizar.');
            return Command::FAILURE;
        }

        foreach ($pedidos as $pedido) {
            if (
                ($pedido->getClaveFacturador() !== null && !empty($pedido->getClaveFacturador())) &&
                $pedido->getEstadoFacturador() !== 'Autorizada' &&
                $pedido->getEstadoFacturador() !== 'No Autorizada' &&
                $pedido->getEstadoFacturador() !== 'Error de envío SRI'
            ) {
                sleep(1);
                try {
                    $clave = $pedido->getClaveFacturador();
                    $response = $this->facturadorService->verificar_factura($clave);

                    if (isset($response['data']['estadoFactura'])) {
                        $pedido->setEstadoFacturador($response['data']['estadoFactura']);
                        $this->logCommandOutput('Factura actualizada en Pedido: ' . $pedido->getNumeroPedido(), Command::SUCCESS);
                        $io->success('Factura actualizada en Pedido: ' . $pedido->getNumeroPedido());
                    } else {
                        $pedido->setEstadoFacturador('No hay datos');
                        $this->logCommandOutput('No hay datos para Pedido: ' . $pedido->getNumeroPedido(), Command::FAILURE);
                        $io->warning('No hay datos para Pedido: ' . $pedido->getNumeroPedido());
                    }

                    $this->em->persist($pedido);
                    $this->em->flush();

                } catch (\Exception $e) {
                    $errorMsg = 'Error en Pedido:' . $pedido->getNumeroPedido() . ' - ' . $e->getMessage();
                    $this->logCommandOutput($errorMsg, Command::FAILURE);
                    $io->error($errorMsg);
                    continue;
                }
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

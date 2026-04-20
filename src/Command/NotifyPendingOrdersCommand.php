<?php

namespace App\Command;

use App\Entity\Pedidos;
use App\Service\DynamicMailerFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use App\Entity\CommandLog;
// ✅ AÑADE ESTA LÍNEA
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:notify-orders',
    description: 'Notifica a los usuarios que no han marcado como entregado un pedido'
)]
class NotifyPendingOrdersCommand extends Command
{

    private $entityManager;
    private $mailer;
    private $parameters;

    public function __construct(EntityManagerInterface $entityManager, DynamicMailerFactory $mailer, ParameterBagInterface $parameters)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->parameters = $parameters;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Notifica a los usuarios que no han marcado como entregado un pedido')
            ->addOption('hours', null, InputOption::VALUE_OPTIONAL, 'Número de horas desde la fecha del pedido',48);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $hours = $input->getOption('hours');

        $now = new \DateTime();
        $interval = new \DateInterval('PT' . $hours . 'H');
        $thresholdDate = $now->sub($interval);

        $pedidosRepository = $this->entityManager->getRepository(Pedidos::class);
        $queryBuilder = $pedidosRepository->createQueryBuilder('p');
        
        $queryBuilder
            ->where('p.estado = :estadoPago')
            ->andWhere('p.fecha_pedido < :thresholdDate')
            ->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->andX(
                        'p.tipo_envio = :domicilio',
                        'p.estado_envio = 20'
                    ),
                    $queryBuilder->expr()->andX(
                        'p.tipo_envio = :retiro',
                        'p.estado_retiro = 26'
                    ),
                    $queryBuilder->expr()->andX(
                        'p.tipo_envio = :ambos',
                        'p.estado_envio = 20',
                        'p.estado_retiro = 26'
                    )
                )
            )
            ->setParameters([
                'estadoPago' => 'APPROVED',
                'thresholdDate' => $thresholdDate,
                'domicilio' => 'A DOMICILIO',
                'retiro' => 'RETIRO EN TIENDA FISICA',
                'ambos' => 'AMBOS',
            ]);

        $pedidos = $queryBuilder->getQuery()->getResult();

        if(!$pedidos){

            $io->writeln('No hay pedidos pendientes por notificar.');
            $this->logCommandOutput('No hay pedidos pendientes por notificar.', Command::FAILURE);
            return Command::SUCCESS;
        }


        foreach ($pedidos as $pedido) {
            
            $io->writeln(sprintf(
                'Pedido ID: %d, Número Pedido: %s, Fecha Pedido: %s, Email: %s',
                $pedido->getId(),
                $pedido->getNumeroPedido(),
                $pedido->getFechaPedido()->format('Y-m-d H:i:s'),
                $pedido->getLogin()->getEmail()
            ));

            $email = (new TemplatedEmail())
                ->to($pedido->getLogin()->getEmail())
                ->subject('Por favor marque como entregado el pedido despues de recibirlo')
                ->htmlTemplate('pedidos/notificacion_pedido.html.twig') 
                ->context([
                'n_pedido' => $pedido->getNumeroPedido(),
                'nombre'=>$pedido->getLogin()->getUsuarios()->getNombre().' '.$pedido->getLogin()->getUsuarios()->getApellido()
                ]); 

            $this->mailer->send($email);
        }

        $io->success('Notificaciones enviadas con éxito.');
        $this->logCommandOutput('Notificaciones enviadas con éxito.', Command::SUCCESS);

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
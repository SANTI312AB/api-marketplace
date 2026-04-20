<?php
// src/Command/DeleteUsersCommand.php

namespace App\Command;

use App\Entity\Login;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput; // Asegúrate de importar BufferedOutput
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Entity\CommandLog;
// ✅ AÑADE ESTA LÍNEA
use Symfony\Component\Console\Attribute\AsCommand;


#[AsCommand(
    name: 'app:eliminar_usuarios',
    description: 'Elimina los usuarios no verificados'
)]
class DeleteUsersCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Elimina los usuarios no verificados')
            ->addOption('hours', null, InputOption::VALUE_OPTIONAL, 'Número de horas desde la fecha de registro', 1);
    }

   

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Utiliza BufferedOutput para capturar la salida del comando
        $bufferedOutput = new BufferedOutput();
        $io = new SymfonyStyle($input, $bufferedOutput);
        
        // Información relevante para el log
        $hours = $input->getOption('hours');
        $now = new \DateTime();
        $interval = new \DateInterval('PT' . $hours . 'H');
        $thresholdDate = $now->sub($interval);
    
        $this->logger->info('Iniciando el proceso de eliminación de usuarios no verificados.', [
            'hours' => $hours,
            'thresholdDate' => $thresholdDate->format('Y-m-d H:i:s'),
        ]);
    
        $loginRepository = $this->entityManager->getRepository(Login::class);
        $queryBuilder = $loginRepository->createQueryBuilder('l');
    
        $queryBuilder
            ->where('l.vericacion = :estado')
            ->andWhere('l.fecha_registro < :thresholdDate')
            ->setParameters([
                'estado' => 8,
                'thresholdDate' => $thresholdDate,
            ]);
    
        $logins = $queryBuilder->getQuery()->getResult();
    
        if (count($logins) === 0) {
            $io->error('No se encontraron usuarios no verificados para eliminar.');
    
            // Mostrar el mensaje en la terminal
            $output->write($bufferedOutput->fetch());
    
            // Guardar el mensaje de error en la base de datos directamente
            $this->logCommandOutput('No se encontraron usuarios no verificados para eliminar.', Command::FAILURE);
            return Command::FAILURE;
        }
    
        foreach ($logins as $login) {
            $this->logger->info('Eliminando usuario no verificado.', [
                'usuario_id' => $login->getId(),
            ]);
    
            $this->entityManager->remove($login);
        }
    
        $this->entityManager->flush();
        $io->success('Usuarios no verificados eliminados con éxito.');
    
        // Mostrar el mensaje de éxito en la terminal
        $output->write($bufferedOutput->fetch());
    
        // Guardar el mensaje de éxito en la base de datos directamente
        $this->logCommandOutput('Usuarios no verificados eliminados con éxito.', Command::SUCCESS);
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

<?php
// src/EventListener/ConsoleCommandListener.php

namespace App\EventListener;

use App\Entity\CommandLog;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\ConsoleEvents;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleCommandListener implements EventSubscriberInterface
{
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private ?CommandLog $logEntry = null;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'onConsoleCommand',
            ConsoleEvents::ERROR => 'onConsoleError',
            ConsoleEvents::TERMINATE => 'onConsoleTerminate',
        ];
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $commandName = $event->getCommand()->getName();
        $input = $event->getInput();
        $arguments = json_encode($input->getArguments());
        $options = json_encode($input->getOptions());

        // Crear una nueva entrada de log para el comando ejecutado
        $this->logEntry = new CommandLog();
        $this->logEntry->setCommandName($commandName);
        $this->logEntry->setArguments($arguments);
        $this->logEntry->setOptions($options);
        $this->logEntry->setStartTime(new \DateTime());

        $this->entityManager->persist($this->logEntry);
        $this->entityManager->flush();

        $this->logger->info('Iniciando comando: ' . $commandName);
    }

    public function onConsoleError(ConsoleErrorEvent $event): void
    {
        if ($this->logEntry) {
            // Capturar el mensaje de error
            $errorMessage = $event->getError()->getMessage();
            $this->logEntry->setErrorMessage($errorMessage);
            $this->logEntry->setEndTime(new \DateTime());
            $this->logEntry->setExitCode($event->getExitCode());

            $this->entityManager->flush();

            $this->logger->error('Error ejecutando el comando: ' . $errorMessage);
        }
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        if ($this->logEntry) {
            // Solo registra el código de salida, ya que el mensaje de salida se maneja en el comando
            $exitCode = $event->getExitCode();
            $this->logEntry->setExitCode($exitCode);
            $this->logEntry->setEndTime(new \DateTime());
    
            $this->entityManager->flush();
    
            $this->logger->info('Comando finalizado con código de salida: ' . $exitCode);
        }
    }
}


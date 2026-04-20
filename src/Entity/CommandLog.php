<?php

namespace App\Entity;

use App\Repository\CommandLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandLogRepository::class)]
class CommandLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"ID_LOG")]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true,name:"NOMBRE_COMANDO")]
    private ?string $commandName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, name:"Argumentos")]
    private ?string $arguments = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, name:"OPCIONES")]
    private ?string $options = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name:"FECHA_EJECUCION")]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name:"FECHA_TERMINO")]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column(nullable: true, name:"CODE")]
    private ?int $exitCode = null;


    #[ORM\Column(type: Types::TEXT, nullable: true, name:"MENSAJE")]
    private ?string $errorMessage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommandName(): ?string
    {
        return $this->commandName;
    }

    public function setCommandName(?string $commandName): static
    {
        $this->commandName = $commandName;

        return $this;
    }

    public function getArguments(): ?string
    {
        return $this->arguments;
    }

    public function setArguments(?string $arguments): static
    {
        $this->arguments = $arguments;

        return $this;
    }

    public function getOptions(): ?string
    {
        return $this->options;
    }

    public function setOptions(?string $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeInterface $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeInterface $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getExitCode(): ?int
    {
        return $this->exitCode;
    }

    public function setExitCode(?int $exitCode): static
    {
        $this->exitCode = $exitCode;

        return $this;
    }


    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }
}

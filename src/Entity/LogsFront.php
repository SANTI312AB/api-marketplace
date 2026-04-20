<?php

namespace App\Entity;

use App\Repository\LogsFrontRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: LogsFrontRepository::class)]
class LogsFront
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"ID_LOG_FRONT")]
    private ?int $id = null;

    #[ORM\Column(length: 500, nullable: false,name:"MENSAJE")]
    private ?string $message = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE,name:"FECHA_REGISTRO")]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 500,name:"CONTEXTO")]
    private ?string $contex = null;

    #[ORM\Column(length: 255,name:"NIVEL")]
    private ?string $level = null;

    #[ORM\Column(length: 500, nullable: true,name:"META")]
    private ?string $meta = null;

    public function __construct()
    {
        $this->date = new DateTime();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getContex(): ?string
    {
        return $this->contex;
    }

    public function setContex(string $contex): static
    {
        $this->contex = $contex;

        return $this;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(string $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getMeta(): ?string
    {
        return $this->meta;
    }

    public function setMeta(?string $meta): static
    {
        $this->meta = $meta;

        return $this;
    }
}

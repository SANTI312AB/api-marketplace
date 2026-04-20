<?php

namespace App\Entity;

use App\Repository\LogsApiRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogsApiRepository::class)]
class LogsApi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDLOG")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'logsApis')]
    #[ORM\JoinColumn(nullable: true, name:"IDLOGIN",referencedColumnName:"IDLOGIN")]
    private ?Login $login = null;

    #[ORM\Column(length: 255, nullable: true,name:"ESTATUS_LOG")]
    private ?string $response_log = null;

    #[ORM\Column(length: 255, nullable: true,name:"ACCTION_LOG")]
    private ?string $acction_log = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true,name:"FECHA_LOG")]
    private ?\DateTimeInterface $fecha_log = null;

    #[ORM\Column(type: Types::TEXT, nullable: true,name:"TOKEN_LOG")]
    private ?string $token = null;

    #[ORM\Column(length: 255, nullable: true,name:"IP_ADDRESS")]
    private ?string $ip = null;

    #[ORM\Column(type: Types::TEXT, nullable: true,name:"MENSAJE_LOG")]
    private ?string $message = null;

    #[ORM\Column(length: 255, nullable: true,name:"METHOD_LOG")]
    private ?string $method = null;


    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResponseLog(): ?string
    {
        return $this->response_log;
    }

    public function setResponseLog(?string $response_log): static
    {
        $this->response_log = $response_log;

        return $this;
    }

    public function getAcctionLog(): ?string
    {
        return $this->acction_log;
    }

    public function setAcctionLog(?string $acction_log): static
    {
        $this->acction_log = $acction_log;

        return $this;
    }

    public function getFechaLog(): ?\DateTimeInterface
    {
        return $this->fecha_log;
    }

    public function setFechaLog(?\DateTimeInterface $fecha_log): static
    {
        $this->fecha_log = $fecha_log;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getLogin(): ?Login
    {
        return $this->login;
    }

    public function setLogin(?Login $login): static
    {
        $this->login = $login;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): static
    {
        $this->ip = $ip;

        return $this;
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

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(?string $method): static
    {
        $this->method = $method;

        return $this;
    }

   
}

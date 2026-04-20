<?php

namespace App\Entity;

use App\Repository\TokenRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TokenRepository::class)]
class Token
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDTOKEN")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tokens')]
    #[ORM\JoinColumn(nullable: false, name:"IDLOGIN", referencedColumnName:"IDLOGIN")]
    private ?Login $login = null;

    #[ORM\Column(length: 255, nullable: true,name:"CODIGO_TOKEN")]
    private ?string $token = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true,name:"FECHA_TOKEN")]
    private ?\DateTimeInterface $fecha = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true,name:"VENCIMIENTO_TOKEN")]
    private ?\DateTimeInterface $vencimiento = null;

    

    public function getId(): ?int
    {
        return $this->id;
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

    public function getFecha(): ?\DateTimeInterface
    {
        return $this->fecha;
    }

    public function setFecha(?\DateTimeInterface $fecha): static
    {
        $this->fecha = $fecha;

        return $this;
    }

    public function getVencimiento(): ?\DateTimeInterface
    {
        return $this->vencimiento;
    }

    public function setVencimiento(?\DateTimeInterface $vencimiento): static
    {
        $this->vencimiento = $vencimiento;

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
}

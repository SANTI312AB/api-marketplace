<?php

namespace App\Entity;

use App\Repository\OfertasRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OfertasRepository::class)]
class Ofertas
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDOFERTA")]
    private ?int $id = null;

    #[ORM\Column(name:"MONTO")]
    private ?float $monto = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE,name:"FECHA_CREACION")]
    private ?\DateTimeInterface $fecha = null;

    #[ORM\ManyToOne(inversedBy: 'ofertas')]
    #[ORM\JoinColumn(nullable: false,name:"IDLOGIN",referencedColumnName:"IDLOGIN")]
    private ?Login $login = null;


    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true,name:"FECHA_EDICION")]
    private ?\DateTimeInterface $fecha_edicion = null;

    #[ORM\ManyToOne(inversedBy: 'ofertas')]
    #[ORM\JoinColumn(nullable: false,name:"IDSUBASTA",referencedColumnName:"IDSUBASTA")]
    private ?Subastas $subasta = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMonto(): ?float
    {
        return $this->monto;
    }

    public function setMonto(float $monto): static
    {
        $this->monto = $monto;

        return $this;
    }

    public function getFecha(): ?\DateTimeInterface
    {
        return $this->fecha;
    }

    public function setFecha(\DateTimeInterface $fecha): static
    {
        $this->fecha = $fecha;

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

    

    public function getFechaEdicion(): ?\DateTimeInterface
    {
        return $this->fecha_edicion;
    }

    public function setFechaEdicion(?\DateTimeInterface $fecha_edicion): static
    {
        $this->fecha_edicion = $fecha_edicion;

        return $this;
    }

    public function getSubasta(): ?Subastas
    {
        return $this->subasta;
    }

    public function setSubasta(?Subastas $subasta): static
    {
        $this->subasta = $subasta;

        return $this;
    }
}

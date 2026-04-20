<?php

namespace App\Entity;

use App\Repository\RecargasRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecargasRepository::class)]
class Recargas
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"ID_RECARGA")]
    private ?int $id = null;


    #[ORM\ManyToOne(inversedBy: 'recargas')]
    #[ORM\JoinColumn(nullable: false, name:"ID_SALDO", referencedColumnName:"ID_SALDO")]
    private ?Saldo $saldo = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE,name:"FECHA_RECARGA")]
    private ?\DateTimeInterface $fecha = null;

    #[ORM\ManyToOne(inversedBy: 'recargas')]
    #[ORM\JoinColumn(nullable: true, name:"IDRETIRO", referencedColumnName:"IDRETIRO")]
    private ?Retiros $retiro = null;

    #[ORM\ManyToOne(inversedBy: 'recargas')]
    #[ORM\JoinColumn(nullable: true, name:"IDPRODUCTO", referencedColumnName:"IDPRODUCTO")]
    private ?Productos $producto = null;

    #[ORM\Column(length: 255,name:"TIPO_RECARGA")]
    private ?string $tipo_recarga = null;

    public function __construct(){
        $this->fecha = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

   
    public function getSaldo(): ?Saldo
    {
        return $this->saldo;
    }

    public function setSaldo(?Saldo $saldo): static
    {
        $this->saldo = $saldo;

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

    public function getRetiro(): ?Retiros
    {
        return $this->retiro;
    }

    public function setRetiro(?Retiros $retiro): static
    {
        $this->retiro = $retiro;

        return $this;
    }

    public function getProducto(): ?Productos
    {
        return $this->producto;
    }

    public function setProducto(?Productos $producto): static
    {
        $this->producto = $producto;

        return $this;
    }

    public function getTipoRecarga(): ?string
    {
        return $this->tipo_recarga;
    }

    public function setTipoRecarga(string $tipo_recarga): static
    {
        $this->tipo_recarga = $tipo_recarga;

        return $this;
    }
}

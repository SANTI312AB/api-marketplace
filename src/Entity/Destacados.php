<?php

namespace App\Entity;

use App\Repository\DestacadosRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DestacadosRepository::class)]
class Destacados
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"ID_DESTACADO")]
    private ?int $id = null;

    #[ORM\Column(length: 255, name:"TITULO")]
    private ?string $titulo = null;

    #[ORM\Column(length: 255, nullable: true,name:"DESCRIPCION")]
    private ?string $descripcion = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, name:"FECHA_CREACION")]
    private ?\DateTimeInterface $creat_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name:"FECHA_ACTUALIZACION")]
    private ?\DateTimeInterface $update_at = null;

    #[ORM\Column(length: 255, nullable: false, name:"ICONO")]
    private ?string $icono = null;

    #[ORM\Column(length: 255, nullable: true, name:"IMAGEN")]
    private ?string $imagen = null;

    #[ORM\Column(length: 255, nullable: true,name:"HREF")]
    private ?string $href = null;

    #[ORM\Column(nullable: false, name:"ORDEN")]
    private ?int $orden = null;

    #[ORM\OneToOne(inversedBy: 'destacados', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true,name:"IDPRODUCTO",referencedColumnName:"IDPRODUCTO")]
    private ?Productos $producto = null;


    public function __construct()
    { 
        $this->creat_at = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitulo(): ?string
    {
        return $this->titulo;
    }

    public function setTitulo(string $titulo): static
    {
        $this->titulo = $titulo;

        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): static
    {
        $this->descripcion = $descripcion;

        return $this;
    }

    public function getCreatAt(): ?\DateTimeInterface
    {
        return $this->creat_at;
    }

    public function setCreatAt(\DateTimeInterface $creat_at): static
    {
        $this->creat_at = $creat_at;

        return $this;
    }

    public function getUpdateAt(): ?\DateTimeInterface
    {
        return $this->update_at;
    }

    public function setUpdateAt(?\DateTimeInterface $update_at): static
    {
        $this->update_at = $update_at;

        return $this;
    }

    public function getIcono(): ?string
    {
        return $this->icono;
    }

    public function setIcono(?string $icono): static
    {
        $this->icono = $icono;

        return $this;
    }

    public function getImagen(): ?string
    {
        return $this->imagen;
    }

    public function setImagen(?string $imagen): static
    {
        $this->imagen = $imagen;

        return $this;
    }

    public function getHref(): ?string
    {
        return $this->href;
    }

    public function setHref(?string $href): static
    {
        $this->href = $href;

        return $this;
    }

    public function getOrden(): ?int
    {
        return $this->orden;
    }

    public function setOrden(?int $orden): static
    {
        $this->orden = $orden;

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

    
}

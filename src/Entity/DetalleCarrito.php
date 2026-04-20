<?php

namespace App\Entity;

use App\Repository\DetalleCarritoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DetalleCarritoRepository::class)]
class DetalleCarrito
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"ID_DETALLE_CARRITO")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'detalleCarritos')]
    #[ORM\JoinColumn(nullable: false, name:"IDCARRITO", referencedColumnName:"IDCARRITO")]
    private ?Carrito $carrito = null;


    #[ORM\Column(nullable: true,name:"CANTIDAD_PRODUCTO")]
    private ?int $cantidad = 1;

    #[ORM\ManyToOne(inversedBy: 'detalleCarritos')]
    #[ORM\JoinColumn(nullable: false,name:"IDPRODUCTO", referencedColumnName:"IDPRODUCTO")]
    private ?Productos $IdProducto = null;

    #[ORM\ManyToOne(inversedBy: 'detalleCarritos')]
    #[ORM\JoinColumn(nullable: true,name:"IDVARIACION",referencedColumnName:"IDVARIACION")]
    private ?Variaciones $IdVariacion = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCarrito(): ?Carrito
    {
        return $this->carrito;
    }

    public function setCarrito(?Carrito $carrito): static
    {
        $this->carrito = $carrito;

        return $this;
    }


    public function getCantidad(): ?int
    {
        return $this->cantidad;
    }

    public function setCantidad(?int $cantidad): static
    {
        $this->cantidad = $cantidad;

        return $this;
    }

    public function getIdProducto(): ?Productos
    {
        return $this->IdProducto;
    }

    public function setIdProducto(?Productos $IdProducto): static
    {
        $this->IdProducto = $IdProducto;

        return $this;
    }

    public function getIdVariacion(): ?Variaciones
    {
        return $this->IdVariacion;
    }

    public function setIdVariacion(?Variaciones $IdVariacion): static
    {
        $this->IdVariacion = $IdVariacion;

        return $this;
    }




}

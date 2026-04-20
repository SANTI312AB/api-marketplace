<?php

namespace App\Entity;

use App\Repository\ProductosGaleriaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductosGaleriaRepository::class)]
class ProductosGaleria
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDPRODUCTO_GALERIA")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'productosGalerias')]
    #[ORM\JoinColumn(nullable: false,name:"IDPRODUCTO", referencedColumnName:"IDPRODUCTO")]
    private ?Productos $producto = null;

    #[ORM\Column(length: 500, nullable: true,name:"URL_PRODUCTO_GALERIA")]
    private ?string $url_producto_galeria = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrlProductoGaleria(): ?string
    {
        return $this->url_producto_galeria;
    }

    public function setUrlProductoGaleria(?string $url_producto_galeria): static
    {
        $this->url_producto_galeria = $url_producto_galeria;

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

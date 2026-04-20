<?php

namespace App\Entity;

use App\Repository\ProductosVentasRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductosVentasRepository::class)]
class ProductosVentas
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDTIPO_VENTA")]
    private ?int $id = null;

    #[ORM\Column(length: 25, nullable: true, name:"TIPO_VENTA_PRODUCTO")]
    private ?string $tipo_venta = null;

    #[ORM\OneToMany(mappedBy: 'productos_ventas', targetEntity: Productos::class, orphanRemoval: true)]
    private Collection $productos;

    public function __construct()
    {
        $this->productos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTipoVenta(): ?string
    {
        return $this->tipo_venta;
    }

    public function setTipoVenta(?string $tipo_venta): static
    {
        $this->tipo_venta = $tipo_venta;

        return $this;
    }

    /**
     * @return Collection<int, Productos>
     */
    public function getProductos(): Collection
    {
        return $this->productos;
    }

    public function addProducto(Productos $producto): static
    {
        if (!$this->productos->contains($producto)) {
            $this->productos->add($producto);
            $producto->setProductosVentas($this);
        }

        return $this;
    }

    public function removeProducto(Productos $producto): static
    {
        if ($this->productos->removeElement($producto)) {
            // set the owning side to null (unless already changed)
            if ($producto->getProductosVentas() === $this) {
                $producto->setProductosVentas(null);
            }
        }

        return $this;
    }
}

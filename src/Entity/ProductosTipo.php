<?php

namespace App\Entity;

use App\Repository\ProductosTipoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductosTipoRepository::class)]
class ProductosTipo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDTIPO_PRODUCTO")]
    private ?int $id = null;

    #[ORM\Column(length: 45,nullable: true,name:"TIPO_PRODUCTO")]
    private ?string $tipo = null;

    #[ORM\OneToMany(mappedBy: 'productos_tipo', targetEntity: Productos::class, orphanRemoval: true)]
    private Collection $productos;

    public function __construct()
    {
        $this->productos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTipo(): ?string
    {
        return $this->tipo;
    }

    public function setTipo(string $tipo): static
    {
        $this->tipo = $tipo;

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
            $producto->setProductosTipo($this);
        }

        return $this;
    }

    public function removeProducto(Productos $producto): static
    {
        if ($this->productos->removeElement($producto)) {
            // set the owning side to null (unless already changed)
            if ($producto->getProductosTipo() === $this) {
                $producto->setProductosTipo(null);
            }
        }

        return $this;
    }
}

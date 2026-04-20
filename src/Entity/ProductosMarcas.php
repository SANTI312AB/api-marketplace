<?php

namespace App\Entity;

use App\Repository\ProductosMarcasRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductosMarcasRepository::class)]
class ProductosMarcas
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDMARCA")]
    private ?int $id = null;

    #[ORM\Column(length: 45, nullable: true,name:"NOMBRE_MARCA",unique:true)]
    private ?string $nombre_m = null;

    #[ORM\Column(length: 45, nullable: true,name:"COMPANIA_MARCA")]
    private ?string $compania = null;

    #[ORM\OneToMany(mappedBy: 'marcas', targetEntity: Productos::class, orphanRemoval: true)]
    private Collection $productos;

    #[ORM\JoinTable(name: 'categorias_marcas')]
    #[ORM\JoinColumn(name: 'IDMARCA', referencedColumnName: 'IDMARCA',onDelete:'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'IDCATEGORIA', referencedColumnName: 'IDCATEGORIA')]
    #[ORM\ManyToMany(targetEntity: Categorias::class, inversedBy: 'productosMarcas')]
    private Collection $categorias;

    #[ORM\Column(length: 255,name:"MARCAS_SLUG",nullable:true,unique:true)]
    private ?string $slug = null;

    #[ORM\Column(length: 255, nullable: true,name:"MARCAS_LOGO")]
    private ?string $logo = null;

    #[ORM\Column(nullable: true,name:"PUBLISHED")]
    private ?bool $published = null;


    public function __construct()
    {
        $this->productos = new ArrayCollection();
        $this->categorias = new ArrayCollection();
    }



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombreM(): ?string
    {
        return $this->nombre_m;
    }

    public function setNombreM(?string $nombre_m): static
    {
        $this->nombre_m = $nombre_m;

        return $this;
    }

    public function getCompania(): ?string
    {
        return $this->compania;
    }

    public function setCompania(?string $compania): static
    {
        $this->compania = $compania;

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
            $producto->setMarcas($this);
        }

        return $this;
    }

    public function removeProducto(Productos $producto): static
    {
        if ($this->productos->removeElement($producto)) {
            // set the owning side to null (unless already changed)
            if ($producto->getMarcas() === $this) {
                $producto->setMarcas(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Categorias>
     */
    public function getCategorias(): Collection
    {
        return $this->categorias;
    }

    public function addCategoria(Categorias $categoria): static
    {
        if (!$this->categorias->contains($categoria)) {
            $this->categorias->add($categoria);
        }

        return $this;
    }

    public function removeCategoria(Categorias $categoria): static
    {
        $this->categorias->removeElement($categoria);

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    public function isPublished(): ?bool
    {
        return $this->published;
    }

    public function setPublished(?bool $published): static
    {
        $this->published = $published;

        return $this;
    }

  
}

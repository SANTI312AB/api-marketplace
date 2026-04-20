<?php

namespace App\Entity;

use App\Repository\SubcategoriasRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubcategoriasRepository::class)]
class Subcategorias
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDSUBCATEGORIA")]
    private ?int $id = null;

    #[ORM\Column(length: 45, nullable: true,name:"NOMBRE_SUBCATEGORIA")]
    private ?string $nombre = null;

    #[ORM\ManyToOne(inversedBy: 'subcategorias')]
    #[ORM\JoinColumn(nullable: false, name:"IDCATEGORIA", referencedColumnName:"IDCATEGORIA")]
    private ?Categorias $categorias = null;

    #[ORM\JoinTable(name: 'productos_subcategorias')]
    #[ORM\JoinColumn(name: 'IDSUBCATEGORIA', referencedColumnName: 'IDSUBCATEGORIA')]
    #[ORM\InverseJoinColumn(name: 'IDPRODUCTO', referencedColumnName: 'IDPRODUCTO')]
    #[ORM\ManyToMany(targetEntity: Productos::class, mappedBy: 'subcategorias')]
    private Collection $productos;

    #[ORM\Column(length: 255, nullable: true,name:"SUBCATEGORIA_SLUG",unique:true)]
    private ?string $slug = null;

    #[ORM\Column(length: 255, nullable: true,name:"SUBCATEGORIA_IMAGEN")]
    private ?string $image = null;

    #[ORM\Column(nullable: true,name:"FECHA_CREACION")]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true,name:"FECHA_EDICION")]
    private ?\DateTimeImmutable $update_at = null;

    public function __construct()
    {
        $this->productos = new ArrayCollection();
    }

    


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(?string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getCategorias(): ?Categorias
    {
        return $this->categorias;
    }

    public function setCategorias(?Categorias $categorias): static
    {
        $this->categorias = $categorias;

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
            $producto->addSubcategoria($this);
        }

        return $this;
    }

    public function removeProducto(Productos $producto): static
    {
        if ($this->productos->removeElement($producto)) {
            $producto->removeSubcategoria($this);
        }

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdateAt(): ?\DateTimeImmutable
    {
        return $this->update_at;
    }

    public function setUpdateAt(?\DateTimeImmutable $update_at): static
    {
        $this->update_at = $update_at;

        return $this;
    }

 
}

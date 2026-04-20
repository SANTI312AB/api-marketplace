<?php

namespace App\Entity;

use App\Repository\SubcategoriasTiendasRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubcategoriasTiendasRepository::class)]
class SubcategoriasTiendas
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDSUBCATEGORIATIENDA")]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: false,name:"NOMBRE_SUBCATEGORIA_TIENDA")]
    private ?string $nombre = null;

    #[ORM\Column(length: 255, nullable: true, name:"IMAGEN_SUBCATEGORIA_TIENDA")]
    private ?string $imagen = null;

    #[ORM\Column(length: 255, nullable: false, name:"SLUG_SUBCATEGORIA_TIENDA", unique:true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE,name:"FECHA_CREACION")]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE,nullable: true, name:"FECHA_EDICION")]
    private ?\DateTimeInterface $update_at = null;

    #[ORM\ManyToOne(inversedBy: 'subcategoriasTiendas')]
    #[ORM\JoinColumn(nullable: false, referencedColumnName:"IDCATEGORIA_TIENDA", name:"IDCATEGORIA_TIENDA")]
    private ?CategoriasTienda $categoriaTienda = null;

    #[ORM\JoinTable(name: 'productos_subcategorias_tienda')]
    #[ORM\JoinColumn(name: 'IDSUBCATEGORIATIENDA', referencedColumnName: 'IDSUBCATEGORIATIENDA')]
    #[ORM\InverseJoinColumn(name: 'IDPRODUCTO', referencedColumnName: 'IDPRODUCTO')]
    #[ORM\ManyToMany(targetEntity: Productos::class, inversedBy: 'subcategoriasTiendas')]
    private Collection $productos;


    public function __construct()
    {
     
        $this->created_at = new \DateTime();
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

    public function getImagen(): ?string
    {
        return $this->imagen;
    }

    public function setImagen(?string $imagen): static
    {
        $this->imagen = $imagen;

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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;

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

    public function getCategoriaTienda(): ?CategoriasTienda
    {
        return $this->categoriaTienda;
    }

    public function setCategoriaTienda(?CategoriasTienda $categoriaTienda): static
    {
        $this->categoriaTienda = $categoriaTienda;

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
        }

        return $this;
    }

    public function removeProducto(Productos $producto): static
    {
        $this->productos->removeElement($producto);

        return $this;
    }
}

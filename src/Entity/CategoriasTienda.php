<?php

namespace App\Entity;

use App\Repository\CategoriasTiendaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: CategoriasTiendaRepository::class)]
class CategoriasTienda
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDCATEGORIA_TIENDA")]
    private ?int $id = null;

    #[ORM\Column(length: 255,name:"NOMBRE_CATEGORIA_TIENDA")]
    private ?string $nombre = null;

    #[ORM\Column(length: 255, nullable: true,name:"BANNER_CATEGORIA_TIENDA")]
    private ?string $banner = null;

    #[ORM\Column(length: 255,name:"SLUG_CATEGORIA_TIENDA",unique:true)]
    private ?string $slug = null;

    #[ORM\ManyToOne(inversedBy: 'categoriasTiendas')]
    #[ORM\JoinColumn(nullable: false,name:"IDTIENDA",referencedColumnName:"IDTIENDA")]
    private ?Tiendas $Tiendas = null;


    #[ORM\JoinTable(name: 'productos_categorias_tienda')]
    #[ORM\JoinColumn(name: 'IDCATEGORIA_TIENDA', referencedColumnName: 'IDCATEGORIA_TIENDA')]
    #[ORM\InverseJoinColumn(name: 'IDPRODUCTO', referencedColumnName: 'IDPRODUCTO')]
    #[ORM\ManyToMany(targetEntity: Productos::class, mappedBy: 'categoriasTiendas')]
    private Collection $productos;

    #[ORM\Column(length: 255, nullable: true,name:"CATEGORIA_TIENDA_IMAGEN")]
    private ?string $imagen = null;

    /**
     * @var Collection<int, SubcategoriasTiendas>
     */
    #[ORM\OneToMany(mappedBy: 'categoriaTienda', targetEntity: SubcategoriasTiendas::class, orphanRemoval: true)]
    private Collection $subcategoriasTiendas;

    #[ORM\Column(nullable: true, type: Types::DATETIME_MUTABLE, name:"FECHA_CREACION" )]
    private ?\DateTimeInterface $creat_at = null;

    #[ORM\Column(nullable: true, type: Types::DATETIME_MUTABLE, name:"FECHA_EDICION")]
    private ?\DateTimeInterface $update_at = null;

    public function __construct()
    {
        $this->productos = new ArrayCollection();
        $this->subcategoriasTiendas = new ArrayCollection();
        $this->creat_at = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getBanner(): ?string
    {
        return $this->banner;
    }

    public function setBanner(?string $banner): static
    {
        $this->banner = $banner;

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

    public function getTiendas(): ?Tiendas
    {
        return $this->Tiendas;
    }

    public function setTiendas(?Tiendas $Tiendas): static
    {
        $this->Tiendas = $Tiendas;

        return $this;
    }

    /**
     * @return Collection<int, Productos>
     */
    public function getProductos(): Collection
    {
        return $this->productos;
    }

    public function addProducto(Productos $producto): self
    {
        if (!$this->productos->contains($producto)) {
            $this->productos->add($producto);
            $producto->addCategoriasTienda($this);
        }

        return $this;
    }

    public function removeProducto(Productos $producto): self
    {
        if ($this->productos->removeElement($producto)) {
            $producto->removeCategoriasTienda($this);
        }

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

    /**
     * @return Collection<int, SubcategoriasTiendas>
     */
    public function getSubcategoriasTiendas(): Collection
    {
        return $this->subcategoriasTiendas;
    }

    public function addSubcategoriasTienda(SubcategoriasTiendas $subcategoriasTienda): static
    {
        if (!$this->subcategoriasTiendas->contains($subcategoriasTienda)) {
            $this->subcategoriasTiendas->add($subcategoriasTienda);
            $subcategoriasTienda->setCategoriaTienda($this);
        }

        return $this;
    }

    public function removeSubcategoriasTienda(SubcategoriasTiendas $subcategoriasTienda): static
    {
        if ($this->subcategoriasTiendas->removeElement($subcategoriasTienda)) {
            // set the owning side to null (unless already changed)
            if ($subcategoriasTienda->getCategoriaTienda() === $this) {
                $subcategoriasTienda->setCategoriaTienda(null);
            }
        }

        return $this;
    }

    public function getCreatAt(): ?\DateTimeInterface
    {
        return $this->creat_at;
    }

    public function setCreatAt(?\DateTimeInterface $creat_at): static
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
}

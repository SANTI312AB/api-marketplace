<?php

namespace App\Entity;

use App\Repository\CategoriasRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;



#[ORM\Entity(repositoryClass: CategoriasRepository::class)]
class Categorias
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDCATEGORIA")]
    private ?int $id = null;

    #[ORM\Column(length: 45, nullable: true,name:"NOMBRE_CATEGORIA",unique:true)]
    private ?string $nombre = null;

    #[ORM\OneToMany(mappedBy: 'categorias', targetEntity: Subcategorias::class, orphanRemoval: true)]
    private Collection $subcategorias;

    #[ORM\Column(length: 255, nullable: true,name:"CATEGORIA_SLUG",unique:true)]
    private ?string $slug = null;

    #[ORM\Column(length: 255, nullable: true,name:"CATEGORIA_BANNER")]
    private ?string $banner = null;

    #[ORM\Column(length: 255, nullable: true,name:"CATEGORIA_IMG_SLIDER")]
    private ?string $img = null;


    #[ORM\Column(length: 255, nullable: true,name:"CATEGORIA_TITULO")]
    private ?string $title = null;

    #[ORM\JoinTable(name: 'productos_categorias')]
    #[ORM\JoinColumn(name: 'IDCATEGORIA', referencedColumnName: 'IDCATEGORIA')]
    #[ORM\InverseJoinColumn(name: 'IDPRODUCTO', referencedColumnName: 'IDPRODUCTO')]
    #[ORM\ManyToMany(targetEntity: Productos::class, mappedBy: 'categorias')]
    private Collection $productos;


    #[ORM\Column(length: 255, nullable: true,name:"CATEGORIA_IMAGEN")]
    private ?string $imagen = null;

    #[ORM\JoinTable(name: 'categorias_marcas')]
    #[ORM\JoinColumn(name: 'IDCATEGORIA', referencedColumnName: 'IDCATEGORIA')]
    #[ORM\InverseJoinColumn(name: 'IDMARCA', referencedColumnName: 'IDMARCA')]
    #[ORM\ManyToMany(targetEntity: ProductosMarcas::class, mappedBy: 'categorias')]
    private Collection $productosMarcas;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true,name:"FECHA_CREACION")]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true,name:"FECHA_EDICION")]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\Column(nullable: true,name:"DESTACADO")]
    private ?bool $destacado = null;

    #[ORM\Column(nullable: true,name:"PUBLICADO")]
    private ?bool $publicado = null;

    /**
     * @var Collection<int, BloquesPromocionales>
     */
    #[ORM\OneToMany(mappedBy: 'categoria', targetEntity: BloquesPromocionales::class, orphanRemoval: true)]
    private Collection $bloquesPromocionales;

    #[ORM\Column(length: 255, nullable: true,name:"DESCRIPCION_CATEGORIA")]
    private ?string $descripcion = null;


    public function __construct()
    {
        $this->subcategorias = new ArrayCollection();
        $this->productos = new ArrayCollection();
        $this->productosMarcas = new ArrayCollection();
        $this->bloquesPromocionales = new ArrayCollection();
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

    /**
     * @return Collection<int, Subcategorias>
     */
    public function getSubcategorias(): Collection
    {
        return $this->subcategorias;
    }

    public function addSubcategoria(Subcategorias $subcategoria): static
    {
        if (!$this->subcategorias->contains($subcategoria)) {
            $this->subcategorias->add($subcategoria);
            $subcategoria->setCategorias($this);
        }

        return $this;
    }

    public function removeSubcategoria(Subcategorias $subcategoria): static
    {
        if ($this->subcategorias->removeElement($subcategoria)) {
            // set the owning side to null (unless already changed)
            if ($subcategoria->getCategorias() === $this) {
                $subcategoria->setCategorias(null);
            }
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

    public function getBanner(): ?string
    {
        return $this->banner;
    }

    public function setBanner(?string $banner): static
    {
        $this->banner = $banner;

        return $this;
    }

    public function getImg(): ?string
    {
        return $this->img;
    }

    public function setImg(?string $img): static
    {
        $this->img = $img;

        return $this;
    }


    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

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
            $producto->addCategoria($this);
        }

        return $this;
    }

    public function removeProducto(Productos $producto): static
    {
        if ($this->productos->removeElement($producto)) {
            $producto->removeCategoria($this);
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
     * @return Collection<int, ProductosMarcas>
     */
    public function getProductosMarcas(): Collection
    {
        return $this->productosMarcas;
    }

    public function addProductosMarca(ProductosMarcas $productosMarca): static
    {
        if (!$this->productosMarcas->contains($productosMarca)) {
            $this->productosMarcas->add($productosMarca);
            $productosMarca->addCategoria($this);
        }

        return $this;
    }

    public function removeProductosMarca(ProductosMarcas $productosMarca): static
    {
        if ($this->productosMarcas->removeElement($productosMarca)) {
            $productosMarca->removeCategoria($this);
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function isDestacado(): ?bool
    {
        return $this->destacado;
    }

    public function setDestacado(?bool $destacado): static
    {
        $this->destacado = $destacado;

        return $this;
    }

    public function isPublicado(): ?bool
    {
        return $this->publicado;
    }

    public function setPublicado(?bool $publicado): static
    {
        $this->publicado = $publicado;

        return $this;
    }

    /**
     * @return Collection<int, BloquesPromocionales>
     */
    public function getBloquesPromocionales(): Collection
    {
        return $this->bloquesPromocionales;
    }

    public function addBloquesPromocionale(BloquesPromocionales $bloquesPromocionale): static
    {
        if (!$this->bloquesPromocionales->contains($bloquesPromocionale)) {
            $this->bloquesPromocionales->add($bloquesPromocionale);
            $bloquesPromocionale->setCategoria($this);
        }

        return $this;
    }

    public function removeBloquesPromocionale(BloquesPromocionales $bloquesPromocionale): static
    {
        if ($this->bloquesPromocionales->removeElement($bloquesPromocionale)) {
            // set the owning side to null (unless already changed)
            if ($bloquesPromocionale->getCategoria() === $this) {
                $bloquesPromocionale->setCategoria(null);
            }
        }

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


}

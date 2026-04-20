<?php

namespace App\Entity;

use App\Repository\BloquesPromocionalesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BloquesPromocionalesRepository::class)]
class BloquesPromocionales
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDBLOQUE_PROMOCIONAL")]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true, name:"HREF_BLOQUE")]
    private ?string $href = null;

    #[ORM\Column(name:"ORDER_BLOQUE")]
    private ?int $orden = null;

    #[ORM\Column(nullable: true, name:"VISIBLE")]
    private ?bool $visible = null;

    #[ORM\Column(nullable: true, name:"RANDOM_BLOQUE")]
    private ?bool $random = null;

    #[ORM\Column(length: 255, name:"TITLE_BLOQUE")]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true, name:"ORIENTATION_BLOQUE")]
    private ?string $orientacion = null;

    #[ORM\Column(nullable: true, name:"FEATURED_BLOCK_PRODUCT_ID")]
    private ?int $future_product = null;


    #[ORM\ManyToOne(inversedBy: 'bloquesPromocionales')]
    #[ORM\JoinColumn(nullable: true, name:"IDCATEGORIA", referencedColumnName:"IDCATEGORIA")]
    private ?Categorias $categoria = null;


    #[ORM\JoinTable(name: 'bloque_producto')]
    #[ORM\JoinColumn(name: 'IDBLOQUE_PROMOCIONAL', referencedColumnName: 'IDBLOQUE_PROMOCIONAL',onDelete:'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'IDPRODUCTO', referencedColumnName: 'IDPRODUCTO')]
    #[ORM\ManyToMany(targetEntity: Productos::class, inversedBy: 'bloquesPromocionales')]
    private Collection $productos;

    public function __construct()
    {
        $this->productos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function setOrden(int $orden): static
    {
        $this->orden = $orden;

        return $this;
    }

    public function isVisible(): ?bool
    {
        return $this->visible;
    }

    public function setVisible(?bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    public function isRandom(): ?bool
    {
        return $this->random;
    }

    public function setRandom(?bool $random): static
    {
        $this->random = $random;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getOrientacion(): ?string
    {
        return $this->orientacion;
    }

    public function setOrientacion(?string $orientacion): static
    {
        $this->orientacion = $orientacion;

        return $this;
    }

    public function getFutureProduct(): ?int
    {
        return $this->future_product;
    }

    public function setFutureProduct(?int $future_product): static
    {
        $this->future_product = $future_product;

        return $this;
    }


    public function getCategoria(): ?Categorias
    {
        return $this->categoria;
    }

    public function setCategoria(?Categorias $categoria): static
    {
        $this->categoria = $categoria;

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

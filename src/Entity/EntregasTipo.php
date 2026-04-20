<?php

namespace App\Entity;

use App\Repository\EntregasTipoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EntregasTipoRepository::class)]
class EntregasTipo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDENTRAGAS_TIPO")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'entregasTipos')]
    #[ORM\JoinColumn(nullable: false,name:"IDESTADO", referencedColumnName:"IDESTADO")]
    private ?Estados $estados = null;

    #[ORM\Column(length: 45, nullable: true, name:"TIPOS_ENTREGA")]
    private ?string $tipo = null;

    #[ORM\OneToMany(mappedBy: 'entrgas_tipo', targetEntity: Productos::class, orphanRemoval: true)]
    private Collection $productos;

    #[ORM\Column(length: 255, nullable: true, name:'SLUG')]
    private ?string $slug = null;

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

    public function setTipo(?string $tipo): static
    {
        $this->tipo = $tipo;

        return $this;
    }

    public function getEstados(): ?Estados
    {
        return $this->estados;
    }

    public function setEstados(?Estados $estados): static
    {
        $this->estados = $estados;

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
            $producto->setEntrgasTipo($this);
        }

        return $this;
    }

    public function removeProducto(Productos $producto): static
    {
        if ($this->productos->removeElement($producto)) {
            // set the owning side to null (unless already changed)
            if ($producto->getEntrgasTipo() === $this) {
                $producto->setEntrgasTipo(null);
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
}

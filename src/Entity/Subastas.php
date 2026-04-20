<?php

// src/Entity/Subastas.php

namespace App\Entity;

use App\Repository\SubastasRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubastasRepository::class)]
class Subastas
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDSUBASTA")]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, name:"INICIO_SUBASTA")]
    private ?\DateTimeInterface $inicio_subasta = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false, name:"FIN_SUBASTA")]
    private ?\DateTimeInterface $fin_subasta = null;

    #[ORM\ManyToOne(inversedBy: 'subastas')]
    #[ORM\JoinColumn(nullable: false, name:"IDPRODUCTO", referencedColumnName:"IDPRODUCTO")]
    private ?Productos $IdProducto = null;

    #[ORM\ManyToOne(inversedBy: 'subastas')]
    #[ORM\JoinColumn(nullable: true, name:"IDVARIACION", referencedColumnName:"IDVARIACION")]
    private ?Variaciones $IdVariacion = null;

    #[ORM\Column(name:"VALOR_MINIMO")]
    private ?float $valor_minimo = null;

    #[ORM\OneToMany(mappedBy: 'subasta', targetEntity: Ofertas::class, orphanRemoval: true)]
    private Collection $ofertas;

    #[ORM\ManyToOne(inversedBy: 'subastas')]
    #[ORM\JoinColumn(nullable: false, name:"IDTIENDA", referencedColumnName:"IDTIENDA")]
    private ?Tiendas $tienda = null;

    #[ORM\Column(nullable: false,name:"ACTIVO")]
    private ?bool $activo = null;

    public function __construct()
    {
        $this->ofertas = new ArrayCollection();
        $this->inicio_subasta = new \DateTime();
    }

    // Getters y setters ...

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInicioSubasta(): ?\DateTimeInterface
    {
        return $this->inicio_subasta;
    }

    public function setInicioSubasta(\DateTimeInterface $inicio_subasta): static
    {
        $this->inicio_subasta = $inicio_subasta;

        return $this;
    }

    public function getFinSubasta(): ?\DateTimeInterface
    {
        return $this->fin_subasta;
    }

    public function setFinSubasta(?\DateTimeInterface $fin_subasta): static
    {
        $this->fin_subasta = $fin_subasta;

        return $this;
    }

    public function getIdProducto(): ?Productos
    {
        return $this->IdProducto;
    }

    public function setIdProducto(?Productos $IdProducto): static
    {
        $this->IdProducto = $IdProducto;

        return $this;
    }

    public function getIdVariacion(): ?Variaciones
    {
        return $this->IdVariacion;
    }

    public function setIdVariacion(?Variaciones $IdVariacion): static
    {
        $this->IdVariacion = $IdVariacion;

        return $this;
    }

    public function getValorMinimo(): ?float
    {
        return $this->valor_minimo;
    }

    public function setValorMinimo(float $valor_minimo): static
    {
        $this->valor_minimo = $valor_minimo;

        return $this;
    }

    public function getOfertas(): Collection
    {
        return $this->ofertas;
    }

    public function addOferta(Ofertas $oferta): static
    {
        if (!$this->ofertas->contains($oferta)) {
            $this->ofertas->add($oferta);
            $oferta->setSubasta($this);
        }

        return $this;
    }

    public function removeOferta(Ofertas $oferta): static
    {
        if ($this->ofertas->removeElement($oferta)) {
            if ($oferta->getSubasta() === $this) {
                $oferta->setSubasta(null);
            }
        }

        return $this;
    }

    public function getTienda(): ?Tiendas
    {
        return $this->tienda;
    }

    public function setTienda(?Tiendas $tienda): static
    {
        $this->tienda = $tienda;

        return $this;
    }

    public function isActivo(): ?bool
    {
        return $this->activo;
    }

    public function setActivo(?bool $activo): static
    {
        $this->activo = $activo;

        return $this;
    }
}


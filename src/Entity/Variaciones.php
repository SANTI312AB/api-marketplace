<?php

namespace App\Entity;

use App\Repository\VariacionesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: VariacionesRepository::class)]
#[UniqueEntity(fields: ['codigo_variante'], message: 'Este código de variante ya está registrado.')]
class Variaciones
{
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDVARIACION")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'variaciones')]
    #[ORM\JoinColumn(nullable: false,name:"IDPRODUCTO", referencedColumnName:"IDPRODUCTO")]
    private ?Productos $productos = null;

    #[ORM\Column(type: Types::TEXT, nullable: true,name:"DESCRIPCION_VARIACION")]
    private ?string $descripcion = null;

    #[ORM\JoinTable(name: 'variaciones_terminos')]
    #[ORM\JoinColumn(name: 'IDVARIACION', referencedColumnName: 'IDVARIACION')]
    #[ORM\InverseJoinColumn(name: 'IDTERMINOS', referencedColumnName: 'IDTERMINOS')]
    #[ORM\ManyToMany(targetEntity: Terminos::class, inversedBy: 'variaciones')]
    private Collection $terminos;

    #[ORM\Column(nullable: true,name:"PRECIO_VARIACION")]
    private ?float $precio = null;

    #[ORM\Column(nullable: true,name:"PRECIO_REBAJADO_VARIACION")]
    private ?float $precio_rebajado = null;

    #[ORM\Column(nullable: true, name:"CANTIDAD_VARIACION")]
    private ?int $cantidad = null;

    #[ORM\Column(length: 255, nullable: true, name:"SKU_VARIACIONES")]
    private ?string $sku = null;

    #[ORM\OneToMany(mappedBy: 'variaciones', targetEntity: VariacionesGaleria::class, orphanRemoval: true)]
    private Collection $variacionesGalerias;

    #[ORM\OneToMany(mappedBy: 'IdVariacion', targetEntity: DetalleCarrito::class, orphanRemoval: true)]
    private Collection $detalleCarritos;

    #[ORM\OneToMany(mappedBy: 'IdVariacion', targetEntity: DetallePedido::class, orphanRemoval: true)]
    private Collection $detallePedidos;

    /**
     * @var Collection<int, Subastas>
     */
    #[ORM\OneToMany(mappedBy: 'IdVariacion', targetEntity: Subastas::class, orphanRemoval: true)]
    private Collection $subastas;

    #[ORM\Column(nullable: true, name:"VARIANTE_DISPONIBILIDAD")]
    private ?bool $disponibilidad_variante = null;

    /**
     * @var Collection<int, Regateos>
     */
    #[ORM\OneToMany(mappedBy: 'variacion', targetEntity: Regateos::class, orphanRemoval: true)]
    private Collection $regateos;

    #[ORM\Column(length: 50, nullable: true,name:"CODIGO_VARIANTE", unique:true)]
    private ?string $codigo_variante = null;


    public function __construct()
    {
        $this->terminos = new ArrayCollection();
        $this->variacionesGalerias = new ArrayCollection();
        $this->detalleCarritos = new ArrayCollection();
        $this->detallePedidos = new ArrayCollection();
        $this->subastas = new ArrayCollection();
        $this->regateos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

   
    public function getProductos(): ?Productos
    {
        return $this->productos;
    }

    public function setProductos(?Productos $productos): static
    {
        $this->productos = $productos;

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

    /**
     * @return Collection<int, Terminos>
     */
    public function getTerminos(): Collection
    {
        return $this->terminos;
    }

    public function addTermino(Terminos $termino): static
    {
        if (!$this->terminos->contains($termino)) {
            $this->terminos->add($termino);
        }

        return $this;
    }

    public function removeTermino(Terminos $termino): static
    {
        $this->terminos->removeElement($termino);

        return $this;
    }

    public function getPrecio(): ?float
    {
        return $this->precio;
    }

    public function setPrecio(?float $precio): static
    {
        $this->precio = $precio;

        return $this;
    }

    public function getPrecioRebajado(): ?float
    {
        return $this->precio_rebajado;
    }

    public function setPrecioRebajado(?float $precio_rebajado): static
    {
        $this->precio_rebajado = $precio_rebajado;

        return $this;
    }

    public function getCantidad(): ?int
    {
        return $this->cantidad;
    }

    public function setCantidad(?int $cantidad): static
    {
        $this->cantidad = $cantidad;

        return $this;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): static
    {
        $this->sku = $sku;

        return $this;
    }

    /**
     * @return Collection<int, VariacionesGaleria>
     */
    public function getVariacionesGalerias(): Collection
    {
        return $this->variacionesGalerias;
    }

    public function addVariacionesGaleria(VariacionesGaleria $variacionesGaleria): static
    {
        if (!$this->variacionesGalerias->contains($variacionesGaleria)) {
            $this->variacionesGalerias->add($variacionesGaleria);
            $variacionesGaleria->setVariaciones($this);
        }

        return $this;
    }

    public function removeVariacionesGaleria(VariacionesGaleria $variacionesGaleria): static
    {
        if ($this->variacionesGalerias->removeElement($variacionesGaleria)) {
            // set the owning side to null (unless already changed)
            if ($variacionesGaleria->getVariaciones() === $this) {
                $variacionesGaleria->setVariaciones(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DetalleCarrito>
     */
    public function getDetalleCarritos(): Collection
    {
        return $this->detalleCarritos;
    }

    public function addDetalleCarrito(DetalleCarrito $detalleCarrito): static
    {
        if (!$this->detalleCarritos->contains($detalleCarrito)) {
            $this->detalleCarritos->add($detalleCarrito);
            $detalleCarrito->setIdVariacion($this);
        }

        return $this;
    }

    public function removeDetalleCarrito(DetalleCarrito $detalleCarrito): static
    {
        if ($this->detalleCarritos->removeElement($detalleCarrito)) {
            // set the owning side to null (unless already changed)
            if ($detalleCarrito->getIdVariacion() === $this) {
                $detalleCarrito->setIdVariacion(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DetallePedido>
     */
    public function getDetallePedidos(): Collection
    {
        return $this->detallePedidos;
    }

    public function addDetallePedido(DetallePedido $detallePedido): static
    {
        if (!$this->detallePedidos->contains($detallePedido)) {
            $this->detallePedidos->add($detallePedido);
            $detallePedido->setIdVariacion($this);
        }

        return $this;
    }

    public function removeDetallePedido(DetallePedido $detallePedido): static
    {
        if ($this->detallePedidos->removeElement($detallePedido)) {
            // set the owning side to null (unless already changed)
            if ($detallePedido->getIdVariacion() === $this) {
                $detallePedido->setIdVariacion(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Subastas>
     */
    public function getSubastas(): Collection
    {
        return $this->subastas;
    }

    public function addSubasta(Subastas $subasta): static
    {
        if (!$this->subastas->contains($subasta)) {
            $this->subastas->add($subasta);
            $subasta->setIdVariacion($this);
        }

        return $this;
    }

    public function removeSubasta(Subastas $subasta): static
    {
        if ($this->subastas->removeElement($subasta)) {
            // set the owning side to null (unless already changed)
            if ($subasta->getIdVariacion() === $this) {
                $subasta->setIdVariacion(null);
            }
        }

        return $this;
    }

    public function isDisponibilidadVariante(): ?bool
    {
        return $this->disponibilidad_variante;
    }

    public function setDisponibilidadVariante(?bool $disponibilidad_variante): static
    {
        $this->disponibilidad_variante = $disponibilidad_variante;

        return $this;
    }

    /**
     * @return Collection<int, Regateos>
     */
    public function getRegateos(): Collection
    {
        return $this->regateos;
    }

    public function addRegateo(Regateos $regateo): static
    {
        if (!$this->regateos->contains($regateo)) {
            $this->regateos->add($regateo);
            $regateo->setVariacion($this);
        }

        return $this;
    }

    public function removeRegateo(Regateos $regateo): static
    {
        if ($this->regateos->removeElement($regateo)) {
            // set the owning side to null (unless already changed)
            if ($regateo->getVariacion() === $this) {
                $regateo->setVariacion(null);
            }
        }

        return $this;
    }

    public function getCodigoVariante(): ?string
    {
        return $this->codigo_variante;
    }

    public function setCodigoVariante(?string $codigo_variante): static
    {
        $this->codigo_variante = $codigo_variante;

        return $this;
    }

    

}

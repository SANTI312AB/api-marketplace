<?php

namespace App\Entity;

use App\Repository\MetodosPagoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetodosPagoRepository::class)]
class MetodosPago
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDMETODOPAGO")]
    private ?int $id = null;

    #[ORM\Column(length: 255,name:"METODOPAGO")]
    private ?string $nombre = null;

    #[ORM\OneToMany(mappedBy: 'metodo_pago', targetEntity: Pedidos::class, orphanRemoval: true)]
    private Collection $pedidos;

    #[ORM\Column(nullable: true, name:"IS_ACTIVE")]
    private ?bool $activo = true;


    #[ORM\Column(length: 255, nullable: true,name:"DESCRIPCION")]
    private ?string $descripcion = null;

    

    public function __construct()
    {
        $this->pedidos = new ArrayCollection();
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

    /**
     * @return Collection<int, Pedidos>
     */
    public function getPedidos(): Collection
    {
        return $this->pedidos;
    }

    public function addPedido(Pedidos $pedido): static
    {
        if (!$this->pedidos->contains($pedido)) {
            $this->pedidos->add($pedido);
            $pedido->setMetodoPago($this);
        }

        return $this;
    }

    public function removePedido(Pedidos $pedido): static
    {
        if ($this->pedidos->removeElement($pedido)) {
            // set the owning side to null (unless already changed)
            if ($pedido->getMetodoPago() === $this) {
                $pedido->setMetodoPago(null);
            }
        }

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

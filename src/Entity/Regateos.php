<?php

namespace App\Entity;

use App\Repository\RegateosRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RegateosRepository::class)]
class Regateos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"ID_REGATEO")]
    private ?int $id = null;

    #[ORM\Column(name:"REGATEO")]
    private ?float $regateo = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE,name:"FECHA_REGISTRO")]
    private ?\DateTimeInterface $fecha = null;

    #[ORM\Column(length: 255,name:"ESTADO")]
    private ?string $estado = null;

    #[ORM\ManyToOne(inversedBy: 'regateos')]
    #[ORM\JoinColumn(nullable: false,name:"IDLOGIN", referencedColumnName: "IDLOGIN")]
    private ?Login $login = null;

    #[ORM\ManyToOne(inversedBy: 'regateos')]
    #[ORM\JoinColumn(nullable: false,referencedColumnName: "IDPRODUCTO",name:"IDPRODUCTO")]
    private ?Productos $producto = null;

    #[ORM\ManyToOne(inversedBy: 'regateos')]
    #[ORM\JoinColumn(nullable: true,name:"IDVARIACION", referencedColumnName: "IDVARIACION")]
    private ?Variaciones $variacion = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true,name:"FECHA_EDICION")]
    private ?\DateTimeInterface $fecha_edicion = null;

    /**
     * @var Collection<int, Pedidos>
     */
    #[ORM\OneToMany(mappedBy: 'regateo', targetEntity: Pedidos::class, orphanRemoval: true)]
    private Collection $pedidos;

    #[ORM\Column(length: 255, nullable: true, name:"N_REGATEO",unique:true)]
    private ?string $n_regateo = null;

    public function __construct()
    {
        $this->fecha = new \DateTime();
        $this->pedidos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRegateo(): ?float
    {
        return $this->regateo;
    }

    public function setRegateo(float $regateo): static
    {
        $this->regateo = $regateo;

        return $this;
    }

    public function getFecha(): ?\DateTimeInterface
    {
        return $this->fecha;
    }

    public function setFecha(\DateTimeInterface $fecha): static
    {
        $this->fecha = $fecha;

        return $this;
    }

    public function getEstado(): ?string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): static
    {
        $this->estado = $estado;

        return $this;
    }

    public function getLogin(): ?Login
    {
        return $this->login;
    }

    public function setLogin(?Login $login): static
    {
        $this->login = $login;

        return $this;
    }

    public function getProducto(): ?Productos
    {
        return $this->producto;
    }

    public function setProducto(?Productos $producto): static
    {
        $this->producto = $producto;

        return $this;
    }

    public function getVariacion(): ?Variaciones
    {
        return $this->variacion;
    }

    public function setVariacion(?Variaciones $variacion): static
    {
        $this->variacion = $variacion;

        return $this;
    }

    public function getFechaEdicion(): ?\DateTimeInterface
    {
        return $this->fecha_edicion;
    }

    public function setFechaEdicion(?\DateTimeInterface $fecha_edicion): static
    {
        $this->fecha_edicion = $fecha_edicion;

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
            $pedido->setRegateo($this);
        }

        return $this;
    }

    public function removePedido(Pedidos $pedido): static
    {
        if ($this->pedidos->removeElement($pedido)) {
            // set the owning side to null (unless already changed)
            if ($pedido->getRegateo() === $this) {
                $pedido->setRegateo(null);
            }
        }

        return $this;
    }

    public function getNRegateo(): ?string
    {
        return $this->n_regateo;
    }

    public function setNRegateo(?string $n_regateo): static
    {
        $this->n_regateo = $n_regateo;

        return $this;
    }
}

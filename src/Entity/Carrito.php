<?php

namespace App\Entity;

use App\Repository\CarritoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CarritoRepository::class)]
class Carrito
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDCARRITO")]
    private ?int $id = null;


    #[ORM\OneToMany(mappedBy: 'carrito', targetEntity: DetalleCarrito::class, orphanRemoval: true)]
    private Collection $detalleCarritos;

    #[ORM\ManyToOne(inversedBy: 'carritos')]
    #[ORM\JoinColumn(nullable: false,name:"IDLOGIN", referencedColumnName:"IDLOGIN")]
    private ?Login $login = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true,name:"FECHA")]
    private ?\DateTimeInterface $fecha = null;

    #[ORM\Column(nullable: true, name:"CARRITO_CONTADOR")]
    private ?int $contador = 0;

    

    public function __construct()
    {
        $this->detalleCarritos = new ArrayCollection();
    

    }

  
    public function getId(): ?int
    {
        return $this->id;
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
            $detalleCarrito->setCarrito($this);
        }

        return $this;
    }

    public function removeDetalleCarrito(DetalleCarrito $detalleCarrito): static
    {
        if ($this->detalleCarritos->removeElement($detalleCarrito)) {
            // set the owning side to null (unless already changed)
            if ($detalleCarrito->getCarrito() === $this) {
                $detalleCarrito->setCarrito(null);
            }
        }

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

    public function getFecha(): ?\DateTimeInterface
    {
        return $this->fecha;
    }

    public function setFecha(?\DateTimeInterface $fecha): static
    {
        $this->fecha = $fecha;

        return $this;
    }

    public function getContador(): ?int
    {
        return $this->contador;
    }

    public function setContador(?int $contador): static
    {
        $this->contador = $contador;

        return $this;
    }


}

<?php

namespace App\Entity;

use App\Repository\FacturaRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FacturaRepository::class)]
class Factura
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDFACTURA")]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true,name:"NOMBRE_CLIENTE")]
    private ?string $nombre = null;

    #[ORM\Column(length: 255, nullable: true,name:"APELLIDO_CLIENTE")]
    private ?string $apellido = null;

    #[ORM\Column(length: 255, nullable: true,name:"TELEFONO_CLIENTE")]
    private ?string $telefono = null;

    #[ORM\Column(length: 255, nullable: true,name:"EMAIL_CLIENTE")]
    private ?string $email = null;

    #[ORM\Column(length: 255,nullable:true,name:"DNI_CLIENTE")]
    private ?string $dni = null;


    #[ORM\ManyToOne(inversedBy: 'facturas')]
    #[ORM\JoinColumn(nullable: false,name:"IDLOGIN", referencedColumnName:"IDLOGIN")]
    private ?Login $login = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true,name:"FECHA_FACTURA")]
    private ?\DateTimeInterface $fecha = null;

    #[ORM\Column(nullable: true,name:"CONSUMIDOR_FINAL")]
    private ?bool $consumidor_final =false;

    #[ORM\OneToMany(mappedBy: 'factura', targetEntity: Pedidos::class, orphanRemoval: true)]
    private Collection $pedidos;

    

    public function __construct()
    {
        $this->fecha= new DateTime();
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

    public function setNombre(?string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getApellido(): ?string
    {
        return $this->apellido;
    }

    public function setApellido(?string $apellido): static
    {
        $this->apellido = $apellido;

        return $this;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function setTelefono(?string $telefono): static
    {
        $this->telefono = $telefono;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getDni(): ?string
    {
        return $this->dni;
    }

    public function setDni(string $dni): static
    {
        $this->dni = $dni;

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

    public function isConsumidorFinal(): ?bool
    {
        return $this->consumidor_final;
    }

    public function setConsumidorFinal(?bool $consumidor_final): static
    {
        $this->consumidor_final = $consumidor_final;

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
            $pedido->setFactura($this);
        }

        return $this;
    }

    public function removePedido(Pedidos $pedido): static
    {
        if ($this->pedidos->removeElement($pedido)) {
            // set the owning side to null (unless already changed)
            if ($pedido->getFactura() === $this) {
                $pedido->setFactura(null);
            }
        }

        return $this;
    }

    
}

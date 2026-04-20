<?php

namespace App\Entity;

use App\Repository\GananciaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GananciaRepository::class)]
class Ganancia
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDGANANCIA")]
    private ?int $id = null;

    #[ORM\Column(nullable: true,name:"GANANCIA")]
    private ?float $ganacia = null;

    #[ORM\OneToOne(inversedBy: 'ganancia', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false,name:"IDLOGIN",referencedColumnName:"IDLOGIN")]
    private ?Login $login = null;


    #[ORM\OneToMany(mappedBy: 'ganancia', targetEntity: Retiros::class, orphanRemoval: true)]
    private Collection $retiros;

    #[ORM\Column(nullable: true,name:"SALDO_DISPONIBLE")]
    private ?float $disponible = null;

    #[ORM\Column(nullable: true,name:"TOTAL_RETIRADO")]
    private ?float $total_retiros = null;

    #[ORM\Column(nullable: true,name:"TOTAL_RECIBIDO")]
    private ?float $total_recibir = null;

    #[ORM\Column(nullable: true,name:"TOTAL_COMISION")]
    private ?float $total_comision = null;

    

    public function __construct()
    {
        $this->retiros = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGanacia(): ?float
    {
        return $this->ganacia;
    }

    public function setGanacia(?float $ganacia): static
    {
        $this->ganacia = $ganacia;

        return $this;
    }

    public function getLogin(): ?Login
    {
        return $this->login;
    }

    public function setLogin(Login $login): static
    {
        $this->login = $login;

        return $this;
    }



    /**
     * @return Collection<int, Retiros>
     */
    public function getRetiros(): Collection
    {
        return $this->retiros;
    }

    public function addRetiro(Retiros $retiro): static
    {
        if (!$this->retiros->contains($retiro)) {
            $this->retiros->add($retiro);
            $retiro->setGanancia($this);
        }

        return $this;
    }

    public function removeRetiro(Retiros $retiro): static
    {
        if ($this->retiros->removeElement($retiro)) {
            // set the owning side to null (unless already changed)
            if ($retiro->getGanancia() === $this) {
                $retiro->setGanancia(null);
            }
        }

        return $this;
    }

    public function getDisponible(): ?float
    {
        return $this->disponible;
    }

    public function setDisponible(?float $disponible): static
    {
        $this->disponible = $disponible;

        return $this;
    }

    public function getTotalRetiros(): ?float
    {
        return $this->total_retiros;
    }

    public function setTotalRetiros(?float $total_retiros): static
    {
        $this->total_retiros = $total_retiros;

        return $this;
    }

    public function getTotalRecibir(): ?float
    {
        return $this->total_recibir;
    }

    public function setTotalRecibir(?float $total_recibir): static
    {
        $this->total_recibir = $total_recibir;

        return $this;
    }

    public function getTotalComision(): ?float
    {
        return $this->total_comision;
    }

    public function setTotalComision(?float $total_comision): static
    {
        $this->total_comision = $total_comision;

        return $this;
    }
}

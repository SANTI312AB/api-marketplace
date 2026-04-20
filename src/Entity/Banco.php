<?php

namespace App\Entity;

use App\Repository\BancoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BancoRepository::class)]
class Banco
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDBANCO")]
    private ?int $id = null;

    #[ORM\Column(length: 255,name:"NOMBRE_CUENTA")]
    private ?string $nombre_cuenta = null;

    #[ORM\Column(type:"bigint",name:"NUMERO_CUENTA")]
    private ?int $numero_cuenta = null;

    #[ORM\Column(length: 255,name:"TIPO_CUENTA")]
    private ?string $tipo_cuenta = null;

    #[ORM\Column(length: 255,name:"BANCO")]
    private ?string $banco = null;

    #[ORM\ManyToOne(inversedBy: 'bancos')]
    #[ORM\JoinColumn(nullable: false,name:"IDLOGIN",referencedColumnName:"IDLOGIN")]
    private ?Login $login = null;

    #[ORM\OneToMany(mappedBy: 'banco', targetEntity: Retiros::class, orphanRemoval: true)]
    private Collection $retiros;

    public function __construct()
    {
        $this->retiros = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombreCuenta(): ?string
    {
        return $this->nombre_cuenta;
    }

    public function setNombreCuenta(string $nombre_cuenta): static
    {
        $this->nombre_cuenta = $nombre_cuenta;

        return $this;
    }

    public function getNumeroCuenta(): ?int
    {
        return $this->numero_cuenta;
    }

    public function setNumeroCuenta(int $numero_cuenta): static
    {
        $this->numero_cuenta = $numero_cuenta;

        return $this;
    }

    public function getTipoCuenta(): ?string
    {
        return $this->tipo_cuenta;
    }

    public function setTipoCuenta(string $tipo_cuenta): static
    {
        $this->tipo_cuenta = $tipo_cuenta;

        return $this;
    }

    public function getBanco(): ?string
    {
        return $this->banco;
    }

    public function setBanco(string $banco): static
    {
        $this->banco = $banco;

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
            $retiro->setBanco($this);
        }

        return $this;
    }

    public function removeRetiro(Retiros $retiro): static
    {
        if ($this->retiros->removeElement($retiro)) {
            // set the owning side to null (unless already changed)
            if ($retiro->getBanco() === $this) {
                $retiro->setBanco(null);
            }
        }

        return $this;
    }


}

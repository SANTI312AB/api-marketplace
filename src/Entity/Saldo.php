<?php

namespace App\Entity;

use App\Repository\SaldoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SaldoRepository::class)]
class Saldo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"ID_SALDO")]
    private ?int $id = null;

    #[ORM\Column(nullable: true,name:"SALDO")]
    private ?float $saldo = null;

    /**
     * @var Collection<int, Recargas>
     */
    #[ORM\OneToMany(mappedBy: 'saldo', targetEntity: Recargas::class, orphanRemoval: true)]
    private Collection $recargas;

    #[ORM\OneToOne(inversedBy: 'saldo', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false,name:"IDLOGIN",referencedColumnName:"IDLOGIN")]
    private ?Login $login = null;

    public function __construct()
    {
        $this->recargas = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSaldo(): ?float
    {
        return $this->saldo;
    }

    public function setSaldo(?float $saldo): static
    {
        $this->saldo = $saldo;

        return $this;
    }

    

    /**
     * @return Collection<int, Recargas>
     */
    public function getRecargas(): Collection
    {
        return $this->recargas;
    }

    public function addRecarga(Recargas $recarga): static
    {
        if (!$this->recargas->contains($recarga)) {
            $this->recargas->add($recarga);
            $recarga->setSaldo($this);
        }

        return $this;
    }

    public function removeRecarga(Recargas $recarga): static
    {
        if ($this->recargas->removeElement($recarga)) {
            // set the owning side to null (unless already changed)
            if ($recarga->getSaldo() === $this) {
                $recarga->setSaldo(null);
            }
        }

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
}

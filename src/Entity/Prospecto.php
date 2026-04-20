<?php

namespace App\Entity;

use App\Repository\ProspectoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProspectoRepository::class)]
class Prospecto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDPROSPECTO")]
    private ?int $id = null;

    #[ORM\Column(length: 255, name:"EMAIL_PROSPECTO",unique:true)]
    private ?string $email = null;

    #[ORM\JoinTable(name: 'prospecto_cupon')]
    #[ORM\JoinColumn(name: 'IDPROSPECTO', referencedColumnName: 'IDPROSPECTO')]
    #[ORM\InverseJoinColumn(name: 'IDCUPON', referencedColumnName: 'IDCUPON')]
    #[ORM\ManyToMany(targetEntity: Cupon::class, inversedBy: 'prospectos')]
    private Collection $cupon;

    public function __construct()
    {
        $this->cupon = new ArrayCollection();
    }

    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return Collection<int, Cupon>
     */
    public function getCupon(): Collection
    {
        return $this->cupon;
    }

    public function addCupon(Cupon $cupon): static
    {
        if (!$this->cupon->contains($cupon)) {
            $this->cupon->add($cupon);
        }

        return $this;
    }

    public function removeCupon(Cupon $cupon): static
    {
        $this->cupon->removeElement($cupon);

        return $this;
    }
}

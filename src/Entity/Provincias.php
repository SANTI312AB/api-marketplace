<?php

namespace App\Entity;

use App\Repository\ProvinciasRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProvinciasRepository::class)]
class Provincias
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDPROVINCIA")]
    private ?int $id = null;

    #[ORM\Column(length: 255,name:"NOMBRE_PROVINCIA")]
    private ?string $provincia = null;

    #[ORM\OneToMany(mappedBy: 'provincia', targetEntity: Ciudades::class)]
    private Collection $ciudades;

    #[ORM\Column(length: 255, nullable: true, name:"REGION_PROVINCIA")]
    private ?string $Region = null;

    public function __construct()
    {
        $this->ciudades = new ArrayCollection();
    }


  
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProvincia(): ?string
    {
        return $this->provincia;
    }

    public function setProvincia(string $provincia): static
    {
        $this->provincia = $provincia;

        return $this;
    }

    /**
     * @return Collection<int, Ciudades>
     */
    public function getCiudades(): Collection
    {
        return $this->ciudades;
    }

    public function addCiudade(Ciudades $ciudade): static
    {
        if (!$this->ciudades->contains($ciudade)) {
            $this->ciudades->add($ciudade);
            $ciudade->setProvincia($this);
        }

        return $this;
    }

    public function removeCiudade(Ciudades $ciudade): static
    {
        if ($this->ciudades->removeElement($ciudade)) {
            // set the owning side to null (unless already changed)
            if ($ciudade->getProvincia() === $this) {
                $ciudade->setProvincia(null);
            }
        }

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->Region;
    }

    public function setRegion(?string $Region): static
    {
        $this->Region = $Region;

        return $this;
    }

 
}

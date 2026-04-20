<?php

namespace App\Entity;

use App\Repository\VirtualTourRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VirtualTourRepository::class)]
class VirtualTour
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDTOUR")]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true,name:"NOMBRE")]
    private ?string $nombre = null;

    #[ORM\ManyToOne(inversedBy: 'virtualTours')]
    #[ORM\JoinColumn(nullable: false, name:"IDTIENDA", referencedColumnName:"IDTIENDA")]
    private ?Tiendas $tienda = null;

    /**
     * @var Collection<int, Scenes>
     */
    #[ORM\OneToMany(mappedBy: 'virtualTour', targetEntity: Scenes::class, orphanRemoval: true)]
    private Collection $scenes;

    public function __construct()
    {
        $this->scenes = new ArrayCollection();
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

    public function getTienda(): ?Tiendas
    {
        return $this->tienda;
    }

    public function setTienda(?Tiendas $tienda): static
    {
        $this->tienda = $tienda;

        return $this;
    }

    /**
     * @return Collection<int, Scenes>
     */
    public function getScenes(): Collection
    {
        return $this->scenes;
    }

    public function addScene(Scenes $scene): static
    {
        if (!$this->scenes->contains($scene)) {
            $this->scenes->add($scene);
            $scene->setVirtualTour($this);
        }

        return $this;
    }

    public function removeScene(Scenes $scene): static
    {
        if ($this->scenes->removeElement($scene)) {
            // set the owning side to null (unless already changed)
            if ($scene->getVirtualTour() === $this) {
                $scene->setVirtualTour(null);
            }
        }

        return $this;
    }
}

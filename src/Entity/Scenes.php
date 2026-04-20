<?php

namespace App\Entity;

use App\Repository\ScenesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScenesRepository::class)]
class Scenes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDSCENE")]
    private ?int $id = null;

    #[ORM\Column(length: 255,name:"IMAGEN")]
    private ?string $imagePath = null;

    #[ORM\ManyToOne(inversedBy: 'scenes')]
    #[ORM\JoinColumn(nullable: false,name:"IDTOUR", referencedColumnName:"IDTOUR")]
    private ?VirtualTour $virtualTour = null;

    /**
     * @var Collection<int, Hotspot>
     */
    #[ORM\OneToMany(mappedBy: 'scene', targetEntity: Hotspot::class, orphanRemoval: true)]
    private Collection $hotspots;

    #[ORM\Column(length: 255, nullable: true,name:"NOMBRE")]
    private ?string $nombre = null;

    public function __construct()
    {
        $this->hotspots = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(string $imagePath): static
    {
        $this->imagePath = $imagePath;

        return $this;
    }

    public function getVirtualTour(): ?VirtualTour
    {
        return $this->virtualTour;
    }

    public function setVirtualTour(?VirtualTour $virtualTour): static
    {
        $this->virtualTour = $virtualTour;

        return $this;
    }

    /**
     * @return Collection<int, Hotspot>
     */
    public function getHotspots(): Collection
    {
        return $this->hotspots;
    }

    public function addHotspot(Hotspot $hotspot): static
    {
        if (!$this->hotspots->contains($hotspot)) {
            $this->hotspots->add($hotspot);
            $hotspot->setScene($this);
        }

        return $this;
    }

    public function removeHotspot(Hotspot $hotspot): static
    {
        if ($this->hotspots->removeElement($hotspot)) {
            // set the owning side to null (unless already changed)
            if ($hotspot->getScene() === $this) {
                $hotspot->setScene(null);
            }
        }

        return $this;
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
}

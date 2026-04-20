<?php

namespace App\Entity;

use App\Repository\HotspotRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HotspotRepository::class)]
class Hotspot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDHOTSPOT")]
    private ?int $id = null;

    #[ORM\Column(name:"CORDENADA_HORIZONTAL")]
    private ?float $yaw = null;

    #[ORM\Column(name:"CORDENADA_VERTICAL")]
    private ?float $pitch = null;


    #[ORM\ManyToOne(inversedBy: 'hotspots')]
    #[ORM\JoinColumn(nullable: false,name:"IDSCENE",referencedColumnName:"IDSCENE")]
    private ?Scenes $scene = null;

    #[ORM\Column(length: 255, nullable: true,name:"TIPO")]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true,name:"TEXTO")]
    private ?string $text = null;

    #[ORM\Column(length: 255, nullable: true,name:"URL")]
    private ?string $url = null;

    #[ORM\Column(length: 500, nullable: true,name:"SLUG_PRODUCTO")]
    private ?string $slug_producto = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getYaw(): ?float
    {
        return $this->yaw;
    }

    public function setYaw(float $yaw): static
    {
        $this->yaw = $yaw;

        return $this;
    }

    public function getPitch(): ?float
    {
        return $this->pitch;
    }

    public function setPitch(float $pitch): static
    {
        $this->pitch = $pitch;

        return $this;
    }


    public function getScene(): ?Scenes
    {
        return $this->scene;
    }

    public function setScene(?Scenes $scene): static
    {
        $this->scene = $scene;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getSlugProducto(): ?string
    {
        return $this->slug_producto;
    }

    public function setSlugProducto(?string $slug_producto): static
    {
        $this->slug_producto = $slug_producto;

        return $this;
    }
}

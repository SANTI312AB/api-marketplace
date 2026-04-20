<?php

namespace App\Entity;

use App\Repository\ShopbyInfoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShopbyInfoRepository::class)]
class ShopbyInfo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"ID_INFO")]
    private ?int $id = null;

    #[ORM\Column(length: 255,name:"NOMBRE")]
    private ?string $nombre = null;

    #[ORM\Column(length: 500, nullable: true,name:"DESCRIPCION")]
    private ?string $descripcion = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): static
    {
        $this->descripcion = $descripcion;

        return $this;
    }
}

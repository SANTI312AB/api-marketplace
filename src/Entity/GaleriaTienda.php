<?php

namespace App\Entity;

use App\Repository\GaleriaTiendaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GaleriaTiendaRepository::class)]
class GaleriaTienda
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDGALERIA_TIENDA")]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true,name:"URL_IMAGEN")]
    private ?string $url = null;

    #[ORM\Column(nullable:true,name:"PRIORIDAD")]
    private ?int $prioridad = null;

    #[ORM\Column(length: 255, nullable: true,name:"SECCION_PAGINA")]
    private ?string $seccion = null;

    #[ORM\ManyToOne(inversedBy: 'galeriaTiendas')]
    #[ORM\JoinColumn(nullable: false,name:"IDTIENDA",referencedColumnName:"IDTIENDA")]
    private ?Tiendas $tienda = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPrioridad(): ?int
    {
        return $this->prioridad;
    }

    public function setPrioridad(int $prioridad): static
    {
        $this->prioridad = $prioridad;

        return $this;
    }

    public function getSeccion(): ?string
    {
        return $this->seccion;
    }

    public function setSeccion(?string $seccion): static
    {
        $this->seccion = $seccion;

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
}

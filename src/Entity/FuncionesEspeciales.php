<?php

namespace App\Entity;

use App\Repository\FuncionesEspecialesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FuncionesEspecialesRepository::class)]
class FuncionesEspeciales
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDFUNCION")]
    private ?int $id = null;

    #[ORM\Column(length: 255,name:"NOMBRE_FUNCION")]
    private ?string $nombre = null;

    #[ORM\Column(nullable: true,name:"ACTIVO")]
    private ?bool $activo = null;

    #[ORM\Column(length: 255, nullable: true, name:"DESCRIPCION")]
    private ?string $descripcion = null;

    #[ORM\Column(length: 255, nullable: true, name:"TIPO")]
    private ?string $tipo = null;

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

    public function isActivo(): ?bool
    {
        return $this->activo;
    }

    public function setActivo(?bool $activo): static
    {
        $this->activo = $activo;

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

    public function getTipo(): ?string
    {
        return $this->tipo;
    }

    public function setTipo(?string $tipo): static
    {
        $this->tipo = $tipo;

        return $this;
    }
}

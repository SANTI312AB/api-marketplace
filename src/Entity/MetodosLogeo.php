<?php

namespace App\Entity;

use App\Repository\MetodosLogeoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetodosLogeoRepository::class)]
class MetodosLogeo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:'ID_METODO_LOGEO')]
    private ?int $id = null;

    #[ORM\Column(length: 255,name:'NOMBRE_METODO_LOGEO')]
    private ?string $nombre = null;

    #[ORM\Column(name:'HABILITADO_METODO_LOGEO', options: ['default' => true])]
    private ?bool $enable = null;

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

    public function isEnable(): ?bool
    {
        return $this->enable;
    }

    public function setEnable(bool $enable): static
    {
        $this->enable = $enable;

        return $this;
    }
}

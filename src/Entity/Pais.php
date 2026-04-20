<?php

namespace App\Entity;

use App\Repository\PaisRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaisRepository::class)]
class Pais
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDPAIS")]
    private ?int $id = null;

    #[ORM\Column(length: 45,name:"NOMBRE_PAIS")]
    private ?string $nombre = null;

    #[ORM\Column(length: 4,name:"COD_PAIS")]
    private ?string $codigo = null;

    #[ORM\Column(nullable: true,name:"COD_AREA")]
    private ?int $c_area = null;

    #[ORM\Column(length: 5, nullable: true,name:"LANG")]
    private ?string $lang = null;

    #[ORM\OneToMany(mappedBy: 'pais', targetEntity: Usuarios::class, orphanRemoval: true)]
    private Collection $usuarios;

    public function __construct()
    {
        $this->usuarios = new ArrayCollection();
    }

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

    public function getCodigo(): ?string
    {
        return $this->codigo;
    }

    public function setCodigo(string $codigo): static
    {
        $this->codigo = $codigo;

        return $this;
    }

    public function getCArea(): ?int
    {
        return $this->c_area;
    }

    public function setCArea(?int $c_area): static
    {
        $this->c_area = $c_area;

        return $this;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }

    public function setLang(?string $lang): static
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * @return Collection<int, Usuarios>
     */
    public function getUsuarios(): Collection
    {
        return $this->usuarios;
    }

    public function addUsuario(Usuarios $usuario): static
    {
        if (!$this->usuarios->contains($usuario)) {
            $this->usuarios->add($usuario);
            $usuario->setPais($this);
        }

        return $this;
    }

    public function removeUsuario(Usuarios $usuario): static
    {
        if ($this->usuarios->removeElement($usuario)) {
            // set the owning side to null (unless already changed)
            if ($usuario->getPais() === $this) {
                $usuario->setPais(null);
            }
        }

        return $this;
    }
}

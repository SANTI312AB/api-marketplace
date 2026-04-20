<?php

namespace App\Entity;

use App\Repository\AtributosRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AtributosRepository::class)]
class Atributos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDATRIBUTO")]
    private ?int $id = null;

    #[ORM\Column(length: 255, name:"NOMBRE_ATRIBUTO", unique: true)]
    private ?string $nombre = null;

    #[ORM\OneToMany(mappedBy: 'atributos', targetEntity: Terminos::class, orphanRemoval: true)]
    private Collection $terminos;

    #[ORM\Column(nullable: true,name:"FECHA_CREACION")]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true,name:"FECHA_EDICION")]
    private ?\DateTimeImmutable $updated_at = null;

    public function __construct()
    {
        $this->terminos = new ArrayCollection();
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

    /**
     * @return Collection<int, Terminos>
     */
    public function getTerminos(): Collection
    {
        return $this->terminos;
    }

    public function addTermino(Terminos $termino): static
    {
        if (!$this->terminos->contains($termino)) {
            $this->terminos->add($termino);
            $termino->setAtributos($this);
        }

        return $this;
    }

    public function removeTermino(Terminos $termino): static
    {
        if ($this->terminos->removeElement($termino)) {
            // set the owning side to null (unless already changed)
            if ($termino->getAtributos() === $this) {
                $termino->setAtributos(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

   

}

<?php

namespace App\Entity;

use App\Repository\TerminosRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TerminosRepository::class)]
class Terminos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDTERMINOS")]
    private ?int $id = null;

    #[ORM\Column(length: 255,name:"NOMBRE_TERMINO")]
    private ?string $nombre = null;

    #[ORM\Column(length: 255, nullable: true,name:"CODIGO_TERMINO")]
    private ?string $codigo = null;

    #[ORM\ManyToOne(inversedBy: 'terminos')]
    #[ORM\JoinColumn(nullable: false,name:"IDATRIBUTO", referencedColumnName:"IDATRIBUTO")]
    private ?Atributos $atributos = null;
    
    #[ORM\JoinTable(name: 'variaciones_terminos')]
    #[ORM\JoinColumn(name: 'IDTERMINOS', referencedColumnName: 'IDTERMINOS')]
    #[ORM\InverseJoinColumn(name: 'IDVARIACION', referencedColumnName: 'IDVARIACION')]
    #[ORM\ManyToMany(targetEntity: Variaciones::class, mappedBy: 'terminos')]
    private Collection $variaciones;

    #[ORM\Column(nullable: true,name:"FECHA_CREACION")]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true,name:"FECHA_EDICION")]
    private ?\DateTimeImmutable $updated_at = null;

    public function __construct()
    {
        $this->variaciones = new ArrayCollection();
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

    public function setCodigo(?string $codigo): static
    {
        $this->codigo = $codigo;

        return $this;
    }

    public function getAtributos(): ?Atributos
    {
        return $this->atributos;
    }

    public function setAtributos(?Atributos $atributos): static
    {
        $this->atributos = $atributos;

        return $this;
    }

    /**
     * @return Collection<int, Variaciones>
     */
    public function getVariaciones(): Collection
    {
        return $this->variaciones;
    }

    public function addVariacione(Variaciones $variacione): static
    {
        if (!$this->variaciones->contains($variacione)) {
            $this->variaciones->add($variacione);
            $variacione->addTermino($this);
        }

        return $this;
    }

    public function removeVariacione(Variaciones $variacione): static
    {
        if ($this->variaciones->removeElement($variacione)) {
            $variacione->removeTermino($this);
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

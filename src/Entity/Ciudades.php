<?php

namespace App\Entity;

use App\Repository\CiudadesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CiudadesRepository::class)]
class Ciudades
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDCIUDAD")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'ciudades')]
    #[ORM\JoinColumn(nullable:true ,name:"IDPROVINCIA",referencedColumnName:"IDPROVINCIA")]
    private ?Provincias $provincia = null;
    

    #[ORM\Column(length: 255,name:"NOMBRE_CIUDAD")]
    private ?string $ciudad = null;

    

    #[ORM\OneToMany(mappedBy: 'ciudad', targetEntity: UsuariosDirecciones::class, orphanRemoval: true)]
    private Collection $usuariosDirecciones;

    #[ORM\Column(nullable: true,name:"ID_SERVIENTREGA")]
    private ?int $id_servientrega = null;

    #[ORM\Column(nullable: true, name:"FREE_CITY")]
    private ?bool $free = null;

    /**
     * @var Collection<int, Productos>
     */
    #[ORM\OneToMany(mappedBy: 'ciudad_servicio', targetEntity: Productos::class, orphanRemoval: true)]
    private Collection $productos;



    public function __construct()
    {
        $this->usuariosDirecciones = new ArrayCollection();
        $this->productos = new ArrayCollection();
        
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCiudad(): ?string
    {
        return $this->ciudad;
    }

    public function setCiudad(string $ciudad): static
    {
        $this->ciudad = $ciudad;

        return $this;
    }

    /**
     * @return Collection<int, UsuariosDirecciones>
     */
    public function getUsuariosDirecciones(): Collection
    {
        return $this->usuariosDirecciones;
    }

    public function addUsuariosDireccione(UsuariosDirecciones $usuariosDireccione): static
    {
        if (!$this->usuariosDirecciones->contains($usuariosDireccione)) {
            $this->usuariosDirecciones->add($usuariosDireccione);
            $usuariosDireccione->setCiudad($this);
        }

        return $this;
    }

    public function removeUsuariosDireccione(UsuariosDirecciones $usuariosDireccione): static
    {
        if ($this->usuariosDirecciones->removeElement($usuariosDireccione)) {
            // set the owning side to null (unless already changed)
            if ($usuariosDireccione->getCiudad() === $this) {
                $usuariosDireccione->setCiudad(null);
            }
        }

        return $this;
    }

    public function getProvincia(): ?Provincias
    {
        return $this->provincia;
    }

    public function setProvincia(?Provincias $provincia): static
    {
        $this->provincia = $provincia;

        return $this;
    }

    public function getIdServientrega(): ?int
    {
        return $this->id_servientrega;
    }

    public function setIdServientrega(?int $id_servientrega): static
    {
        $this->id_servientrega = $id_servientrega;

        return $this;
    }

    public function isFree(): ?bool
    {
        return $this->free;
    }

    public function setFree(?bool $free): static
    {
        $this->free = $free;

        return $this;
    }

    /**
     * @return Collection<int, Productos>
     */
    public function getProductos(): Collection
    {
        return $this->productos;
    }

    public function addProducto(Productos $producto): static
    {
        if (!$this->productos->contains($producto)) {
            $this->productos->add($producto);
            $producto->setCiudadServicio($this);
        }

        return $this;
    }

    public function removeProducto(Productos $producto): static
    {
        if ($this->productos->removeElement($producto)) {
            // set the owning side to null (unless already changed)
            if ($producto->getCiudadServicio() === $this) {
                $producto->setCiudadServicio(null);
            }
        }

        return $this;
    }

    

    
}

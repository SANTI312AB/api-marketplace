<?php

namespace App\Entity;

use App\Repository\UsuariosDireccionesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Nullable;

#[ORM\Entity(repositoryClass: UsuariosDireccionesRepository::class)]
class UsuariosDirecciones
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDUSUARIOS_DIRECCIONES")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'usuariosDirecciones')]
    #[ORM\JoinColumn(nullable: false,name:"IDUSUARIO",referencedColumnName:"IDUSUARIO")]
    private ?Usuarios $usuario = null;

    #[ORM\ManyToOne(inversedBy: 'usuariosDirecciones')]
    #[ORM\JoinColumn(nullable: false,name:"IDCIUDAD", referencedColumnName:"IDCIUDAD")]
    private ?Ciudades $ciudad = null;

    #[ORM\Column(length: 255,nullable:true,name:"DIRECCION_P")]
    private ?string $direccion_p =null;

    #[ORM\Column(length: 255, nullable: true,name:"DIRECCION_S")]
    private ?string $direccion_s = null;

    #[ORM\Column(length: 255,nullable:true,name:"CODIGO_POSTAL")]
    private ?int $codigo_postal = null;

    #[ORM\Column(length: 60, nullable: true,name:"ETIQUETA_DIRECCION")]
    private ?string $etiqueta_direccion = null;

    #[ORM\Column(length: 200, nullable: true,name:"REFERENCIA_DIRECCION")]
    private ?string $referencia_direccion = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true,name:"FECHA_DIRECCION")]
    private ?\DateTimeInterface $fecha_creacion = null;



    #[ORM\Column(nullable: true,name:"DEFAULT_DIRECCION")]
    private ?int $default_direccion = null;

   

    #[ORM\Column(length: 255, nullable: true,name:"NUMERO_CASA")]
    private ?string $n_casa = null;

    #[ORM\OneToMany(mappedBy: 'direcciones', targetEntity: Productos::class)]
    private Collection $productos;

    #[ORM\ManyToOne(inversedBy: 'usuariosDirecciones')]
    #[ORM\JoinColumn(nullable: false, name:"IDESTADO", referencedColumnName:"IDESTADO")]
    private ?Estados $estado = null;

    #[ORM\Column(length: 255, nullable: true,name:"LATITUD")]
    private ?float $latitud = null;

    #[ORM\Column(length: 255, nullable: true,name:"LONGITUD")]
    private ?float $longitud = null;

    #[ORM\Column(length: 300, nullable: true,name:"OBSERVACION_DIRECCION")]
    private ?string $observacion = null;

    public function __construct()
    {
        
        $this->fecha_creacion = new \DateTime();
        $this->productos = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDireccionP(): ?string
    {
        return $this->direccion_p;
    }

    public function setDireccionP(?string $direccion_p): static
    {
        $this->direccion_p = $direccion_p;

        return $this;
    }

    public function getDireccionS(): ?string
    {
        return $this->direccion_s;
    }

    public function setDireccionS(?string $direccion_s): static
    {
        $this->direccion_s = $direccion_s;

        return $this;
    }

    public function getCodigoPostal(): ?int
    {
        return $this->codigo_postal;
    }

    public function setCodigoPostal(?int $codigo_postal): static
    {
        $this->codigo_postal = $codigo_postal;

        return $this;
    }

    public function getEtiquetaDireccion(): ?string
    {
        return $this->etiqueta_direccion;
    }

    public function setEtiquetaDireccion(?string $etiqueta_direccion): static
    {
        $this->etiqueta_direccion = $etiqueta_direccion;

        return $this;
    }

    public function getReferenciaDireccion(): ?string
    {
        return $this->referencia_direccion;
    }

    public function setReferenciaDireccion(?string $referencia_direccion): static
    {
        $this->referencia_direccion = $referencia_direccion;

        return $this;
    }

    public function getFechaCreacion(): ?\DateTimeInterface
    {
        return $this->fecha_creacion;
    }

    public function setFechaCreacion(?\DateTimeInterface $fecha_creacion): static
    {
        $this->fecha_creacion = $fecha_creacion;

        return $this;
    }

  

    public function getDefaultDireccion(): ?int
    {
        return $this->default_direccion;
    }

    public function setDefaultDireccion(?int $default_direccion): static
    {
        $this->default_direccion = $default_direccion;

        return $this;
    }

    public function getUsuario(): ?Usuarios
    {
        return $this->usuario;
    }

    public function setUsuario(?Usuarios $usuario): static
    {
        $this->usuario = $usuario;

        return $this;
    }

    public function getCiudad(): ?Ciudades
    {
        return $this->ciudad;
    }

    public function setCiudad(?Ciudades $ciudad): static
    {
        $this->ciudad = $ciudad;

        return $this;
    }

    public function getNCasa(): ?string
    {
        return $this->n_casa;
    }

    public function setNCasa(?string $n_casa): static
    {
        $this->n_casa = $n_casa;

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
            $producto->setDirecciones($this);
        }

        return $this;
    }

    public function removeProducto(Productos $producto): static
    {
        if ($this->productos->removeElement($producto)) {
            // set the owning side to null (unless already changed)
            if ($producto->getDirecciones() === $this) {
                $producto->setDirecciones(null);
            }
        }

        return $this;
    }

    public function getEstado(): ?Estados
    {
        return $this->estado;
    }

    public function setEstado(?Estados $estado): static
    {
        $this->estado = $estado;

        return $this;
    }

    public function getLatitud(): ?float
    {
        return $this->latitud;
    }

    public function setLatitud(?float $latitud): static
    {
        $this->latitud = $latitud;

        return $this;
    }

    public function getLongitud(): ?float
    {
        return $this->longitud;
    }

    public function setLongitud(?float $longitud): static
    {
        $this->longitud = $longitud;

        return $this;
    }

    public function getObservacion(): ?string
    {
        return $this->observacion;
    }

    public function setObservacion(?string $observacion): static
    {
        $this->observacion = $observacion;

        return $this;
    }
}

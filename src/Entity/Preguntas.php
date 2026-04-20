<?php

namespace App\Entity;

use App\Repository\PreguntasRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PreguntasRepository::class)]
class Preguntas
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDPREGUNTA")]
    private ?int $id = null;

    #[ORM\Column(length: 300, nullable: true,name:"PREGUNTA")]
    private ?string $pregunta = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE,name:"FECHA_PREGUNTA")]
    private ?\DateTimeInterface $fecha = null;

    #[ORM\ManyToOne(inversedBy: 'preguntas')]
    #[ORM\JoinColumn(nullable: false,name:"IDLOGIN",referencedColumnName:"IDLOGIN")]
    private ?Login $login = null;

    #[ORM\ManyToOne(inversedBy: 'preguntas')]
    #[ORM\JoinColumn(nullable: false,name:"IDPRODUCTO",referencedColumnName:"IDPRODUCTO")]
    private ?Productos $producto = null;

    #[ORM\OneToMany(mappedBy: 'pregunta', targetEntity: Respuestas::class, orphanRemoval: true)]
    private Collection $respuestas;

    public function __construct()
    {
        $this->fecha= new DateTime();
        $this->respuestas = new ArrayCollection();
        $this->fecha = new DateTime();
 
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPregunta(): ?string
    {
        return $this->pregunta;
    }

    public function setPregunta(?string $pregunta): static
    {
        $this->pregunta = $pregunta;

        return $this;
    }

    public function getFecha(): ?\DateTimeInterface
    {
        return $this->fecha;
    }

    public function setFecha(\DateTimeInterface $fecha): static
    {
        $this->fecha = $fecha;

        return $this;
    }

    public function getLogin(): ?Login
    {
        return $this->login;
    }

    public function setLogin(?Login $login): static
    {
        $this->login = $login;

        return $this;
    }

    public function getProducto(): ?Productos
    {
        return $this->producto;
    }

    public function setProducto(?Productos $producto): static
    {
        $this->producto = $producto;

        return $this;
    }

    /**
     * @return Collection<int, Respuestas>
     */
    public function getRespuestas(): Collection
    {
        return $this->respuestas;
    }

    public function addRespuesta(Respuestas $respuesta): static
    {
        if (!$this->respuestas->contains($respuesta)) {
            $this->respuestas->add($respuesta);
            $respuesta->setPregunta($this);
        }

        return $this;
    }

    public function removeRespuesta(Respuestas $respuesta): static
    {
        if ($this->respuestas->removeElement($respuesta)) {
            // set the owning side to null (unless already changed)
            if ($respuesta->getPregunta() === $this) {
                $respuesta->setPregunta(null);
            }
        }

        return $this;
    }
}

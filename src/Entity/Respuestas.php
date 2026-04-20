<?php

namespace App\Entity;

use App\Repository\RespuestasRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RespuestasRepository::class)]
class Respuestas
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDRESPUESTA")]
    private ?int $id = null;

    #[ORM\Column(length: 300,name:"RESPUESTA")]
    private ?string $respuesta = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE,name:"FECHA_RESPUESTA")]
    private ?\DateTimeInterface $fecha = null;

    #[ORM\ManyToOne(inversedBy: 'respuestas')]
    #[ORM\JoinColumn(nullable: false,name:"IDLOGIN",referencedColumnName:"IDLOGIN")]
    private ?login $login = null;

    #[ORM\ManyToOne(inversedBy: 'respuestas')]
    #[ORM\JoinColumn(nullable: false,name:"IDPREGUNTA",referencedColumnName:"IDPREGUNTA")]
    private ?Preguntas $pregunta = null;


    public function __construct(){
        $this->fecha = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRespuesta(): ?string
    {
        return $this->respuesta;
    }

    public function setRespuesta(string $respuesta): static
    {
        $this->respuesta = $respuesta;

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

    public function getLogin(): ?login
    {
        return $this->login;
    }

    public function setLogin(?login $login): static
    {
        $this->login = $login;

        return $this;
    }

    public function getPregunta(): ?Preguntas
    {
        return $this->pregunta;
    }

    public function setPregunta(?Preguntas $pregunta): static
    {
        $this->pregunta = $pregunta;

        return $this;
    }
}

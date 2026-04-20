<?php

namespace App\Entity;

use App\Repository\ImpuestosRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImpuestosRepository::class)]
class Impuestos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDIMPUESTO")]
    private ?int $id = null;

    #[ORM\Column(name:"IMPUESTO")]
    private ?float $iva = null;

    #[ORM\Column(length: 255, nullable: true, name:"NOMBRE_IMPUESTO")]
    private ?string $nombre = null;



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIva(): ?float
    {
        return $this->iva;
    }

    public function setIva(float $iva): static
    {
        $this->iva = $iva;

        return $this;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(?string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

   
}

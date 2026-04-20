<?php

namespace App\Entity;

use App\Repository\VariacionesGaleriaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VariacionesGaleriaRepository::class)]
class VariacionesGaleria
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDVARIACION_GALERIA")]
    private ?int $id = null;

    #[ORM\Column(length: 500, nullable: true,name:"URL_VARIACION_GALERIA")]
    private ?string $url_variacion = null;

    #[ORM\ManyToOne(inversedBy: 'variacionesGalerias')]
    #[ORM\JoinColumn(nullable: false,name:"IDVARIACION", referencedColumnName:"IDVARIACION")]
    private ?Variaciones $variaciones = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrlVariacion(): ?string
    {
        return $this->url_variacion;
    }

    public function setUrlVariacion(?string $url_variacion): static
    {
        $this->url_variacion = $url_variacion;

        return $this;
    }

    public function getVariaciones(): ?Variaciones
    {
        return $this->variaciones;
    }

    public function setVariaciones(?Variaciones $variaciones): static
    {
        $this->variaciones = $variaciones;

        return $this;
    }
}

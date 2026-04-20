<?php

namespace App\Entity;

use App\Repository\TarifasServientregaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TarifasServientregaRepository::class)]
class TarifasServientrega
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDTARIFAS_SERVIENTREGA")]
    private ?int $id = null;

    #[ORM\Column(length: 255, name:"TRAYECTOS_SERVIENTREGAS")]
    private ?string $trayectos = null;

    #[ORM\Column(name:"TARIFAS_SERVIENTREGA")]
    private ?float $tarifas = null;

    #[ORM\Column(nullable: true,name:"TARIFA_DOS_KILOS")]
    private ?float $dos_kilos = null;

    #[ORM\Column(nullable: true,name:"TARIFA_KILO_ADICIONAL")]
    private ?float $kilo_adicional = null;

    #[ORM\ManyToOne(inversedBy: 'tarifasServientregas')]
    #[ORM\JoinColumn(nullable: true, name:"ID_METODOENVIO",referencedColumnName:"ID_METODOENVIO")]
    private ?MetodosEnvio $metodo_envio = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTrayectos(): ?string
    {
        return $this->trayectos;
    }

    public function setTrayectos(string $trayectos): static
    {
        $this->trayectos = $trayectos;

        return $this;
    }

    public function getTarifas(): ?float
    {
        return $this->tarifas;
    }

    public function setTarifas(float $tarifas): static
    {
        $this->tarifas = $tarifas;

        return $this;
    }

    public function getDosKilos(): ?float
    {
        return $this->dos_kilos;
    }

    public function setDosKilos(?float $dos_kilos): static
    {
        $this->dos_kilos = $dos_kilos;

        return $this;
    }

    public function getKiloAdicional(): ?float
    {
        return $this->kilo_adicional;
    }

    public function setKiloAdicional(?float $kilo_adicional): static
    {
        $this->kilo_adicional = $kilo_adicional;

        return $this;
    }

    public function getMetodoEnvio(): ?MetodosEnvio
    {
        return $this->metodo_envio;
    }

    public function setMetodoEnvio(?MetodosEnvio $metodo_envio): static
    {
        $this->metodo_envio = $metodo_envio;

        return $this;
    }


}

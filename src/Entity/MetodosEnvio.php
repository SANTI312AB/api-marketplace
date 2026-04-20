<?php

namespace App\Entity;

use App\Repository\MetodosEnvioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetodosEnvioRepository::class)]
class MetodosEnvio
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"ID_METODOENVIO")]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true,name:"METODO_ENVIO")]
    private ?string $nombre = null;

    #[ORM\Column(nullable: true,name:"ACTIVO")]
    private ?bool $activo = null;

    #[ORM\OneToMany(mappedBy: 'metodo_envio', targetEntity: TarifasServientrega::class, orphanRemoval: true)]
    private Collection $tarifasServientregas;

    #[ORM\OneToMany(mappedBy: 'metodo_envio', targetEntity: Pedidos::class, orphanRemoval: true)]
    private Collection $pedidos;

    #[ORM\Column(length: 255, nullable: true,name:"CONTACTO_ENVIO")]
    private ?string $contacto_envio = null;

    /**
     * @var Collection<int, Servientrega>
     */
    #[ORM\OneToMany(mappedBy: 'metodo_envio', targetEntity: Servientrega::class, orphanRemoval: true)]
    private Collection $servientregas;

    #[ORM\Column(length: 255, nullable: true,name:"DESCRIPCION")]
    private ?string $descripcion = null;

    public function __construct()
    {
        $this->tarifasServientregas = new ArrayCollection();
        $this->pedidos = new ArrayCollection();
        $this->servientregas = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function isActivo(): ?bool
    {
        return $this->activo;
    }

    public function setActivo(?bool $activo): static
    {
        $this->activo = $activo;

        return $this;
    }

    /**
     * @return Collection<int, TarifasServientrega>
     */
    public function getTarifasServientregas(): Collection
    {
        return $this->tarifasServientregas;
    }

    public function addTarifasServientrega(TarifasServientrega $tarifasServientrega): static
    {
        if (!$this->tarifasServientregas->contains($tarifasServientrega)) {
            $this->tarifasServientregas->add($tarifasServientrega);
            $tarifasServientrega->setMetodoEnvio($this);
        }

        return $this;
    }

    public function removeTarifasServientrega(TarifasServientrega $tarifasServientrega): static
    {
        if ($this->tarifasServientregas->removeElement($tarifasServientrega)) {
            // set the owning side to null (unless already changed)
            if ($tarifasServientrega->getMetodoEnvio() === $this) {
                $tarifasServientrega->setMetodoEnvio(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Pedidos>
     */
    public function getPedidos(): Collection
    {
        return $this->pedidos;
    }

    public function addPedido(Pedidos $pedido): static
    {
        if (!$this->pedidos->contains($pedido)) {
            $this->pedidos->add($pedido);
            $pedido->setMetodoEnvio($this);
        }

        return $this;
    }

    public function removePedido(Pedidos $pedido): static
    {
        if ($this->pedidos->removeElement($pedido)) {
            // set the owning side to null (unless already changed)
            if ($pedido->getMetodoEnvio() === $this) {
                $pedido->setMetodoEnvio(null);
            }
        }

        return $this;
    }

    public function getContactoEnvio(): ?string
    {
        return $this->contacto_envio;
    }

    public function setContactoEnvio(?string $contacto_envio): static
    {
        $this->contacto_envio = $contacto_envio;

        return $this;
    }

    /**
     * @return Collection<int, Servientrega>
     */
    public function getServientregas(): Collection
    {
        return $this->servientregas;
    }

    public function addServientrega(Servientrega $servientrega): static
    {
        if (!$this->servientregas->contains($servientrega)) {
            $this->servientregas->add($servientrega);
            $servientrega->setMetodoEnvio($this);
        }

        return $this;
    }

    public function removeServientrega(Servientrega $servientrega): static
    {
        if ($this->servientregas->removeElement($servientrega)) {
            // set the owning side to null (unless already changed)
            if ($servientrega->getMetodoEnvio() === $this) {
                $servientrega->setMetodoEnvio(null);
            }
        }

        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): static
    {
        $this->descripcion = $descripcion;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\RetirosRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RetirosRepository::class)]
class Retiros
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDRETIRO")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'retiros')]
    #[ORM\JoinColumn(nullable: false,name:"IDGANANCIA",referencedColumnName:"IDGANANCIA")]
    private ?Ganancia $ganancia = null;

    #[ORM\Column(name:"VALOR_RETIRO")]
    private ?float $retiro = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE,name:"FECHA_RETIRO")]
    private ?\DateTimeInterface $fecha = null;

    #[ORM\ManyToOne(inversedBy: 'retiros')]
    #[ORM\JoinColumn(nullable: true, name:"IDBANCO",referencedColumnName:"IDBANCO")]
    private ?Banco $banco = null;

    #[ORM\Column(length: 255, nullable: true,name:"ESTADO_RETIRO")]
    private ?string $estado = null;

    #[ORM\Column(nullable: true,name:"COMISION_SHOPBY")]
    private ?float $comision_shopby = null;

    #[ORM\Column(nullable: true,name:"VALOR_RETIRO_FINAL")]
    private ?float $retiro_final = null;

    #[ORM\Column(length: 255, nullable: true,name:"COMENTARIO")]
    private ?string $comentario = null;

    #[ORM\Column(length: 255, nullable: true,name:"COMPROBANTE_PAGO")]
    private ?string $comprobante = null;

    /**
     * @var Collection<int, Recargas>
     */
    #[ORM\OneToMany(mappedBy: 'retiro', targetEntity: Recargas::class, orphanRemoval: true)]
    private Collection $recargas;

    public function __construct()
    {
        $this->recargas = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGanancia(): ?Ganancia
    {
        return $this->ganancia;
    }

    public function setGanancia(?Ganancia $ganancia): static
    {
        $this->ganancia = $ganancia;

        return $this;
    }

    public function getRetiro(): ?float
    {
        return $this->retiro;
    }

    public function setRetiro(float $retiro): static
    {
        $this->retiro = $retiro;

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

    public function getBanco(): ?Banco
    {
        return $this->banco;
    }

    public function setBanco(?Banco $banco): static
    {
        $this->banco = $banco;

        return $this;
    }

    public function getEstado(): ?string
    {
        return $this->estado;
    }

    public function setEstado(?string $estado): static
    {
        $this->estado = $estado;

        return $this;
    }

    public function getComisionShopby(): ?float
    {
        return $this->comision_shopby;
    }

    public function setComisionShopby(?float $comision_shopby): static
    {
        $this->comision_shopby = $comision_shopby;

        return $this;
    }

    public function getRetiroFinal(): ?float
    {
        return $this->retiro_final;
    }

    public function setRetiroFinal(?float $retiro_final): static
    {
        $this->retiro_final = $retiro_final;

        return $this;
    }

    public function getComentario(): ?string
    {
        return $this->comentario;
    }

    public function setComentario(?string $comentario): static
    {
        $this->comentario = $comentario;

        return $this;
    }

    public function getComprobante(): ?string
    {
        return $this->comprobante;
    }

    public function setComprobante(?string $comprobante): static
    {
        $this->comprobante = $comprobante;

        return $this;
    }

    /**
     * @return Collection<int, Recargas>
     */
    public function getRecargas(): Collection
    {
        return $this->recargas;
    }

    public function addRecarga(Recargas $recarga): static
    {
        if (!$this->recargas->contains($recarga)) {
            $this->recargas->add($recarga);
            $recarga->setRetiro($this);
        }

        return $this;
    }

    public function removeRecarga(Recargas $recarga): static
    {
        if ($this->recargas->removeElement($recarga)) {
            // set the owning side to null (unless already changed)
            if ($recarga->getRetiro() === $this) {
                $recarga->setRetiro(null);
            }
        }

        return $this;
    }
}

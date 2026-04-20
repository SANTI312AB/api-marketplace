<?php

namespace App\Entity;

use App\Repository\CuponRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CuponRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_codigo_valor', columns: ['codigo_cupon'])]
class Cupon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDCUPON")]
    
    private ?int $id = null;

    #[ORM\Column(nullable:false,name:"CODIGO_CUPON", length: 100, unique:true)]
    private ?string $codigo_cupon = null;

    #[ORM\Column(name:"FECHA_INICIO_CUPON",type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fecha_inicio = null;

    #[ORM\Column(name:"FECHA_FIN_CUPON",type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fecha_fin = null;

    #[ORM\Column(name:"TIPO_DESCUENTO",length: 45, nullable:true)]
    private ?string $tipo_descuento = null;

    #[ORM\Column(name:"VALOR_DESCUENTO",nullable: false)]
    private ?float $valor_descuento = null;

    #[ORM\Column(name:"GASTO_MINIMO",nullable: true)]
    private ?float $gasto_minimo = null;

    #[ORM\Column(name:"ACTIVO_CUPON",nullable: true)]
    private ?bool $activo = null;

    #[ORM\Column(name:"DESCRIPCION_CUPON",length: 255,nullable:true)]
    private ?string $descripcion = null;

    #[ORM\Column(name:"LIMITE_USO",nullable: true)]
    private ?int $limite_uso = null;

    #[ORM\Column(name:"USO_CUPON",nullable: true)]
    private ?int $uso_cupon = null;
    
    #[ORM\JoinTable(name: 'cupones_login')]
    #[ORM\JoinColumn(name: 'IDCUPON', referencedColumnName: 'IDCUPON')]
    #[ORM\InverseJoinColumn(name: 'IDLOGIN', referencedColumnName: 'IDLOGIN')]
    #[ORM\ManyToMany(targetEntity: Login::class, inversedBy: 'cupons')]
    private Collection $login;

    #[ORM\OneToMany(mappedBy: 'cupon', targetEntity: Pedidos::class, orphanRemoval: true)]
    private Collection $pedidos;

    #[ORM\JoinTable(name: 'prospecto_cupon')]
    #[ORM\JoinColumn(name: 'IDCUPON', referencedColumnName: 'IDCUPON')]
    #[ORM\InverseJoinColumn(name: 'IDPROSPECTO', referencedColumnName: 'IDPROSPECTO')]
    #[ORM\ManyToMany(targetEntity: Prospecto::class, mappedBy: 'cupon')]
    private Collection $prospectos;

    #[ORM\JoinTable(name: 'producto_cupon')]
    #[ORM\JoinColumn(name: 'IDCUPON', referencedColumnName: 'IDCUPON',onDelete:'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'IDPRODUCTO', referencedColumnName: 'IDPRODUCTO')]
    #[ORM\ManyToMany(targetEntity: Productos::class, mappedBy: 'cupon')]
    private Collection $productos;

    #[ORM\Column(nullable: true,name:"ENVIO_GRATIS")]
    private ?bool $con_envio = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE,nullable: true,name:"CREATED_AT")]
    private ?\DateTimeInterface $creat_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE,nullable: true,name:"UPDATED_AT")]
    private ?\DateTimeInterface $update_at = null;

    #[ORM\Column(length: 255, nullable: true,name:"TIPO_CUPON")]
    private ?string $tipo = null;

    #[ORM\ManyToOne(inversedBy: 'cupons')]
    #[ORM\JoinColumn(nullable: true, name:"IDTIENDA",referencedColumnName:"IDTIENDA")]
    private ?Tiendas $tienda = null;

    #[ORM\Column(nullable: true, name:"AÑADIDO_SALDO", options:["default"=>false])]
    private ?bool $add_saldo = null;


  
    public function __construct()
    {
        $this->login = new ArrayCollection();
        $this->pedidos = new ArrayCollection();
        $this->prospectos = new ArrayCollection();
        $this->productos = new ArrayCollection();
        $this->activo= true;
        $this->add_saldo=false;
        $this->creat_at = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodigoCupon(): ?string
    {
        return $this->codigo_cupon;
    }

    public function setCodigoCupon(string $codigo_cupon): static
    {
        $this->codigo_cupon = $codigo_cupon;

        return $this;
    }

    public function getFechaInicio(): ?\DateTimeInterface
    {
        return $this->fecha_inicio;
    }

    public function setFechaInicio(?\DateTimeInterface $fecha_inicio): static
    {
        $this->fecha_inicio = $fecha_inicio;

        return $this;
    }

    public function getFechaFin(): ?\DateTimeInterface
    {
        return $this->fecha_fin;
    }

    public function setFechaFin(?\DateTimeInterface $fecha_fin): static
    {
        $this->fecha_fin = $fecha_fin;

        return $this;
    }

    public function getTipoDescuento(): ?string
    {
        return $this->tipo_descuento;
    }

    public function setTipoDescuento(?string $tipo_descuento): static
    {
        $this->tipo_descuento = $tipo_descuento;

        return $this;
    }

    public function getValorDescuento(): ?float
    {
        return $this->valor_descuento;
    }

    public function setValorDescuento(?float $valor_descuento): static
    {
        $this->valor_descuento = $valor_descuento;

        return $this;
    }

    public function getGastoMinimo(): ?float
    {
        return $this->gasto_minimo;
    }

    public function setGastoMinimo(?float $gasto_minimo): static
    {
        $this->gasto_minimo = $gasto_minimo;

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

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): static
    {
        $this->descripcion = $descripcion;

        return $this;
    }

    public function getLimiteUso(): ?int
    {
        return $this->limite_uso;
    }

    public function setLimiteUso(?int $limite_uso): static
    {
        $this->limite_uso = $limite_uso;

        return $this;
    }

    public function getUsoCupon(): ?int
    {
        return $this->uso_cupon;
    }

    public function setUsoCupon(?int $uso_cupon): static
    {
        $this->uso_cupon = $uso_cupon;

        return $this;
    }

    /**
     * @return Collection<int, Login>
     */
    public function getLogin(): Collection
    {
        return $this->login;
    }

    public function addLogin(Login $login): static
    {
        if (!$this->login->contains($login)) {
            $this->login->add($login);
        }

        return $this;
    }

    public function removeLogin(Login $login): static
    {
        $this->login->removeElement($login);

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
            $pedido->setCupon($this);
        }

        return $this;
    }

    public function removePedido(Pedidos $pedido): static
    {
        if ($this->pedidos->removeElement($pedido)) {
            // set the owning side to null (unless already changed)
            if ($pedido->getCupon() === $this) {
                $pedido->setCupon(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Prospecto>
     */
    public function getProspectos(): Collection
    {
        return $this->prospectos;
    }

    public function addProspecto(Prospecto $prospecto): static
    {
        if (!$this->prospectos->contains($prospecto)) {
            $this->prospectos->add($prospecto);
            $prospecto->addCupon($this);
        }

        return $this;
    }

    public function removeProspecto(Prospecto $prospecto): static
    {
        if ($this->prospectos->removeElement($prospecto)) {
            $prospecto->removeCupon($this);
        }

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
            $producto->addCupon($this);
        }

        return $this;
    }

    public function removeProducto(Productos $producto): static
    {
        if ($this->productos->removeElement($producto)) {
            $producto->removeCupon($this);
        }

        return $this;
    }

    public function isConEnvio(): ?bool
    {
        return $this->con_envio;
    }

    public function setConEnvio(?bool $con_envio): static
    {
        $this->con_envio = $con_envio;

        return $this;
    }

    public function getCreatAt(): ?\DateTimeInterface
    {
        return $this->creat_at;
    }

    public function setCreatAt(?\DateTimeInterface $creat_at): static
    {
        $this->creat_at = $creat_at;

        return $this;
    }

    public function getUpdateAt(): ?\DateTimeImmutable
    {
        return $this->update_at;
    }

    public function setUpdateAt(?\DateTimeImmutable $update_at): static
    {
        $this->update_at = $update_at;

        return $this;
    }

    public function getTipo(): ?string
    {
        return $this->tipo;
    }

    public function setTipo(?string $tipo): static
    {
        $this->tipo = $tipo;

        return $this;
    }

    public function getTienda(): ?Tiendas
    {
        return $this->tienda;
    }

    public function setTienda(?Tiendas $tienda): static
    {
        $this->tienda = $tienda;

        return $this;
    }

    public function isAddSaldo(): ?bool
    {
        return $this->add_saldo;
    }

    public function setAddSaldo(?bool $add_saldo): static
    {
        $this->add_saldo = $add_saldo;

        return $this;
    }

}

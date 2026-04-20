<?php

namespace App\Entity;

use App\Repository\EstadosRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EstadosRepository::class)]
class Estados
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDESTADO")]
    private ?int $id = null;

    #[ORM\Column(length: 45, nullable: true,name:"NOMBRE_ESTADO")]
    private ?string $nobre_estado = null;

    #[ORM\Column(length: 45, nullable: true,name:"TIPO_ESTADO")]
    private ?string $tipo_estado = null;

    #[ORM\OneToMany(mappedBy: 'estados', targetEntity: Login::class)]
    private Collection $login;

    #[ORM\OneToMany(mappedBy: 'estado', targetEntity: Tiendas::class, orphanRemoval: true)]
    private Collection $tiendas;

    #[ORM\OneToMany(mappedBy: 'estado', targetEntity: Productos::class, orphanRemoval: true)]
    private Collection $productos;

    #[ORM\OneToMany(mappedBy: 'vericacion', targetEntity: Login::class)]
    private Collection $logins;

    #[ORM\OneToMany(mappedBy: 'estados', targetEntity: EntregasTipo::class, orphanRemoval: true)]
    private Collection $entregasTipos;

    #[ORM\OneToMany(mappedBy: 'estado', targetEntity: UsuariosDirecciones::class, orphanRemoval: true)]
    private Collection $usuariosDirecciones;

    #[ORM\OneToMany(mappedBy: 'estados', targetEntity: Usuarios::class, orphanRemoval: true)]
    private Collection $usuarios;

    #[ORM\OneToMany(mappedBy: 'estado_envio', targetEntity: Pedidos::class, orphanRemoval: true)]
    private Collection $pedidos;

    #[ORM\OneToMany(mappedBy: 'estado_retiro', targetEntity: Pedidos::class, orphanRemoval: true)]
    private Collection $pedidos_retiro;

    #[ORM\Column(length: 255, nullable: true,name:"ESTADO_SLUG")]
    private ?string $slug = null;

  

   


    public function __construct()
    {
        $this->login = new ArrayCollection();
        $this->tiendas = new ArrayCollection();
        $this->productos = new ArrayCollection();
        $this->logins = new ArrayCollection();
        $this->entregasTipos = new ArrayCollection();
        $this->usuariosDirecciones = new ArrayCollection();
        $this->usuarios = new ArrayCollection();
        $this->pedidos = new ArrayCollection();
        $this->pedidos_retiro = new ArrayCollection();
    
    }



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNobreEstado(): ?string
    {
        return $this->nobre_estado;
    }

    public function setNobreEstado(?string $nobre_estado): static
    {
        $this->nobre_estado = $nobre_estado;

        return $this;
    }

    public function getTipoEstado(): ?string
    {
        return $this->tipo_estado;
    }

    public function setTipoEstado(?string $tipo_estado): static
    {
        $this->tipo_estado = $tipo_estado;

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
            $login->setEstados($this);
        }

        return $this;
    }

    public function removeLogin(Login $login): static
    {
        if ($this->login->removeElement($login)) {
            // set the owning side to null (unless already changed)
            if ($login->getEstados() === $this) {
                $login->setEstados(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Tiendas>
     */
    public function getTiendas(): Collection
    {
        return $this->tiendas;
    }

    public function addTienda(Tiendas $tienda): static
    {
        if (!$this->tiendas->contains($tienda)) {
            $this->tiendas->add($tienda);
            $tienda->setEstado($this);
        }

        return $this;
    }

    public function removeTienda(Tiendas $tienda): static
    {
        if ($this->tiendas->removeElement($tienda)) {
            // set the owning side to null (unless already changed)
            if ($tienda->getEstado() === $this) {
                $tienda->setEstado(null);
            }
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
            $producto->setEstado($this);
        }

        return $this;
    }

    public function removeProducto(Productos $producto): static
    {
        if ($this->productos->removeElement($producto)) {
            // set the owning side to null (unless already changed)
            if ($producto->getEstado() === $this) {
                $producto->setEstado(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Login>
     */
    public function getLogins(): Collection
    {
        return $this->logins;
    }

    /**
     * @return Collection<int, EntregasTipo>
     */
    public function getEntregasTipos(): Collection
    {
        return $this->entregasTipos;
    }

    public function addEntregasTipo(EntregasTipo $entregasTipo): static
    {
        if (!$this->entregasTipos->contains($entregasTipo)) {
            $this->entregasTipos->add($entregasTipo);
            $entregasTipo->setEstados($this);
        }

        return $this;
    }

    public function removeEntregasTipo(EntregasTipo $entregasTipo): static
    {
        if ($this->entregasTipos->removeElement($entregasTipo)) {
            // set the owning side to null (unless already changed)
            if ($entregasTipo->getEstados() === $this) {
                $entregasTipo->setEstados(null);
            }
        }

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
            $usuariosDireccione->setEstado($this);
        }

        return $this;
    }

    public function removeUsuariosDireccione(UsuariosDirecciones $usuariosDireccione): static
    {
        if ($this->usuariosDirecciones->removeElement($usuariosDireccione)) {
            // set the owning side to null (unless already changed)
            if ($usuariosDireccione->getEstado() === $this) {
                $usuariosDireccione->setEstado(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Usuarios>
     */
    public function getUsuarios(): Collection
    {
        return $this->usuarios;
    }

    public function addUsuario(Usuarios $usuario): static
    {
        if (!$this->usuarios->contains($usuario)) {
            $this->usuarios->add($usuario);
            $usuario->setEstados($this);
        }

        return $this;
    }

    public function removeUsuario(Usuarios $usuario): static
    {
        if ($this->usuarios->removeElement($usuario)) {
            // set the owning side to null (unless already changed)
            if ($usuario->getEstados() === $this) {
                $usuario->setEstados(null);
            }
        }

        return $this;
    }



    /**
     * @return Collection<int, Pedidos>
     */
    public function getPedidosRetiro(): Collection
    {
        return $this->pedidos_retiro;
    }

    public function addPedidosRetiro(Pedidos $pedidosRetiro): static
    {
        if (!$this->pedidos_retiro->contains($pedidosRetiro)) {
            $this->pedidos_retiro->add($pedidosRetiro);
            $pedidosRetiro->setEstadoRetiro($this);
        }

        return $this;
    }

    public function removePedidosRetiro(Pedidos $pedidosRetiro): static
    {
        if ($this->pedidos_retiro->removeElement($pedidosRetiro)) {
            // set the owning side to null (unless already changed)
            if ($pedidosRetiro->getEstadoRetiro() === $this) {
                $pedidosRetiro->setEstadoRetiro(null);
            }
        }

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

   
}

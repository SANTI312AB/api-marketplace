<?php

namespace App\Entity;

use App\Repository\TiendasRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TiendasRepository::class)]
class Tiendas
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDTIENDA")]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'tiendas', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false, name:"IDLOGIN",referencedColumnName:"IDLOGIN")]
    private ?Login $login = null;

    #[ORM\ManyToOne(inversedBy: 'tiendas')]
    #[ORM\JoinColumn(nullable: false,name:"IDESTADO", referencedColumnName:"IDESTADO")]
    private ?Estados $estado = null;
    
    #[ORM\Column(length: 45, nullable: true,name:"SLUG_TIENDA",unique:true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, name:"DESCRIPCION_TIENDA")]
    private ?string $descripcion = null;


    #[ORM\Column(length: 80, nullable: true,name:"COVER_TIENDA")]
    private ?string $cover = null;


    #[ORM\Column(type: Types::TEXT, nullable: true,name:"META_TIENDA")]
    private ?string $meta = null;

    #[ORM\Column(nullable: true,name:"CELULAR_TIENDA")]
    private ?string $celular = null;

    #[ORM\Column(length: 255, nullable: true,name:"TELEFONO_TIENDA")]
    private ?string $telefono = null;

    #[ORM\Column(length: 80, nullable: true, name:"EMAIL_TIENDA")]
    private ?string $email = null;
    
   

    #[ORM\Column(nullable: true,name:"VISIBILIDAD_TIENDA")]
    private ?bool $visibilidad_tienda = true;


    #[ORM\Column(nullable: true,name:"CIRUC_TIENDA")]
    private ?string $ruc_tienda = null;

     
    #[ORM\OneToMany(mappedBy: 'tienda', targetEntity: Productos::class, orphanRemoval: true)]
    private Collection $productos;

    #[ORM\OneToMany(mappedBy: 'tienda', targetEntity: DetallePedido::class, orphanRemoval: true)]
    private Collection $detallePedidos;

    #[ORM\Column(length: 255, nullable: true,name:"NOMBRE_TIENDA")]
    private ?string $nombre_tienda = null;

    #[ORM\Column(length: 255, nullable: true,name:"MAIN_TIENDA")]
    private ?string $main = null;


    #[ORM\OneToMany(mappedBy: 'tienda', targetEntity: Servientrega::class, orphanRemoval: true)]
    private Collection $servientregas;

    #[ORM\OneToMany(mappedBy: 'tienda', targetEntity: GaleriaTienda::class, orphanRemoval: true)]
    private Collection $galeriaTiendas;
    

    #[ORM\OneToMany(mappedBy: 'tienda', targetEntity: Pedidos::class, orphanRemoval: true)]
    private Collection $pedidos;

    #[ORM\Column(nullable: true,name:"COMISION_SHOPBY")]
    private ?int $comision = null;

    #[ORM\Column(length: 255, nullable: true, name:"CONTACTO_TIENDA")]
    private ?string $nombre_contacto = null;

    #[ORM\OneToMany(mappedBy: 'Tiendas', targetEntity: CategoriasTienda::class, orphanRemoval: true)]
    private Collection $categoriasTiendas;

    #[ORM\Column(nullable: true,name:"TIENDA_VISIBLE")]
    private ?bool $visible = null;

    /**
     * @var Collection<int, Subastas>
     */
    #[ORM\OneToMany(mappedBy: 'tienda', targetEntity: Subastas::class, orphanRemoval: true)]
    private Collection $subastas;

    /**
     * @var Collection<int, VirtualTour>
     */
    #[ORM\OneToMany(mappedBy: 'tienda', targetEntity: VirtualTour::class, orphanRemoval: true)]
    private Collection $virtualTours;

    /**
     * @var Collection<int, Cupon>
     */
    #[ORM\OneToMany(mappedBy: 'tienda', targetEntity: Cupon::class, orphanRemoval: true)]
    private Collection $cupons;



    public function __construct()
    {
        $this->productos = new ArrayCollection();
        $this->detallePedidos = new ArrayCollection();
        $this->servientregas = new ArrayCollection();
        $this->galeriaTiendas = new ArrayCollection();
        $this->pedidos = new ArrayCollection();
        $this->categoriasTiendas = new ArrayCollection();
        $this->subastas = new ArrayCollection();
        $this->virtualTours = new ArrayCollection();
        $this->cupons = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

  

    public function getCover(): ?string
    {
        return $this->cover;
    }

    public function setCover(?string $cover): static
    {
        $this->cover = $cover;

        return $this;
    }

    public function getCelular(): ?string
    {
        return $this->celular;
    }

    public function setCelular(string $celular): static
    {
        $this->celular = $celular;

        return $this;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function setTelefono(?string $telefono): static
    {
        $this->telefono = $telefono;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

 

    public function getRucTienda(): ?string
    {
        return $this->ruc_tienda;
    }

    public function setRucTienda(?string $ruc_tienda): static
    {
        $this->ruc_tienda = $ruc_tienda;

        return $this;
    }

    public function isVisibilidadTienda(): ?bool
    {
        return $this->visibilidad_tienda;
    }

    public function setVisibilidadTienda(?bool $visibilidad_tienda): static
    {
        $this->visibilidad_tienda = $visibilidad_tienda;

        return $this;
    }

   



    public function getLogin(): ?Login
    {
        return $this->login;
    }

    public function setLogin(Login $login): static
    {
        $this->login = $login;

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
            $producto->setTienda($this);
        }

        return $this;
    }

    public function removeProducto(Productos $producto): static
    {
        if ($this->productos->removeElement($producto)) {
            // set the owning side to null (unless already changed)
            if ($producto->getTienda() === $this) {
                $producto->setTienda(null);
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getMeta(): ?string
    {
        return $this->meta;
    }

    public function setMeta(?string $meta): static
    {
        $this->meta = $meta;

        return $this;
    }



    /**
     * @return Collection<int, DetallePedido>
     */
    public function getDetallePedidos(): Collection
    {
        return $this->detallePedidos;
    }

    public function addDetallePedido(DetallePedido $detallePedido): static
    {
        if (!$this->detallePedidos->contains($detallePedido)) {
            $this->detallePedidos->add($detallePedido);
            $detallePedido->setTienda($this);
        }

        return $this;
    }

    public function removeDetallePedido(DetallePedido $detallePedido): static
    {
        if ($this->detallePedidos->removeElement($detallePedido)) {
            // set the owning side to null (unless already changed)
            if ($detallePedido->getTienda() === $this) {
                $detallePedido->setTienda(null);
            }
        }

        return $this;
    }

    public function getNombreTienda(): ?string
    {
        return $this->nombre_tienda;
    }

    public function setNombreTienda(?string $nombre_tienda): static
    {
        $this->nombre_tienda = $nombre_tienda;

        return $this;
    }

    public function getMain(): ?string
    {
        return $this->main;
    }

    public function setMain(?string $main): static
    {
        $this->main = $main;

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
            $servientrega->setTienda($this);
        }

        return $this;
    }

    public function removeServientrega(Servientrega $servientrega): static
    {
        if ($this->servientregas->removeElement($servientrega)) {
            // set the owning side to null (unless already changed)
            if ($servientrega->getTienda() === $this) {
                $servientrega->setTienda(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, GaleriaTienda>
     */
    public function getGaleriaTiendas(): Collection
    {
        return $this->galeriaTiendas;
    }

    public function addGaleriaTienda(GaleriaTienda $galeriaTienda): static
    {
        if (!$this->galeriaTiendas->contains($galeriaTienda)) {
            $this->galeriaTiendas->add($galeriaTienda);
            $galeriaTienda->setTienda($this);
        }

        return $this;
    }

    public function removeGaleriaTienda(GaleriaTienda $galeriaTienda): static
    {
        if ($this->galeriaTiendas->removeElement($galeriaTienda)) {
            // set the owning side to null (unless already changed)
            if ($galeriaTienda->getTienda() === $this) {
                $galeriaTienda->setTienda(null);
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
            $pedido->setTienda($this);
        }

        return $this;
    }

    public function removePedido(Pedidos $pedido): static
    {
        if ($this->pedidos->removeElement($pedido)) {
            // set the owning side to null (unless already changed)
            if ($pedido->getTienda() === $this) {
                $pedido->setTienda(null);
            }
        }

        return $this;
    }

    public function getComision(): ?int
    {
        return $this->comision;
    }

    public function setComision(?int $comision): static
    {
        $this->comision = $comision;

        return $this;
    }

    public function getNombreContacto(): ?string
    {
        return $this->nombre_contacto;
    }

    public function setNombreContacto(?string $nombre_contacto): static
    {
        $this->nombre_contacto = $nombre_contacto;

        return $this;
    }

    /**
     * @return Collection<int, CategoriasTienda>
     */
    public function getCategoriasTiendas(): Collection
    {
        return $this->categoriasTiendas;
    }

    public function addCategoriasTienda(CategoriasTienda $categoriasTienda): static
    {
        if (!$this->categoriasTiendas->contains($categoriasTienda)) {
            $this->categoriasTiendas->add($categoriasTienda);
            $categoriasTienda->setTiendas($this);
        }

        return $this;
    }

    public function removeCategoriasTienda(CategoriasTienda $categoriasTienda): static
    {
        if ($this->categoriasTiendas->removeElement($categoriasTienda)) {
            // set the owning side to null (unless already changed)
            if ($categoriasTienda->getTiendas() === $this) {
                $categoriasTienda->setTiendas(null);
            }
        }

        return $this;
    }

    public function isVisible(): ?bool
    {
        return $this->visible;
    }

    public function setVisible(?bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * @return Collection<int, Subastas>
     */
    public function getSubastas(): Collection
    {
        return $this->subastas;
    }

    public function addSubasta(Subastas $subasta): static
    {
        if (!$this->subastas->contains($subasta)) {
            $this->subastas->add($subasta);
            $subasta->setTienda($this);
        }

        return $this;
    }

    public function removeSubasta(Subastas $subasta): static
    {
        if ($this->subastas->removeElement($subasta)) {
            // set the owning side to null (unless already changed)
            if ($subasta->getTienda() === $this) {
                $subasta->setTienda(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, VirtualTour>
     */
    public function getVirtualTours(): Collection
    {
        return $this->virtualTours;
    }

    public function addVirtualTour(VirtualTour $virtualTour): static
    {
        if (!$this->virtualTours->contains($virtualTour)) {
            $this->virtualTours->add($virtualTour);
            $virtualTour->setTienda($this);
        }

        return $this;
    }

    public function removeVirtualTour(VirtualTour $virtualTour): static
    {
        if ($this->virtualTours->removeElement($virtualTour)) {
            // set the owning side to null (unless already changed)
            if ($virtualTour->getTienda() === $this) {
                $virtualTour->setTienda(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Cupon>
     */
    public function getCupons(): Collection
    {
        return $this->cupons;
    }

    public function addCupon(Cupon $cupon): static
    {
        if (!$this->cupons->contains($cupon)) {
            $this->cupons->add($cupon);
            $cupon->setTienda($this);
        }

        return $this;
    }

    public function removeCupon(Cupon $cupon): static
    {
        if ($this->cupons->removeElement($cupon)) {
            // set the owning side to null (unless already changed)
            if ($cupon->getTienda() === $this) {
                $cupon->setTienda(null);
            }
        }

        return $this;
    }
    


}

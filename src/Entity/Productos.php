<?php

namespace App\Entity;

use App\Repository\ProductosRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


#[ORM\Entity(repositoryClass: ProductosRepository::class)]
#[UniqueEntity(fields: ['codigo_producto'], message: 'Este código de producto ya está registrado.')]
class Productos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDPRODUCTO")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'productos')]
    #[ORM\JoinColumn(nullable: false,name:"IDTIENDA",referencedColumnName:"IDTIENDA")]
    private ?Tiendas $tienda = null;

    #[ORM\ManyToOne(inversedBy: 'productos')]
    #[ORM\JoinColumn(nullable: false,name:"IDESTADO",referencedColumnName:"IDESTADO")]
    private ?Estados $estado = null;

    #[ORM\ManyToOne(inversedBy: 'productos')] 
    #[ORM\JoinColumn(nullable: false, name:"IDTIPO_VENTA", referencedColumnName:"IDTIPO_VENTA")]
    private ?ProductosVentas $productos_ventas = null;

    #[ORM\ManyToOne(inversedBy: 'productos')]
    #[ORM\JoinColumn(nullable: true,name:"IDTIPO_PRODUCTO",referencedColumnName:"IDTIPO_PRODUCTO")]
    private ?ProductosTipo $productos_tipo = null;

    #[ORM\ManyToOne(inversedBy: 'productos')]   
    #[ORM\JoinColumn(nullable: true,name:"IDENTRAGAS_TIPO", referencedColumnName:"IDENTRAGAS_TIPO")]
    private ?EntregasTipo $entrgas_tipo = null;


    #[ORM\Column(length: 250,name:"NOMBRE_PRODUCTO")]
    private ?string $nombre_producto = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, name:"DESCRIPCION_CORTA_PRODUCTO")]
    private ?string $descripcion_corta_producto = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, name:"DESCRIPCION_LARGA_PRODUCTO")]
    private ?string $descripcion_larga_producto = null;

    #[ORM\Column(length: 120, nullable: true, name:"IMAGEN_PRODUCTO")]
    private ?string $imegen_producto = null;

    #[ORM\Column(nullable: true,name:"CANTIDAD_PRODUCTO")]
    private ?int $cantidad_producto = null;

    #[ORM\Column(length: 500, nullable: true,name:"SLUG_PRODUCTO", unique:true)]
    private ?string $slug_producto = null;

    #[ORM\Column(nullable: true,name:"PRECIO_NORMAL_PRODUCTO")]
    private ?float $precio_normal_producto = null;

    #[ORM\Column(nullable: true, name:"PRECIO_REBAJADO_PRODUCTO")]
    private ?float $precio_rebajado_producto = null;

    #[ORM\Column(length: 45, nullable: true,name:"EAN_PRODUCTO",unique:false)]
    private ?string $ean_producto = null;

    #[ORM\Column(length: 20, nullable: true,name:"SKU_PRODUCTO")]
    private ?string $sku_producto = null;

    #[ORM\Column(length: 80, nullable: true,name:"VIDEO_PRODUCTO")]
    private ?string $video_producto = null;

    #[ORM\Column(type: Types::TEXT, nullable: true,name:"META_PRODUCTO")]
    private ?string $meta_producto = null;

    #[ORM\Column(length: 300, nullable: true, name:"GARANTIA_PRODUCTO")]
    private ?string $garantia_producto = null;

    #[ORM\Column(nullable: true, name:"REGATEO_PRODUCTO")]
    private ?bool $regateo_producto = null;

    #[ORM\Column(length: 500, nullable: true,name:"ETIQUETAS_PRODUCTO")]
    private ?string $etiquetas_producto = null;


    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name:"FECHA_REGISTRO_PRODUCTO")]
    private ?\DateTimeInterface $fecha_registro_producto = null;

   
    #[ORM\OneToMany(mappedBy: 'producto', targetEntity: ProductosGaleria::class, orphanRemoval: true)]
    private Collection $productosGalerias;

    #[ORM\OneToMany(mappedBy: 'producto', targetEntity: ProductosFavoritos::class, orphanRemoval: true)]
    private Collection $productosFavoritos;
    
    #[ORM\JoinTable(name: 'productos_subcategorias')]
    #[ORM\JoinColumn(name: 'IDPRODUCTO', referencedColumnName: 'IDPRODUCTO',onDelete:'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'IDSUBCATEGORIA', referencedColumnName: 'IDSUBCATEGORIA')]
    #[ORM\ManyToMany(targetEntity: Subcategorias::class, inversedBy: 'productos')]
    private Collection $subcategorias;

    #[ORM\ManyToOne(inversedBy: 'productos')]
    #[ORM\JoinColumn(nullable: true,name:"IDMARCA",referencedColumnName:"IDMARCA")]
    private ?ProductosMarcas $marcas = null;

    #[ORM\OneToMany(mappedBy: 'productos', targetEntity: ProductosComentarios::class, orphanRemoval: true)]
    private Collection $productosComentarios;


    #[ORM\ManyToOne(inversedBy: 'productos')]
    #[ORM\JoinColumn(nullable:true,name:"IDUSUARIOS_DIRECCIONES",referencedColumnName:"IDUSUARIOS_DIRECCIONES")]
    private ?UsuariosDirecciones $direcciones = null;

    #[ORM\JoinTable(name: 'productos_categorias')]
    #[ORM\JoinColumn(name: 'IDPRODUCTO', referencedColumnName: 'IDPRODUCTO',onDelete:'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'IDCATEGORIA', referencedColumnName: 'IDCATEGORIA')]
    #[ORM\ManyToMany(targetEntity: Categorias::class, inversedBy: 'productos')]
    private Collection $categorias;

    #[ORM\OneToMany(mappedBy: 'productos', targetEntity: Variaciones::class, orphanRemoval: true)]
    private Collection $variaciones;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true,name:"FECHA_EDICION_PRODUCTO")]
    private ?\DateTimeInterface $fecha_edicion = null;

    #[ORM\Column(name:"PRODUCTO_VARIABLE")]
    private ?bool $variable =null;

    #[ORM\Column(type: Types::TEXT, nullable: true,name:"FICHA_TECNICA_PRODUCTO")]
    private ?string $ficha_tecnica = null;

    #[ORM\Column(name:"PRODUCTO_DISPONIBILIDAD")]
    private ?bool $disponibilidad_producto = null;

    #[ORM\Column(nullable: true,name:"LARGO_PRODUCTO")]
    private ?float $largo = null;

    #[ORM\Column(nullable: true,name:"ANCHO_PROODUCTO")]
    private ?float $ancho = null;

    #[ORM\Column(nullable: true,name:"ALTO_PRODUCTO")]
    private ?float $alto = null;

    #[ORM\OneToMany(mappedBy: 'IdProducto', targetEntity: DetalleCarrito::class, orphanRemoval: true)]
    private Collection $detalleCarritos;

  
    #[ORM\Column(nullable: true,name:"PRODUCTO_TIENE_IVA")]
    private ?bool $tiene_iva = true;


    #[ORM\Column(nullable: true,name:"PRODUCTO_INCLUIDO_IMPUESTOS")]
    private ?bool $impuestos_incluidos = false;

    #[ORM\OneToMany(mappedBy: 'IdProductos', targetEntity: DetallePedido::class, orphanRemoval: true)]
    private Collection $detallePedidos;

    #[ORM\Column(nullable: true,name:"PESO_PRODUCTO")]
    private ?float $peso = null;

    #[ORM\OneToMany(mappedBy: 'producto', targetEntity: Preguntas::class, orphanRemoval: true)]
    private Collection $preguntas;

    #[ORM\Column(nullable: true,name:"TIENE_DESCUENTO")]
    private ?bool $tiene_descuento = false;

    #[ORM\JoinTable(name: 'producto_cupon')]
    #[ORM\JoinColumn(name: 'IDPRODUCTO', referencedColumnName: 'IDPRODUCTO')]
    #[ORM\InverseJoinColumn(name: 'IDCUPON', referencedColumnName: 'IDCUPON')]
    #[ORM\ManyToMany(targetEntity: Cupon::class, inversedBy: 'productos')]
    private Collection $cupon;
    
    #[ORM\JoinTable(name: 'productos_categorias_tienda')]
    #[ORM\JoinColumn(name: 'IDPRODUCTO', referencedColumnName: 'IDPRODUCTO')]
    #[ORM\InverseJoinColumn(name: 'IDCATEGORIA_TIENDA', referencedColumnName: 'IDCATEGORIA_TIENDA')]
    #[ORM\ManyToMany(targetEntity: CategoriasTienda::class, inversedBy: 'productos')]
    private Collection $categoriasTiendas;

    /**
     * @var Collection<int, Subastas>
     */
    #[ORM\OneToMany(mappedBy: 'IdProducto', targetEntity: Subastas::class, orphanRemoval: true)]
    private Collection $subastas;

   
    #[ORM\JoinTable(name: 'bloque_producto')]
    #[ORM\JoinColumn(name: 'IDPRODUCTO', referencedColumnName: 'IDPRODUCTO',onDelete:'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'IDBLOQUE_PROMOCIONAL', referencedColumnName: 'IDBLOQUE_PROMOCIONAL')]
    #[ORM\ManyToMany(targetEntity: BloquesPromocionales::class, mappedBy: 'productos')]
    private Collection $bloquesPromocionales;

    #[ORM\Column(nullable: true, name:"PRODUCTO_SUSPENDIDO")]
    private ?bool $suspendido = null;

    #[ORM\Column(nullable: true, name:"PRODUCTO_BORRADOR")]
    private ?bool $borrador = null;

  
    #[ORM\JoinTable(name: 'productos_subcategorias_tienda')]
    #[ORM\JoinColumn(name: 'IDPRODUCTO', referencedColumnName: 'IDPRODUCTO',onDelete:'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'IDSUBCATEGORIATIENDA', referencedColumnName: 'IDSUBCATEGORIATIENDA')]
    #[ORM\ManyToMany(targetEntity: SubcategoriasTiendas::class, mappedBy: 'productos')]
    private Collection $subcategoriasTiendas;

    #[ORM\Column(nullable: true, name:"FROM_FORM")]
    private ?bool $from_form = null;

    #[ORM\OneToOne(mappedBy: 'producto', cascade: ['persist', 'remove'])]
    private ?Destacados $destacados = null;

    #[ORM\Column(length: 255, nullable: true,name:"COBRO_SERVICIO")]
    private ?string $cobro_servicio = null;

    



    /**
     * @var Collection<int, Recargas>
     */
    #[ORM\OneToMany(mappedBy: 'producto', targetEntity: Recargas::class, orphanRemoval: true)]
    private Collection $recargas;

    #[ORM\Column(length: 255, nullable: true,name:"TIPO_SERVICIO")]
    private ?string $tipo_servicio = null;

    /**
     * @var Collection<int, Regateos>
     */
    #[ORM\OneToMany(mappedBy: 'producto', targetEntity: Regateos::class, orphanRemoval: true)]
    private Collection $regateos;

    #[ORM\ManyToOne(inversedBy: 'productos')]
    #[ORM\JoinColumn(nullable:true, name:"IDCIUDAD", referencedColumnName:"IDCIUDAD")]
    private ?Ciudades $ciudad_servicio = null;

    #[ORM\Column(nullable: true,name:"TIEMPO_ENTREGA",options:["default"=>72])]
    private ?int $tiempo_entrega = null;

    #[ORM\Column(length: 50, nullable: true, name:"CODIGO_PRODUCTO", unique:true)]
    private ?string $codigo_producto = null;

    public function __construct()
    { 
        $this->fecha_registro_producto = new \DateTime();
        $this->productosGalerias = new ArrayCollection();
        $this->productosFavoritos = new ArrayCollection();
        $this->subcategorias = new ArrayCollection();
        $this->productosComentarios = new ArrayCollection();
        $this->categorias = new ArrayCollection();
        $this->variaciones = new ArrayCollection();  
        $this->disponibilidad_producto= true;
        $this->suspendido= false;
        $this->detalleCarritos = new ArrayCollection();
        $this->detallePedidos = new ArrayCollection();
        $this->preguntas = new ArrayCollection();
        $this->cupon = new ArrayCollection();
        $this->categoriasTiendas = new ArrayCollection();
        $this->subastas = new ArrayCollection();
        $this->bloquesPromocionales = new ArrayCollection();
        $this->borrador= true;
        $this->from_form= false;
        $this->subcategoriasTiendas = new ArrayCollection();
        $this->recargas = new ArrayCollection();
        $this->regateos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombreProducto(): ?string
    {
        return $this->nombre_producto;
    }

    public function setNombreProducto(?string $nombre_producto): static
    {
        $this->nombre_producto = $nombre_producto;

        return $this;
    }

    public function getDescripcionCortaProducto(): ?string
    {
        return $this->descripcion_corta_producto;
    }

    public function setDescripcionCortaProducto(?string $descripcion_corta_producto): static
    {
        $this->descripcion_corta_producto = $descripcion_corta_producto;

        return $this;
    }

    public function getDescripcionLargaProducto(): ?string
    {
        return $this->descripcion_larga_producto;
    }

    public function setDescripcionLargaProducto(?string $descripcion_larga_producto): static
    {
        $this->descripcion_larga_producto = $descripcion_larga_producto;

        return $this;
    }

   

    public function getCantidadProducto(): ?int
    {
        return $this->cantidad_producto;
    }

    public function setCantidadProducto(?int $cantidad_producto): static
    {
        $this->cantidad_producto = $cantidad_producto;

        return $this;
    }
    
    public function getSlugProducto(): ?string
    {
        return $this->slug_producto;
    }

    public function setSlugProducto(?string $slug_producto): static
    {
        $this->slug_producto = $slug_producto;

        return $this;
    }

   


    public function getEanProducto(): ?string
    {
        return $this->ean_producto;
    }

    public function setEanProducto(?string $ean_producto): static
    {
        $this->ean_producto = $ean_producto;

        return $this;
    }

    public function getSkuProducto(): ?string
    {
        return $this->sku_producto;
    }

    public function setSkuProducto(?string $sku_producto): static
    {
        $this->sku_producto = $sku_producto;

        return $this;
    }

    public function getVideoProducto(): ?string
    {
        return $this->video_producto;
    }

    public function setVideoProducto(?string $video_producto): static
    {
        $this->video_producto = $video_producto;

        return $this;
    }

    public function getMetaProducto(): ?string
    {
        return $this->meta_producto;
    }

    public function setMetaProducto(?string $meta_producto): static
    {
        $this->meta_producto = $meta_producto;

        return $this;
    }

    public function getGarantiaProducto(): ?string
    {
        return $this->garantia_producto;
    }

    public function setGarantiaProducto(?string $garantia_producto): static
    {
        $this->garantia_producto = $garantia_producto;

        return $this;
    }

    public function isRegateoProducto(): ?bool
    {
        return $this->regateo_producto;
    }

    public function setRegateoProducto(?bool $regateo_producto): static
    {
        $this->regateo_producto = $regateo_producto;

        return $this;
    }

    public function getEtiquetasProducto(): ?string
    {
        return $this->etiquetas_producto;
    }

    public function setEtiquetasProducto(?string $etiquetas_producto): static
    {
        $this->etiquetas_producto = $etiquetas_producto;

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

    public function getEstado(): ?Estados
    {
        return $this->estado;
    }

    public function setEstado(?Estados $estado): static
    {
        $this->estado = $estado;

        return $this;
    }

    public function getPrecioNormalProducto(): ?float
    {
        return $this->precio_normal_producto;
    }

    public function setPrecioNormalProducto(?float $precio_normal_producto): static
    {
        $this->precio_normal_producto = $precio_normal_producto;

        return $this;
    }

    public function getPrecioRebajadoProducto(): ?float
    {
        return $this->precio_rebajado_producto;
    }

    public function setPrecioRebajadoProducto(?float $precio_rebajado_producto): static
    {
        $this->precio_rebajado_producto = $precio_rebajado_producto;

        return $this;
    }

  

    public function getFechaRegistroProducto(): ?\DateTimeInterface
    {
        return $this->fecha_registro_producto;
    }

    public function setFechaRegistroProducto(?\DateTimeInterface $fecha_registro_producto): static
    {
        $this->fecha_registro_producto = $fecha_registro_producto;

        return $this;
    }

    public function getImegenProducto(): ?string
    {
        return $this->imegen_producto;
    }

    public function setImegenProducto(?string $imegen_producto): static
    {
        $this->imegen_producto = $imegen_producto;

        return $this;
    }

    /**
     * @return Collection<int, ProductosGaleria>
     */
    public function getProductosGalerias(): Collection
    {
        return $this->productosGalerias;
    }

    public function addProductosGaleria(ProductosGaleria $productosGaleria): static
    {
        if (!$this->productosGalerias->contains($productosGaleria)) {
            $this->productosGalerias->add($productosGaleria);
            $productosGaleria->setProducto($this);
        }

        return $this;
    }

    public function removeProductosGaleria(ProductosGaleria $productosGaleria): static
    {
        if ($this->productosGalerias->removeElement($productosGaleria)) {
            // set the owning side to null (unless already changed)
            if ($productosGaleria->getProducto() === $this) {
                $productosGaleria->setProducto(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductosFavoritos>
     */
    public function getProductosFavoritos(): Collection
    {
        return $this->productosFavoritos;
    }

    public function addProductosFavorito(ProductosFavoritos $productosFavorito): static
    {
        if (!$this->productosFavoritos->contains($productosFavorito)) {
            $this->productosFavoritos->add($productosFavorito);
            $productosFavorito->setProducto($this);
        }

        return $this;
    }

    public function removeProductosFavorito(ProductosFavoritos $productosFavorito): static
    {
        if ($this->productosFavoritos->removeElement($productosFavorito)) {
            // set the owning side to null (unless already changed)
            if ($productosFavorito->getProducto() === $this) {
                $productosFavorito->setProducto(null);
            }
        }

        return $this;
    }

    public function getProductosVentas(): ?ProductosVentas
    {
        return $this->productos_ventas;
    }

    public function setProductosVentas(?ProductosVentas $productos_ventas): static
    {
        $this->productos_ventas = $productos_ventas;

        return $this;
    }

    public function getProductosTipo(): ?ProductosTipo
    {
        return $this->productos_tipo;
    }

    public function setProductosTipo(?ProductosTipo $productos_tipo): static
    {
        $this->productos_tipo = $productos_tipo;

        return $this;
    }

    public function getEntrgasTipo(): ?EntregasTipo
    {
        return $this->entrgas_tipo;
    }

    public function setEntrgasTipo(?EntregasTipo $entrgas_tipo): static
    {
        $this->entrgas_tipo = $entrgas_tipo;

        return $this;
    }



    /**
     * @return Collection<int, Subcategorias>
     */
    public function getSubcategorias(): Collection
    {
        return $this->subcategorias;
    }

    public function addSubcategoria(Subcategorias $subcategoria): static
    {
        if (!$this->subcategorias->contains($subcategoria)) {
            $this->subcategorias->add($subcategoria);
        }

        return $this;
    }

    public function removeSubcategoria(Subcategorias $subcategoria): static
    {
        $this->subcategorias->removeElement($subcategoria);

        return $this;
    }

    public function getMarcas(): ?ProductosMarcas
    {
        return $this->marcas;
    }

    public function setMarcas(?ProductosMarcas $marcas): static
    {
        $this->marcas = $marcas;

        return $this;
    }

    /**
     * @return Collection<int, ProductosComentarios>
     */
    public function getProductosComentarios(): Collection
    {
        return $this->productosComentarios;
    }

    public function addProductosComentario(ProductosComentarios $productosComentario): static
    {
        if (!$this->productosComentarios->contains($productosComentario)) {
            $this->productosComentarios->add($productosComentario);
            $productosComentario->setProductos($this);
        }

        return $this;
    }

    public function removeProductosComentario(ProductosComentarios $productosComentario): static
    {
        if ($this->productosComentarios->removeElement($productosComentario)) {
            // set the owning side to null (unless already changed)
            if ($productosComentario->getProductos() === $this) {
                $productosComentario->setProductos(null);
            }
        }

        return $this;
    }

    

   

    public function getDirecciones(): ?UsuariosDirecciones
    {
        return $this->direcciones;
    }

    public function setDirecciones(?UsuariosDirecciones $direcciones): static
    {
        $this->direcciones = $direcciones;

        return $this;
    }

    /**
     * @return Collection<int, Categorias>
     */
    public function getCategorias(): Collection
    {
        return $this->categorias;
    }

    public function addCategoria(Categorias $categoria): static
    {
        if (!$this->categorias->contains($categoria)) {
            $this->categorias->add($categoria);
        }

        return $this;
    }

    public function removeCategoria(Categorias $categoria): static
    {
        $this->categorias->removeElement($categoria);

        return $this;
    }

    /**
     * @return Collection<int, Variaciones>
     */
    public function getVariaciones(): Collection
    {
        return $this->variaciones;
    }

    public function addVariacione(Variaciones $variacione): static
    {
        if (!$this->variaciones->contains($variacione)) {
            $this->variaciones->add($variacione);
            $variacione->setProductos($this);
        }

        return $this;
    }

    public function removeVariacione(Variaciones $variacione): static
    {
        if ($this->variaciones->removeElement($variacione)) {
            // set the owning side to null (unless already changed)
            if ($variacione->getProductos() === $this) {
                $variacione->setProductos(null);
            }
        }

        return $this;
    }

    public function getFechaEdicion(): ?\DateTimeInterface
    {
        return $this->fecha_edicion;
    }

    public function setFechaEdicion(?\DateTimeInterface $fecha_edicion): static
    {
        $this->fecha_edicion = $fecha_edicion;

        return $this;
    }

    public function isVariable(): ?bool
    {
        return $this->variable;
    }

    public function setVariable(?bool $variable): static
    {
        $this->variable = $variable;

        return $this;
    }

    

    public function getFichaTecnica(): ?string
    {
        return $this->ficha_tecnica;
    }

    public function setFichaTecnica(?string $ficha_tecnica): static
    {
        $this->ficha_tecnica = $ficha_tecnica;

        return $this;
    }

    public function isDisponibilidadProducto(): ?bool
    {
        return $this->disponibilidad_producto;
    }

    public function setDisponibilidadProducto(?bool $disponibilidad_producto): static
    {
        $this->disponibilidad_producto = $disponibilidad_producto;

        return $this;
    }

    public function getLargo(): ?float
    {
        return $this->largo;
    }

    public function setLargo(?float $largo): static
    {
        $this->largo = $largo;

        return $this;
    }

    public function getAncho(): ?float
    {
        return $this->ancho;
    }

    public function setAncho(?float $ancho): static
    {
        $this->ancho = $ancho;

        return $this;
    }

    public function getAlto(): ?float
    {
        return $this->alto;
    }

    public function setAlto(?float $alto): static
    {
        $this->alto = $alto;

        return $this;
    }

    /**
     * @return Collection<int, DetalleCarrito>
     */
    public function getDetalleCarritos(): Collection
    {
        return $this->detalleCarritos;
    }

    public function addDetalleCarrito(DetalleCarrito $detalleCarrito): static
    {
        if (!$this->detalleCarritos->contains($detalleCarrito)) {
            $this->detalleCarritos->add($detalleCarrito);
            $detalleCarrito->setIdProducto($this);
        }

        return $this;
    }

    public function removeDetalleCarrito(DetalleCarrito $detalleCarrito): static
    {
        if ($this->detalleCarritos->removeElement($detalleCarrito)) {
            // set the owning side to null (unless already changed)
            if ($detalleCarrito->getIdProducto() === $this) {
                $detalleCarrito->setIdProducto(null);
            }
        }

        return $this;
    }


    public function isTieneIva(): ?bool
    {
        return $this->tiene_iva;
    }

    public function setTieneIva(?bool $tiene_iva): static
    {
        $this->tiene_iva = $tiene_iva;

        return $this;
    }

  
    public function isImpuestosIncluidos(): ?bool
    {
        return $this->impuestos_incluidos;
    }

    public function setImpuestosIncluidos(?bool $impuestos_incluidos): static
    {
        $this->impuestos_incluidos = $impuestos_incluidos;

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
            $detallePedido->setIdProductos($this);
        }

        return $this;
    }

    public function removeDetallePedido(DetallePedido $detallePedido): static
    {
        if ($this->detallePedidos->removeElement($detallePedido)) {
            // set the owning side to null (unless already changed)
            if ($detallePedido->getIdProductos() === $this) {
                $detallePedido->setIdProductos(null);
            }
        }

        return $this;
    }

    public function getPeso(): ?float
    {
        return $this->peso;
    }

    public function setPeso(?float $peso): static
    {
        $this->peso = $peso;

        return $this;
    }

    /**
     * @return Collection<int, Preguntas>
     */
    public function getPreguntas(): Collection
    {
        return $this->preguntas;
    }

    public function addPregunta(Preguntas $pregunta): static
    {
        if (!$this->preguntas->contains($pregunta)) {
            $this->preguntas->add($pregunta);
            $pregunta->setProducto($this);
        }

        return $this;
    }

    public function removePregunta(Preguntas $pregunta): static
    {
        if ($this->preguntas->removeElement($pregunta)) {
            // set the owning side to null (unless already changed)
            if ($pregunta->getProducto() === $this) {
                $pregunta->setProducto(null);
            }
        }

        return $this;
    }

    public function isTieneDescuento(): ?bool
    {
        return $this->tiene_descuento;
    }

    public function setTieneDescuento(?bool $tiene_descuento): static
    {
        $this->tiene_descuento = $tiene_descuento;

        return $this;
    }

    /**
     * @return Collection<int, Cupon>
     */
    public function getCupon(): Collection
    {
        return $this->cupon;
    }

    public function addCupon(Cupon $cupon): static
    {
        if (!$this->cupon->contains($cupon)) {
            $this->cupon->add($cupon);
        }

        return $this;
    }

    public function removeCupon(Cupon $cupon): static
    {
        $this->cupon->removeElement($cupon);

        return $this;
    }

    /**
     * @return Collection<int, CategoriasTienda>
     */
    public function getCategoriasTiendas(): Collection
    {
        return $this->categoriasTiendas;
    }

    public function addCategoriasTienda(CategoriasTienda $categoriasTienda): self
    {
        if (!$this->categoriasTiendas->contains($categoriasTienda)) {
            $this->categoriasTiendas->add($categoriasTienda);
            $categoriasTienda->addProducto($this);
        }

        return $this;
    }

    public function removeCategoriasTienda(CategoriasTienda $categoriasTienda): self
    {
        if ($this->categoriasTiendas->removeElement($categoriasTienda)) {
            $categoriasTienda->removeProducto($this);
        }

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
            $subasta->setIdProducto($this);
        }

        return $this;
    }

    public function removeSubasta(Subastas $subasta): static
    {
        if ($this->subastas->removeElement($subasta)) {
            // set the owning side to null (unless already changed)
            if ($subasta->getIdProducto() === $this) {
                $subasta->setIdProducto(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, BloquesPromocionales>
     */
    public function getBloquesPromocionales(): Collection
    {
        return $this->bloquesPromocionales;
    }

    public function addBloquesPromocionale(BloquesPromocionales $bloquesPromocionale): static
    {
        if (!$this->bloquesPromocionales->contains($bloquesPromocionale)) {
            $this->bloquesPromocionales->add($bloquesPromocionale);
            $bloquesPromocionale->addProducto($this);
        }

        return $this;
    }

    public function removeBloquesPromocionale(BloquesPromocionales $bloquesPromocionale): static
    {
        if ($this->bloquesPromocionales->removeElement($bloquesPromocionale)) {
            $bloquesPromocionale->removeProducto($this);
        }

        return $this;
    }

    public function isSuspendido(): ?bool
    {
        return $this->suspendido;
    }

    public function setSuspendido(?bool $suspendido): static
    {
        $this->suspendido = $suspendido;

        return $this;
    }

    public function isBorrador(): ?bool
    {
        return $this->borrador;
    }

    public function setBorrador(?bool $borrador): static
    {
        $this->borrador = $borrador;

        return $this;
    }

    /**
     * @return Collection<int, SubcategoriasTiendas>
     */
    public function getSubcategoriasTiendas(): Collection
    {
        return $this->subcategoriasTiendas;
    }

    public function addSubcategoriasTienda(SubcategoriasTiendas $subcategoriasTienda): static
    {
        if (!$this->subcategoriasTiendas->contains($subcategoriasTienda)) {
            $this->subcategoriasTiendas->add($subcategoriasTienda);
            $subcategoriasTienda->addProducto($this);
        }

        return $this;
    }

    public function removeSubcategoriasTienda(SubcategoriasTiendas $subcategoriasTienda): static
    {
        if ($this->subcategoriasTiendas->removeElement($subcategoriasTienda)) {
            $subcategoriasTienda->removeProducto($this);
        }

        return $this;
    }

    public function isFromForm(): ?bool
    {
        return $this->from_form;
    }

    public function setFromForm(?bool $from_form): static
    {
        $this->from_form = $from_form;

        return $this;
    }

    public function getDestacados(): ?Destacados
    {
        return $this->destacados;
    }

    public function setDestacados(?Destacados $destacados): static
    {
        // unset the owning side of the relation if necessary
        if ($destacados === null && $this->destacados !== null) {
            $this->destacados->setProducto(null);
        }

        // set the owning side of the relation if necessary
        if ($destacados !== null && $destacados->getProducto() !== $this) {
            $destacados->setProducto($this);
        }

        $this->destacados = $destacados;

        return $this;
    }

    public function getCobroServicio(): ?string
    {
        return $this->cobro_servicio;
    }

    public function setCobroServicio(?string $cobro_servicio): static
    {
        $this->cobro_servicio = $cobro_servicio;

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
            $recarga->setProducto($this);
        }

        return $this;
    }

    public function removeRecarga(Recargas $recarga): static
    {
        if ($this->recargas->removeElement($recarga)) {
            // set the owning side to null (unless already changed)
            if ($recarga->getProducto() === $this) {
                $recarga->setProducto(null);
            }
        }

        return $this;
    }

    public function getTipoServicio(): ?string
    {
        return $this->tipo_servicio;
    }

    public function setTipoServicio(?string $tipo_servicio): static
    {
        $this->tipo_servicio = $tipo_servicio;

        return $this;
    }

    /**
     * @return Collection<int, Regateos>
     */
    public function getRegateos(): Collection
    {
        return $this->regateos;
    }

    public function addRegateo(Regateos $regateo): static
    {
        if (!$this->regateos->contains($regateo)) {
            $this->regateos->add($regateo);
            $regateo->setProducto($this);
        }

        return $this;
    }

    public function removeRegateo(Regateos $regateo): static
    {
        if ($this->regateos->removeElement($regateo)) {
            // set the owning side to null (unless already changed)
            if ($regateo->getProducto() === $this) {
                $regateo->setProducto(null);
            }
        }

        return $this;
    }

    public function getCiudadServicio(): ?Ciudades
    {
        return $this->ciudad_servicio;
    }

    public function setCiudadServicio(?Ciudades $ciudad_servicio): static
    {
        $this->ciudad_servicio = $ciudad_servicio;

        return $this;
    }

    public function getTiempoEntrega(): ?int
    {
        return $this->tiempo_entrega;
    }

    public function setTiempoEntrega(?int $tiempo_entrega): static
    {
        $this->tiempo_entrega = $tiempo_entrega;

        return $this;
    }

    public function getCodigoProducto(): ?string
    {
        return $this->codigo_producto;
    }

    public function setCodigoProducto(?string $codigo_producto): static
    {
        $this->codigo_producto = $codigo_producto;

        return $this;
    }
 
}

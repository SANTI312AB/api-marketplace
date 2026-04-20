<?php

namespace App\Entity;

use App\Repository\DetallePedidoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DetallePedidoRepository::class)]
class DetallePedido
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDDETALLE_PEDIDO")]
    private ?int $id = null;

    #[ORM\Column(nullable: true,name:"CANTIDAD")]
    private ?int $cantidad = null;

    #[ORM\Column(nullable: true,name:"SUBTOTAL")]
    private ?float $subtotal = null;

    #[ORM\Column(nullable: true,name:"IMPUESTO")]
    private ?float $impuesto = null;


    #[ORM\Column(nullable: true,name:"COSTO_TOTAL")]
    private ?float $total = null;

    #[ORM\ManyToOne(inversedBy: 'detallePedidos')]
    #[ORM\JoinColumn(nullable: false,name:"IDPRODUCTO",referencedColumnName:"IDPRODUCTO")]
    private ?Productos $IdProductos = null;

    #[ORM\ManyToOne(inversedBy: 'detallePedidos')]
    #[ORM\JoinColumn(nullable: true,name:"IDVARIACION",referencedColumnName:"IDVARIACION")]
    private ?Variaciones $IdVariacion = null;

    #[ORM\ManyToOne(inversedBy: 'detallePedidos')]
    #[ORM\JoinColumn(nullable: false, name: "IDPEDIDO", referencedColumnName: "IDPEDIDO", onDelete: "CASCADE")]
    private ?Pedidos $pedido = null;

    #[ORM\ManyToOne(inversedBy: 'detallePedidos')]
    #[ORM\JoinColumn(nullable: false,name:"IDTIENDA",referencedColumnName:"IDTIENDA")]
    private ?Tiendas $tienda = null;

    #[ORM\Column(length: 255, nullable: true,name:"CIUDAD_REMITE")]
    private ?string $ciudad_remite = null;

    #[ORM\Column(length: 700, nullable: true,name:"DIRECCION_REMITE")]
    private ?string $direccion_remite = null;


    #[ORM\Column(length: 255, nullable: true,name:"NOMBRE_PRODUCTO")]
    private ?string $nombre_producto = null;


    #[ORM\Column(nullable: true,name:"ID_SERVIENTREGA")]
    private ?int $id_direccion = null;

    #[ORM\Column(length: 255, nullable: true,name:"PROVINCIA_VENDEDOR")]
    private ?string $provincia = null;

    #[ORM\Column(length: 255, nullable: true,name:"REGION_VENDEDOR")]
    private ?string $region = null;

    #[ORM\Column(nullable: true,name:"PESO_PRODUCTO")]
    private ?float $peso = null;

    #[ORM\Column(nullable: true,name:"DESCUENTO_PRODUCTO")]
    private ?float $descuento_producto = null;

    #[ORM\Column(nullable: true,name:"SUBTOTAL_ORIGINAL")]
    private ?float $subtotal_original = null;

    #[ORM\Column(nullable: true, name:"LATITUD")]
    private ?float $latitud = null;

    #[ORM\Column(nullable: true , name:"LONGITUD")]
    private ?float $longitud = null;

    #[ORM\Column(length: 255, nullable: true,name:"CELULAR_REMITE")]
    private ?string $celular = null;

    #[ORM\Column(length: 255, nullable: true,name:"REFERENCIA")]
    private ?string $referencia = null;

    #[ORM\Column(length: 255, nullable: true,name:"NOMBRE_REMITE")]
    private ?string $nombre = null;

    #[ORM\Column(nullable: true, name:"TOTAL_PRECIO_UNITARIO")]
    private ?float $precio_unitario = null;

    #[ORM\Column(nullable: true, name:"IVA_PRECIO_UNITARIO")]
    private ?float $iva_unitario = null;

    #[ORM\Column(nullable: true, name:"SUBTOTAL_PRECIO_UNITARIO")]
    private ?float $subtotal_unitario = null;

    #[ORM\Column(length: 50, nullable: true, name:"CODIGO_PRODUCTO")]
    private ?string $codigo_producto = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCantidad(): ?int
    {
        return $this->cantidad;
    }

    public function setCantidad(?int $cantidad): static
    {
        $this->cantidad = $cantidad;

        return $this;
    }

    public function getSubtotal(): ?float
    {
        return $this->subtotal;
    }

    public function setSubtotal(?float $subtotal): static
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    public function getImpuesto(): ?float
    {
        return $this->impuesto;
    }

    public function setImpuesto(?float $impuesto): static
    {
        $this->impuesto = $impuesto;

        return $this;
    }

    
    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal(?float $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getIdProductos(): ?Productos
    {
        return $this->IdProductos;
    }

    public function setIdProductos(?Productos $IdProductos): static
    {
        $this->IdProductos = $IdProductos;

        return $this;
    }

    public function getIdVariacion(): ?Variaciones
    {
        return $this->IdVariacion;
    }

    public function setIdVariacion(?Variaciones $IdVariacion): static
    {
        $this->IdVariacion = $IdVariacion;

        return $this;
    }

    public function getPedido(): ?Pedidos
    {
        return $this->pedido;
    }

    public function setPedido(?Pedidos $pedido): static
    {
        $this->pedido = $pedido;

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

    public function getCiudadRemite(): ?string
    {
        return $this->ciudad_remite;
    }

    public function setCiudadRemite(?string $ciudad_remite): static
    {
        $this->ciudad_remite = $ciudad_remite;

        return $this;
    }

    public function getDireccionRemite(): ?string
    {
        return $this->direccion_remite;
    }

    public function setDireccionRemite(?string $direccion_remite): static
    {
        $this->direccion_remite = $direccion_remite;

        return $this;
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



   

    public function getIdDireccion(): ?int
    {
        return $this->id_direccion;
    }

    public function setIdDireccion(?int $id_direccion): static
    {
        $this->id_direccion = $id_direccion;

        return $this;
    }

    public function getProvincia(): ?string
    {
        return $this->provincia;
    }

    public function setProvincia(?string $provincia): static
    {
        $this->provincia = $provincia;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): static
    {
        $this->region = $region;

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

    public function getDescuentoProducto(): ?float
    {
        return $this->descuento_producto;
    }

    public function setDescuentoProducto(?float $descuento_producto): static
    {
        $this->descuento_producto = $descuento_producto;

        return $this;
    }

    public function getSubtotalOriginal(): ?float
    {
        return $this->subtotal_original;
    }

    public function setSubtotalOriginal(?float $subtotal_original): static
    {
        $this->subtotal_original = $subtotal_original;

        return $this;
    }

    public function getLatitud(): ?float
    {
        return $this->latitud;
    }

    public function setLatitud(?float $latitud): static
    {
        $this->latitud = $latitud;

        return $this;
    }

    public function getLongitud(): ?float
    {
        return $this->longitud;
    }

    public function setLongitud(?float $longitud): static
    {
        $this->longitud = $longitud;

        return $this;
    }

    public function getCelular(): ?string
    {
        return $this->celular;
    }

    public function setCelular(?string $celular): static
    {
        $this->celular = $celular;

        return $this;
    }

    public function getReferencia(): ?string
    {
        return $this->referencia;
    }

    public function setReferencia(?string $referencia): static
    {
        $this->referencia = $referencia;

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

    public function getPrecioUnitario(): ?float
    {
        return $this->precio_unitario;
    }

    public function setPrecioUnitario(?float $precio_unitario): static
    {
        $this->precio_unitario = $precio_unitario;

        return $this;
    }

    public function getIvaUnitario(): ?float
    {
        return $this->iva_unitario;
    }

    public function setIvaUnitario(?float $iva_unitario): static
    {
        $this->iva_unitario = $iva_unitario;

        return $this;
    }

    public function getSubtotalUnitario(): ?float
    {
        return $this->subtotal_unitario;
    }

    public function setSubtotalUnitario(?float $subtotal_unitario): static
    {
        $this->subtotal_unitario = $subtotal_unitario;

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

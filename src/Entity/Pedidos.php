<?php

namespace App\Entity;

use App\Repository\PedidosRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PedidosRepository::class)]
class Pedidos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDPEDIDO")]
    private ?int $id = null;


    #[ORM\Column(type: Types::DATETIME_MUTABLE,name:"FECHA_PEDIDO")]
    private ?\DateTimeInterface $fecha_pedido = null;

    #[ORM\ManyToOne(inversedBy: 'pedidos')]
    #[ORM\JoinColumn(nullable: false,name:"IDLOGIN",referencedColumnName:"IDLOGIN")]
    private ?Login $login = null;

    #[ORM\Column(length: 255, nullable: true,name:"NOMBRE_CLIENTE")]
    private ?string $customer = null;

    #[ORM\Column(length: 255, nullable: true,name:"DNI_CLIENTE")]
    private ?string $dni_customer = null;

    #[ORM\Column(length: 255, nullable: true,name:"CELULAR_CLIENTE")]
    private ?string $celular_customer = null;

    #[ORM\Column(length: 255, nullable: true,name:"CODIGO_POSTAL_CLIENTE")]
    private ?string $codigo_postal_customer = null;

    #[ORM\Column(length: 255, nullable: true,name:"CIUDAD_CLIENTE")]
    private ?string $customer_city = null;


    #[ORM\OneToMany(mappedBy: 'pedido', targetEntity: DetallePedido::class, orphanRemoval: true)]
    private Collection $detallePedidos;

    #[ORM\Column(length: 255,name:"NUMERO_PEDIDO",unique:true)]
    private ?string $numero_pedido = null;

    #[ORM\Column(nullable: true,name:"REFERENCIA_PEDIDO")]
    private ?string $referencia_pedido = null;

    #[ORM\ManyToOne(inversedBy: 'pedidos')]
    #[ORM\JoinColumn(nullable: true,name:"IDFACTURA",referencedColumnName:"IDFACTURA")]
    private ?Factura $factura = null;

    #[ORM\Column(length: 255, nullable: true,name:"ESTADO_PAGO")]
    private ?string $estado = null;

    #[ORM\Column(length: 255, nullable: true, name:"PEDIDO_AUTORIZACION")]
    private ?string $autorizacion = null;

    #[ORM\Column(nullable: true,name:"ID_SERVIENTREGA")]
    private ?int $id_direccion = null;

    #[ORM\Column(length: 255, nullable: true,name:"DIRECCION_PRINCIPAL_PEDIDO")]
    private ?string $direccion_principal = null;

    #[ORM\Column(length: 255, nullable: true,name:"DIRECCION_SECUNDARIA_PEDIDO")]
    private ?string $direccion_secundaria = null;

    #[ORM\Column(length: 255, nullable: true,name:"UBICACION_REFERENCIA_PEDIDO")]
    private ?string $ubicacion_referencia = null;

    #[ORM\ManyToOne(inversedBy: 'pedidos')]
    #[ORM\JoinColumn(nullable: true,name:"IDESTADO_ENVIO",referencedColumnName:"IDESTADO")]
    private ?Estados $estado_envio = null;

    #[ORM\OneToMany(mappedBy: 'pedido', targetEntity: Servientrega::class, orphanRemoval: true)]
    private Collection $servientregas;

    #[ORM\ManyToOne(inversedBy: 'pedidos_retiro')]
    #[ORM\JoinColumn(nullable: true,name:"IDESTADO_RETIRO", referencedColumnName:"IDESTADO")]
    private ?Estados $estado_retiro = null;

    #[ORM\OneToMany(mappedBy: 'pedido', targetEntity: ProductosComentarios::class, orphanRemoval: true)]
    private Collection $productosComentarios;

    #[ORM\Column(length: 255, nullable: true,name:"TIPO_ENVIO")]
    private ?string $tipo_envio = null;

    #[ORM\Column(length: 255, nullable: false,name:"N_VENTA")]
    private ?string $n_venta = null;

    #[ORM\ManyToOne(inversedBy: 'pedidos')]
    #[ORM\JoinColumn(nullable: true,name:"IDMETODOPAGO",referencedColumnName:"IDMETODOPAGO")]
    private ?MetodosPago $metodo_pago = null;

    #[ORM\ManyToOne(inversedBy: 'pedidos')]
    #[ORM\JoinColumn(nullable: true, name:"IDCUPON",referencedColumnName:"IDCUPON")]
    private ?Cupon $cupon = null;

    #[ORM\Column(length: 255, nullable: true,name:"PROVINCIA_CLIENTE")]
    private ?string $provincia = null;

    #[ORM\Column(length: 255, nullable: true,name:"REGION_CLIENTE")]
    private ?string $region = null;

    #[ORM\ManyToOne(inversedBy: 'pedidos')]
    #[ORM\JoinColumn(nullable: true, name:"IDTIENDA", referencedColumnName:"IDTIENDA")]
    private ?Tiendas $tienda = null;

    #[ORM\ManyToOne(inversedBy: 'pedidos')]
    #[ORM\JoinColumn(nullable: true,name:"ID_METODOENVIO",referencedColumnName:"ID_METODOENVIO")]
    private ?MetodosEnvio $metodo_envio = null;

    #[ORM\Column(nullable: true,name:"PEDIDO_SUBTOTAL")]
    private ?float $subtotal = null;

    #[ORM\Column(nullable: true,name:"PEDIDO_IVA")]
    private ?float $iva = null;

    #[ORM\Column(nullable: true,name:"SUBTOTAL_MAS_IVA")]
    private ?float $total = null;

    #[ORM\Column(nullable: true,name:"PEDIDO_COSTO_ENVIO")]
    private ?float $costo_envio = null;

    #[ORM\Column(nullable: true,name:"COMISION_PAYPAL")]
    private ?float $comision_paypal = null;

    #[ORM\Column(nullable: true,name:"PEDIDO_TOTAL_FINAL")]
    private ?float $total_final = null;

    #[ORM\Column(nullable: true,name:"PEDIDO_DESCUENTO_CUPON")]
    private ?float $descuento_cupon = null;

    #[ORM\Column(nullable: true,name:"SUBTOTAL_ORIGINAL")]
    private ?float $subtotal_original = null;

    #[ORM\Column(nullable: true, name:"LATITUD_USUARIO")]
    private ?float $latitud = null;

    #[ORM\Column(nullable: true, name:"LONGITUD_USUARIO")]
    private ?float $longitud = null;

    #[ORM\Column(nullable: true,name:"GUIA_CONTADOR")]
    private ?int $guia_contador = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name:"FECHA_PAGO")]
    private ?\DateTimeInterface $fecha_pago = null;


    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name:"FECHA_RECHAZO_PAGO")]
    private ?\DateTimeInterface $fecha_rechazo = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name:"FECHA_LISTO_PARA_RETIRAR_ADOMICILIO")]
    private ?\DateTimeInterface $fecha_retirar_adomicilio = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name:"FECHA_RETIRO_ADOMICILIO")]
    private ?\DateTimeInterface $fecha_retiro_adomicilio = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true,name:'FECHA_EN_CAMINO')]
    private  ?\DateTimeInterface $fecha_en_camino = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name:"FECHA_ENTREGADO_ADOMICILIO")]
    private ?\DateTimeInterface $fecha_entrega_adomicilio = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name:"FECHA_LISTO_PARA_RETIRAR_FISICO")]
    private ?\DateTimeInterface $fecha_retirar_fisico = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name:"FECHA_ENTREGO_FISICO")]
    private ?\DateTimeInterface $fecha_entrego_fisico = null;

    #[ORM\Column(nullable: true,name:"PORCENTAJE_IVA")]
    private ?float $iva_aplicado = null;

    #[ORM\Column(nullable: true,name:"SUBTOTAL_ENVIO")]
    private ?float $subtotal_envio = null;

    #[ORM\Column(nullable: true,name:"IVA_ENVIO")]
    private ?float $iva_envio = null;

    #[ORM\ManyToOne(inversedBy: 'pedidos')]
    #[ORM\JoinColumn(nullable: true, name:"ID_REGATEO", referencedColumnName:"ID_REGATEO")]
    private ?Regateos $regateo = null;

    #[ORM\Column(length: 255, nullable: true,name:"URL_PAGO")]
    private ?string $url_pago = null;

    #[ORM\Column(length: 255, nullable: true,name:"REFERENCIA_INTERNA_PEDIDO")]
    private ?string $referencia_interna = null;

    #[ORM\Column(length: 255, nullable: true,name:"CLAVE_FACTURADOR")]
    private ?string $clave_facturador = null;

    #[ORM\Column(length: 300, nullable: true,name:"ESTADO_FACTURADOR")]
    private ?string $estado_facturador = null;

    #[ORM\Column(length: 255, nullable: true,name:"NUMERO_FACTURA")]
    private ?string $n_factura = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true,name:"FECHA_FACTURACION")]
    private ?\DateTimeInterface $fecha_facturacion = null;

    #[ORM\Column(nullable: true, name:"MONTO_SALDO")]
    private ?float $monto_saldo = null;

    #[ORM\Column(nullable: true, name:"MONTO_PASARELA")]
    private ?float $monto_pasarela = null;

    #[ORM\Column(nullable: true, name:"PAGO_MIXTO")]
    private ?bool $pago_mixto = false;
    public function __construct()
    {
        $this->fecha_pedido= new DateTime();
        $this->detallePedidos = new ArrayCollection();
        $this->servientregas = new ArrayCollection();
        $this->productosComentarios = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getFechaPedido(): ?\DateTimeInterface
    {
        return $this->fecha_pedido;
    }

    public function setFechaPedido(\DateTimeInterface $fecha_pedido): static
    {
        $this->fecha_pedido = $fecha_pedido;

        return $this;
    }

    public function getLogin(): ?Login
    {
        return $this->login;
    }

    public function setLogin(?Login $login): static
    {
        $this->login = $login;

        return $this;
    }




    public function getCustomer(): ?string
    {
        return $this->customer;
    }

    public function setCustomer(?string $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getDniCustomer(): ?string
    {
        return $this->dni_customer;
    }

    public function setDniCustomer(?string $dni_customer): static
    {
        $this->dni_customer = $dni_customer;

        return $this;
    }

    public function getCelularCustomer(): ?string
    {
        return $this->celular_customer;
    }

    public function setCelularCustomer(?string $celular_customer): static
    {
        $this->celular_customer = $celular_customer;

        return $this;
    }

    public function getCodigoPostalCustomer(): ?string
    {
        return $this->codigo_postal_customer;
    }

    public function setCodigoPostalCustomer(?string $codigo_postal_customer): static
    {
        $this->codigo_postal_customer = $codigo_postal_customer;

        return $this;
    }

    public function getCustomerCity(): ?string
    {
        return $this->customer_city;
    }

    public function setCustomerCity(?string $customer_city): static
    {
        $this->customer_city = $customer_city;

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
            $detallePedido->setPedido($this);
        }

        return $this;
    }

    public function removeDetallePedido(DetallePedido $detallePedido): static
    {
        if ($this->detallePedidos->removeElement($detallePedido)) {
            // set the owning side to null (unless already changed)
            if ($detallePedido->getPedido() === $this) {
                $detallePedido->setPedido(null);
            }
        }

        return $this;
    }

    public function getNumeroPedido(): ?string
    {
        return $this->numero_pedido;
    }

    public function setNumeroPedido(string $numero_pedido): static
    {
        $this->numero_pedido = $numero_pedido;

        return $this;
    }

    public function getReferenciaPedido(): ?string
    {
        return $this->referencia_pedido;
    }

    public function setReferenciaPedido(?string $referencia_pedido): static
    {
        $this->referencia_pedido = $referencia_pedido;

        return $this;
    }

    public function getFactura(): ?Factura
    {
        return $this->factura;
    }

    public function setFactura(?Factura $factura): static
    {
        $this->factura = $factura;

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

    public function getAutorizacion(): ?string
    {
        return $this->autorizacion;
    }

    public function setAutorizacion(?string $autorizacion): static
    {
        $this->autorizacion = $autorizacion;

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

    public function getDireccionPrincipal(): ?string
    {
        return $this->direccion_principal;
    }

    public function setDireccionPrincipal(?string $direccion_principal): static
    {
        $this->direccion_principal = $direccion_principal;

        return $this;
    }

    public function getDireccionSecundaria(): ?string
    {
        return $this->direccion_secundaria;
    }

    public function setDireccionSecundaria(?string $direccion_secundaria): static
    {
        $this->direccion_secundaria = $direccion_secundaria;

        return $this;
    }


    public function getUbicacionReferencia(): ?string
    {
        return $this->ubicacion_referencia;
    }

    public function setUbicacionReferencia(?string $ubicacion_referencia): static
    {
        $this->ubicacion_referencia = $ubicacion_referencia;

        return $this;
    }

    public function getEstadoEnvio(): ?Estados
    {
        return $this->estado_envio;
    }

    public function setEstadoEnvio(?Estados $estado_envio): static
    {
        $this->estado_envio = $estado_envio;

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
            $servientrega->setPedido($this);
        }

        return $this;
    }

    public function removeServientrega(Servientrega $servientrega): static
    {
        if ($this->servientregas->removeElement($servientrega)) {
            // set the owning side to null (unless already changed)
            if ($servientrega->getPedido() === $this) {
                $servientrega->setPedido(null);
            }
        }

        return $this;
    }

    public function getEstadoRetiro(): ?Estados
    {
        return $this->estado_retiro;
    }

    public function setEstadoRetiro(?Estados $estado_retiro): static
    {
        $this->estado_retiro = $estado_retiro;

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
            $productosComentario->setPedido($this);
        }

        return $this;
    }

    public function removeProductosComentario(ProductosComentarios $productosComentario): static
    {
        if ($this->productosComentarios->removeElement($productosComentario)) {
            // set the owning side to null (unless already changed)
            if ($productosComentario->getPedido() === $this) {
                $productosComentario->setPedido(null);
            }
        }

        return $this;
    }

    public function getTipoEnvio(): ?string
    {
        return $this->tipo_envio;
    }

    public function setTipoEnvio(?string $tipo_envio): static
    {
        $this->tipo_envio = $tipo_envio;

        return $this;
    }

    public function getNVenta(): ?string
    {
        return $this->n_venta;
    }

    public function setNVenta(?string $n_venta): static
    {
        $this->n_venta = $n_venta;

        return $this;
    }

    public function getMetodoPago(): ?MetodosPago
    {
        return $this->metodo_pago;
    }

    public function setMetodoPago(?MetodosPago $metodo_pago): static
    {
        $this->metodo_pago = $metodo_pago;

        return $this;
    }

    public function getCupon(): ?Cupon
    {
        return $this->cupon;
    }

    public function setCupon(?Cupon $cupon): static
    {
        $this->cupon = $cupon;

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

    public function getTienda(): ?Tiendas
    {
        return $this->tienda;
    }

    public function setTienda(?Tiendas $tienda): static
    {
        $this->tienda = $tienda;

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

    public function getSubtotal(): ?float
    {
        return $this->subtotal;
    }

    public function setSubtotal(?float $subtotal): static
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    public function getIva(): ?float
    {
        return $this->iva;
    }

    public function setIva(?float $iva): static
    {
        $this->iva = $iva;

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

    public function getCostoEnvio(): ?float
    {
        return $this->costo_envio;
    }

    public function setCostoEnvio(?float $costo_envio): static
    {
        $this->costo_envio = $costo_envio;

        return $this;
    }

    public function getComisionPaypal(): ?float
    {
        return $this->comision_paypal;
    }

    public function setComisionPaypal(?float $comision_paypal): static
    {
        $this->comision_paypal = $comision_paypal;

        return $this;
    }

    public function getTotalFinal(): ?float
    {
        return $this->total_final;
    }

    public function setTotalFinal(?float $total_final): static
    {
        $this->total_final = $total_final;

        return $this;
    }

    public function getDescuentoCupon(): ?float
    {
        return $this->descuento_cupon;
    }

    public function setDescuentoCupon(?float $descuento_cupon): static
    {
        $this->descuento_cupon = $descuento_cupon;

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

    public function getGuiaContador(): ?int
    {
        return $this->guia_contador;
    }

    public function setGuiaContador(?int $guia_contador): static
    {
        $this->guia_contador = $guia_contador;

        return $this;
    }

    public function getFechaPago(): ?\DateTimeInterface
    {
        return $this->fecha_pago;
    }

    public function setFechaPago(?\DateTimeInterface $fecha_pago): static
    {
        $this->fecha_pago = $fecha_pago;

        return $this;
    }


    public function getFechaRechazo(): ?\DateTimeInterface
    {
        return $this->fecha_rechazo;
    }

    public function setFechaRechazo(?\DateTimeInterface $fecha_rechazo): static
    {
        $this->fecha_rechazo = $fecha_rechazo;

        return $this;
    }

    public function getFechaRetirarAdomicilio(): ?\DateTimeInterface
    {
        return $this->fecha_retirar_adomicilio;
    }

    public function setFechaRetirarAdomicilio(?\DateTimeInterface $fecha_retirar_adomicilio): static
    {
        $this->fecha_retirar_adomicilio = $fecha_retirar_adomicilio;

        return $this;
    }

    public function getFechaRetiroAdomicilio(): ?\DateTimeInterface
    {
        return $this->fecha_retiro_adomicilio;
    }

    public function setFechaRetiroAdomicilio(?\DateTimeInterface $fecha_retiro_adomicilio): static
    {
        $this->fecha_retiro_adomicilio = $fecha_retiro_adomicilio;

        return $this;
    }

    public function getFechaEnCamino(): ?\DateTimeInterface
    {
        return $this->fecha_en_camino;
    }

    public function setFechaEnCamino(?\DateTimeInterface $fecha_en_camino): static
    {
        $this->fecha_en_camino = $fecha_en_camino;

        return $this;
    }

    public function getFechaEntregaAdomicilio(): ?\DateTimeInterface
    {
        return $this->fecha_entrega_adomicilio;
    }

    public function setFechaEntregaAdomicilio(?\DateTimeInterface $fecha_entrega_adomicilio): static
    {
        $this->fecha_entrega_adomicilio = $fecha_entrega_adomicilio;

        return $this;
    }

    public function getFechaRetirarFisico(): ?\DateTimeInterface
    {
        return $this->fecha_retirar_fisico;
    }

    public function setFechaRetirarFisico(?\DateTimeInterface $fecha_retirar_fisico): static
    {
        $this->fecha_retirar_fisico = $fecha_retirar_fisico;

        return $this;
    }

    public function getFechaEntregoFisico(): ?\DateTimeInterface
    {
        return $this->fecha_entrego_fisico;
    }

    public function setFechaEntregoFisico(?\DateTimeInterface $fecha_entrego_fisico): static
    {
        $this->fecha_entrego_fisico = $fecha_entrego_fisico;

        return $this;
    }

    public function getIvaAplicado(): ?float
    {
        return $this->iva_aplicado;
    }

    public function setIvaAplicado(?float $iva_aplicado): static
    {
        $this->iva_aplicado = $iva_aplicado;

        return $this;
    }

    public function getSubtotalEnvio(): ?float
    {
        return $this->subtotal_envio;
    }

    public function setSubtotalEnvio(?float $subtotal_envio): static
    {
        $this->subtotal_envio = $subtotal_envio;

        return $this;
    }

    public function getIvaEnvio(): ?float
    {
        return $this->iva_envio;
    }

    public function setIvaEnvio(?float $iva_envio): static
    {
        $this->iva_envio = $iva_envio;

        return $this;
    }

    public function getRegateo(): ?Regateos
    {
        return $this->regateo;
    }

    public function setRegateo(?Regateos $regateo): static
    {
        $this->regateo = $regateo;

        return $this;
    }

    public function getUrlPago(): ?string
    {
        return $this->url_pago;
    }

    public function setUrlPago(?string $url_pago): static
    {
        $this->url_pago = $url_pago;

        return $this;
    }

    public function getReferenciaInterna(): ?string
    {
        return $this->referencia_interna;
    }

    public function setReferenciaInterna(?string $referencia_interna): static
    {
        $this->referencia_interna = $referencia_interna;

        return $this;
    }

    public function getClaveFacturador(): ?string
    {
        return $this->clave_facturador;
    }

    public function setClaveFacturador(?string $clave_facturador): static
    {
        $this->clave_facturador = $clave_facturador;

        return $this;
    }

    public function getEstadoFacturador(): ?string
    {
        return $this->estado_facturador;
    }

    public function setEstadoFacturador(?string $estado_facturador): static
    {
        $this->estado_facturador = $estado_facturador;

        return $this;
    }

    public function getNFactura(): ?string
    {
        return $this->n_factura;
    }

    public function setNFactura(?string $n_factura): static
    {
        $this->n_factura = $n_factura;

        return $this;
    }

    public function getFechaFacturacion(): ?\DateTimeInterface
    {
        return $this->fecha_facturacion;
    }

    public function setFechaFacturacion(?\DateTimeInterface $fecha_facturacion): static
    {
        $this->fecha_facturacion = $fecha_facturacion;

        return $this;
    }

    public function getMontoSaldo(): ?float
    {
        return $this->monto_saldo;
    }

    public function setMontoSaldo(?float $monto_saldo): static
    {
        $this->monto_saldo = $monto_saldo;

        return $this;
    }

    public function getMontoPasarela(): ?float
    {
        return $this->monto_pasarela;
    }

    public function setMontoPasarela(?float $monto_pasarela): static
    {
        $this->monto_pasarela = $monto_pasarela;

        return $this;
    }

    public function isPagoMixto(): ?bool
    {
        return $this->pago_mixto;
    }

    public function setPagoMixto(?bool $pago_mixto): static
    {
        $this->pago_mixto = $pago_mixto;

        return $this;
    }

    

}

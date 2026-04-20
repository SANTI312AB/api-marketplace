<?php

namespace App\Entity;

use App\Repository\ServientregaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: ServientregaRepository::class)]
class Servientrega
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDSERVIENTREGA")]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true,name:"N_PEDIDO")]
    private ?string $n_pedido = null;

    #[ORM\Column(nullable: true,name:"ID_CIUDAD_ENVIO")]
    private ?int $id_ciudad_envio = null;

    #[ORM\Column(length: 255, nullable: true,name:"CIUDAD_ENVIO")]
    private ?string $ciudad_envio = null;

    #[ORM\Column(length: 255, nullable: true,name:"DIRECCION_PRINCIPAL")]
    private ?string $direccion_principal = null;

    #[ORM\Column(length: 255, nullable: true,name:"DIRECCION_SECUNDARIA")]
    private ?string $direccion_secundaria = null;

    #[ORM\Column(length: 255, nullable: true,name:"CODIGO_POSTAL")]
    private ?string $codigo_postal = null;

    #[ORM\Column(length: 255, nullable: true,name:"UBICACION_REFENCIA")]
    private ?string $ubicacion_referencia = null;

    #[ORM\Column(length: 255, nullable: true,name:"NOMBRE")]
    private ?string $nombre = null;

    #[ORM\Column(length: 255, nullable: true,name:"APELLIDO")]
    private ?string $apellido = null;

    #[ORM\Column(length: 255, nullable: true,name:"DNI")]
    private ?string $dni = null;

    #[ORM\Column(length: 255, nullable: true,name:"CELULAR")]
    private ?string $ceular = null;

    #[ORM\Column(length: 255, nullable: true,name:"ID_CIUDAD_REMITE")]
    private ?string $id_ciudad_remite = null;

    #[ORM\Column(length: 255, nullable: true,name:"CIUDAD_REMITE")]
    private ?string $ciudad_remite = null;

    #[ORM\Column(length: 255, nullable: true,name:"DIRECCION_REMITE")]
    private ?string $direccion_remite = null;

    #[ORM\Column(length: 255, nullable: true,name:"NOMBRE_VENDEDOR")]
    private ?string $nombre_vendedor = null;

    #[ORM\Column(length: 255, nullable: true,name:"APELLIDO_VENDEDOR")]
    private ?string $apellido_vendedor = null;

    #[ORM\Column(length: 255, nullable: true,name:"DNI_VENDEDOR")]
    private ?string $dni_vendedor = null;

    #[ORM\Column(length: 255, nullable: true,name:"CEDULA_VENDEDOR")]
    private ?string $celular_vendedor = null;

    #[ORM\Column(nullable: true,name:"CODIGO_SERVIENTREGA")]
    private ?int $codigo_servientrega = null;

    #[ORM\Column(length: 255, nullable: true,name:"MSJ_SERVIENTREGA")]
    private ?string $msj_servientrega = null;

    #[ORM\Column(nullable: true,name:"PESO_TOTAL")]
    private ?float $peso_total = null;

    #[ORM\Column(nullable: true,name:"CANTIDAD_TOTAL")]
    private ?int $cantidad_total = null;

    #[ORM\Column(nullable: true,name:"VALOR_TOTAL")]
    private ?float $valor_total = null;

    #[ORM\Column(type: Types::TEXT, nullable: true,name:"PRODUCTOS")]
    private ?string $productos = null;

    #[ORM\ManyToOne(inversedBy: 'servientregas')]
    #[ORM\JoinColumn(nullable: true,name:"IDPEDIDO", referencedColumnName:"IDPEDIDO")]
    private ?Pedidos $pedido = null;

    #[ORM\Column(length: 255, nullable: true,name:"RESPONSE_SERVIENTREGA")]
    private ?string $response_servientrega = null;

    #[ORM\ManyToOne(inversedBy: 'servientregas')]
    #[ORM\JoinColumn(nullable: true,name:"IDTIENDA",referencedColumnName:"IDTIENDA")]
    private ?Tiendas $tienda = null;

    #[ORM\Column(nullable: true, name:"VALOR_ASEGURADO")]
    private ?float $valor_seguro = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name:"FECHA_PEDIDO")]
    private ?\DateTimeInterface $fecha_pedido = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true,name:"FECHA_REGISTRO")]
    private ?\DateTimeInterface $fecha_registro = null;


    #[ORM\Column(nullable: true,name:"EXCEL_GENERARO")]
    private ?bool $excel_generado = false;

    #[ORM\ManyToOne(inversedBy: 'servientregas')]
    #[ORM\JoinColumn(nullable: true,name:"ID_METODOENVIO", referencedColumnName:"ID_METODOENVIO")]
    private ?MetodosEnvio $metodo_envio = null;

    #[ORM\Column(nullable: true,name:"GUIA_ANULADA")]
    private ?bool $anulado = null;

    #[ORM\Column(length: 300, nullable: true,name:" GUIA_OBSERVACION")]
    private ?string $observacion = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, name:"ARCHIVO")]
    private ?string $archivo = null;

      
    public function __construct()
    {
      
        $this->fecha_registro = new \DateTime();
        $this->anulado= false;

    }



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNPedido(): ?string
    {
        return $this->n_pedido;
    }

    public function setNPedido(?string $n_pedido): static
    {
        $this->n_pedido = $n_pedido;

        return $this;
    }

    public function getIdCiudadEnvio(): ?int
    {
        return $this->id_ciudad_envio;
    }

    public function setIdCiudadEnvio(?int $id_ciudad_envio): static
    {
        $this->id_ciudad_envio = $id_ciudad_envio;

        return $this;
    }

    public function getCiudadEnvio(): ?string
    {
        return $this->ciudad_envio;
    }

    public function setCiudadEnvio(?string $ciudad_envio): static
    {
        $this->ciudad_envio = $ciudad_envio;

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

    public function getCodigoPostal(): ?string
    {
        return $this->codigo_postal;
    }

    public function setCodigoPostal(?string $codigo_postal): static
    {
        $this->codigo_postal = $codigo_postal;

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

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(?string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getApellido(): ?string
    {
        return $this->apellido;
    }

    public function setApellido(?string $apellido): static
    {
        $this->apellido = $apellido;

        return $this;
    }

    public function getDni(): ?string
    {
        return $this->dni;
    }

    public function setDni(?string $dni): static
    {
        $this->dni = $dni;

        return $this;
    }

    public function getCeular(): ?string
    {
        return $this->ceular;
    }

    public function setCeular(?string $ceular): static
    {
        $this->ceular = $ceular;

        return $this;
    }

    public function getIdCiudadRemite(): ?string
    {
        return $this->id_ciudad_remite;
    }

    public function setIdCiudadRemite(?string $id_ciudad_remite): static
    {
        $this->id_ciudad_remite = $id_ciudad_remite;

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

    public function getNombreVendedor(): ?string
    {
        return $this->nombre_vendedor;
    }

    public function setNombreVendedor(?string $nombre_vendedor): static
    {
        $this->nombre_vendedor = $nombre_vendedor;

        return $this;
    }

    public function getApellidoVendedor(): ?string
    {
        return $this->apellido_vendedor;
    }

    public function setApellidoVendedor(?string $apellido_vendedor): static
    {
        $this->apellido_vendedor = $apellido_vendedor;

        return $this;
    }

    public function getDniVendedor(): ?string
    {
        return $this->dni_vendedor;
    }

    public function setDniVendedor(?string $dni_vendedor): static
    {
        $this->dni_vendedor = $dni_vendedor;

        return $this;
    }

    public function getCelularVendedor(): ?string
    {
        return $this->celular_vendedor;
    }

    public function setCelularVendedor(?string $celular_vendedor): static
    {
        $this->celular_vendedor = $celular_vendedor;

        return $this;
    }

    public function getCodigoServientrega(): ?int
    {
        return $this->codigo_servientrega;
    }

    public function setCodigoServientrega(?int $codigo_servientrega): static
    {
        $this->codigo_servientrega = $codigo_servientrega;

        return $this;
    }

    public function getMsjServientrega(): ?string
    {
        return $this->msj_servientrega;
    }

    public function setMsjServientrega(?string $msj_servientrega): static
    {
        $this->msj_servientrega = $msj_servientrega;

        return $this;
    }

    public function getPesoTotal(): ?float
    {
        return $this->peso_total;
    }

    public function setPesoTotal(?float $peso_total): static
    {
        $this->peso_total = $peso_total;

        return $this;
    }

    public function getCantidadTotal(): ?int
    {
        return $this->cantidad_total;
    }

    public function setCantidadTotal(?int $cantidad_total): static
    {
        $this->cantidad_total = $cantidad_total;

        return $this;
    }

    public function getValorTotal(): ?float
    {
        return $this->valor_total;
    }

    public function setValorTotal(?float $valor_total): static
    {
        $this->valor_total = $valor_total;

        return $this;
    }

    public function getProductos(): ?string
    {
        return $this->productos;
    }

    public function setProductos(?string $productos): static
    {
        $this->productos = $productos;

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

    public function getResponseServientrega(): ?string
    {
        return $this->response_servientrega;
    }

    public function setResponseServientrega(?string $response_servientrega): static
    {
        $this->response_servientrega = $response_servientrega;

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

    public function getValorSeguro(): ?float
    {
        return $this->valor_seguro;
    }

    public function setValorSeguro(?float $valor_seguro): static
    {
        $this->valor_seguro = $valor_seguro;

        return $this;
    }

    public function getFechaPedido(): ?\DateTimeInterface
    {
        return $this->fecha_pedido;
    }

    public function setFechaPedido(?\DateTimeInterface $fecha_pedido): static
    {
        $this->fecha_pedido = $fecha_pedido;

        return $this;
    }



    public function isExcelGenerado(): ?bool
    {
        return $this->excel_generado;
    }

    public function getFechaRegistro(): ?\DateTimeInterface
    {
        return $this->fecha_registro;
    }

    public function setFechaRegistro(?\DateTimeInterface $fecha_registro): static
    {
        $this->fecha_registro = $fecha_registro;

        return $this;
    }


    public function setExcelGenerado(?bool $excel_generado): static
    {
        $this->excel_generado = $excel_generado;

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

    public function isAnulado(): ?bool
    {
        return $this->anulado;
    }

    public function setAnulado(?bool $anulado): static
    {
        $this->anulado = $anulado;

        return $this;
    }

    public function getObservacion(): ?string
    {
        return $this->observacion;
    }

    public function setObservacion(?string $observacion): static
    {
        $this->observacion = $observacion;

        return $this;
    }

    public function getArchivo(): ?string
    {
        return $this->archivo;
    }

    public function setArchivo(?string $archivo): static
    {
        $this->archivo = $archivo;

        return $this;
    }

   

}

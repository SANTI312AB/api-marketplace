<?php

namespace App\Entity;

use App\Repository\ProductosComentariosRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductosComentariosRepository::class)]
class ProductosComentarios
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDCOMENTARIO")]
    private ?int $id = null;

    #[ORM\Column(length: 300, nullable:true,name:"COMENTARIO_PRODUCTO")]
    private ?string $comentario = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true,name:"FECHA_COMENTARIO")]
    private ?\DateTimeInterface $fecha = null;

    #[ORM\ManyToOne(inversedBy: 'productosComentarios')]
    #[ORM\JoinColumn(nullable: false, name:"IDPRODUCTO",referencedColumnName:"IDPRODUCTO")]
    private ?Productos $productos = null;

    #[ORM\ManyToOne(inversedBy: 'productosComentarios')]
    #[ORM\JoinColumn(nullable: false, name:"IDLOGIN",referencedColumnName:"IDLOGIN")]
    private ?Login $login = null;

    #[ORM\Column(nullable: true, name:"PRODUCTO_CALIFICACION")]
    private ?int $calificacion = null;

    #[ORM\ManyToOne(inversedBy: 'productosComentarios')]
    #[ORM\JoinColumn(nullable: false,name:"IDPEDIDO",referencedColumnName:"IDPEDIDO")]
    private ?Pedidos $pedido = null;

    public function __construct()
    {

        $this->fecha = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getComentario(): ?string
    {
        return $this->comentario;
    }

    public function setComentario(string $comentario): static
    {
        $this->comentario = $comentario;

        return $this;
    }

    public function getFecha(): ?\DateTimeInterface
    {
        return $this->fecha;
    }

    public function setFecha(?\DateTimeInterface $fecha): static
    {
        $this->fecha = $fecha;

        return $this;
    }

    public function getProductos(): ?Productos
    {
        return $this->productos;
    }

    public function setProductos(?Productos $productos): static
    {
        $this->productos = $productos;

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

    public function getCalificacion(): ?int
    {
        return $this->calificacion;
    }

    public function setCalificacion(?int $calificacion): static
    {
        $this->calificacion = $calificacion;

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
}

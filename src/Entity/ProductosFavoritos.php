<?php

namespace App\Entity;

use App\Repository\ProductosFavoritosRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductosFavoritosRepository::class)]
class ProductosFavoritos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDFAVORITO")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'productosFavoritos')]
    #[ORM\JoinColumn(nullable: false,name:"IDLOGIN", referencedColumnName:"IDLOGIN")]
    private ?Login $login = null;

    #[ORM\ManyToOne(inversedBy: 'productosFavoritos')]
    #[ORM\JoinColumn(nullable: false,name:"IDPRODUCTO",referencedColumnName:"IDPRODUCTO")]
    private ?Productos $producto = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE,name:"FECHA_FAVORITO")]
    private ?\DateTimeInterface $fecha_favorita = null;

    public function __construct()
    {
        $this->fecha_favorita = new \DateTime();
    }

    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFechaFavorita(): ?\DateTimeInterface
    {
        return $this->fecha_favorita;
    }

    public function setFechaFavorita(\DateTimeInterface $fecha_favorita): static
    {
        $this->fecha_favorita = $fecha_favorita;

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

    public function getProducto(): ?Productos
    {
        return $this->producto;
    }

    public function setProducto(?Productos $producto): static
    {
        $this->producto = $producto;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\SlidersRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SlidersRepository::class)]
class Sliders
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDSLIDER")]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true, name:"MOBILE_SLIDER_URL")]
    private ?string $movil_slider = null;

    #[ORM\Column(length: 255, nullable: true, name:"DESKTOP_SLIDER_URL")]
    private ?string $desktop_slider = null;

    #[ORM\Column(length: 255, nullable: true,name:"HREF_SLIDER")]
    private ?string $href_slider = null;

    #[ORM\Column(name:"ORDER_SLIDER")]
    private ?int $order_slider = null;

    #[ORM\Column(nullable: true, name:"IDTIENDA")]
    private ?int $id_tienda = null;

    #[ORM\Column(length: 500, nullable: true, name:"SLUG_PRODUCTO_DESTACADO")]
    private ?string $slug_producto_destacado = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMovilSlider(): ?string
    {
        return $this->movil_slider;
    }

    public function setMovilSlider(?string $movil_slider): static
    {
        $this->movil_slider = $movil_slider;

        return $this;
    }

    public function getDesktopSlider(): ?string
    {
        return $this->desktop_slider;
    }

    public function setDesktopSlider(?string $desktop_slider): static
    {
        $this->desktop_slider = $desktop_slider;

        return $this;
    }

    public function getHrefSlider(): ?string
    {
        return $this->href_slider;
    }

    public function setHrefSlider(?string $href_slider): static
    {
        $this->href_slider = $href_slider;

        return $this;
    }

    public function getOrderSlider(): ?int
    {
        return $this->order_slider;
    }

    public function setOrderSlider(int $order_slider): static
    {
        $this->order_slider = $order_slider;

        return $this;
    }

    public function getIdTienda(): ?int
    {
        return $this->id_tienda;
    }

    public function setIdTienda(?int $id_tienda): static
    {
        $this->id_tienda = $id_tienda;

        return $this;
    }

    public function getSlugProductoDestacado(): ?string
    {
        return $this->slug_producto_destacado;
    }

    public function setSlugProductoDestacado(?string $slug_producto_destacado): static
    {
        $this->slug_producto_destacado = $slug_producto_destacado;

        return $this;
    }
}

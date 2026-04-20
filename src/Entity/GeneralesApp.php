<?php

namespace App\Entity;

use App\Repository\GeneralesAppRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GeneralesAppRepository::class)]
#[ORM\Table(name:"generales_app")]
class GeneralesApp
{
    #[ORM\Column(name:"IDGENERALES_APP" , type:"integer", nullable:false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy:"IDENTITY")]
    private $idGeneralesApp;


    # @var string
    #[ORM\Column(name:"VALOR_GENERAL", type:"string", length:300, nullable:true)]
    private $valorGeneral;


    # @var string
    #[ORM\Column(name:"ATRIBUTO_GENERAL", type:"string", length:45, nullable:true)]
    private $atributoGeneral;


    # @var string
    #[ORM\Column(name:"TIPO_AMBIENTE", type:"string", length:5, nullable:true)]
    private $tipoGeneral;


    #[ORM\Column(length: 255, nullable: true,name:"TIPO_SERVICIO")]
    private ?string $tipo = null;

    #[ORM\Column(length: 255, nullable: true,name:"NOMBRE")]
    private ?string $nombre = null;

    /**
     * GeneralesApp constructor.
     * @param $idGeneralesApp
     * @param $valorGeneral
     * @param $atributoGeneral
     * @param $tipoGeneral
     */
    public function __construct($idGeneralesApp, $idEstado, $nombreGeneral, $valorGeneral, $atributoGeneral, $tipoGeneral)
    {
        $this->idGeneralesApp = $idGeneralesApp;
        $this->valorGeneral = $valorGeneral;
        $this->atributoGeneral = $atributoGeneral;
        $this->tipoGeneral = $tipoGeneral;
    }

    /**
     * @return mixed
     */
    public function getIdGeneralesApp()
    {
        return $this->idGeneralesApp;
    }

    /**
     * @param mixed $idGeneralesApp
     */
    public function setIdGeneralesApp($idGeneralesApp)
    {
        $this->idGeneralesApp = $idGeneralesApp;
    }


    /**
     * @return mixed
     */
    public function getValorGeneral()
    {
        return $this->valorGeneral;
    }

    /**
     * @param mixed $valorGeneral
     */
    public function setValorGeneral($valorGeneral)
    {
        $this->valorGeneral = $valorGeneral;
    }

    /**
     * @return mixed
     */
    public function getAtributoGeneral()
    {
        return $this->atributoGeneral;
    }

    /**
     * @param mixed $atributoGeneral
     */
    public function setAtributoGeneral($atributoGeneral)
    {
        $this->atributoGeneral = $atributoGeneral;
    }

    /**
     * @return mixed
     */
    public function getTipoGeneral()
    {
        return $this->tipoGeneral;
    }

    /**
     * @param mixed $tipoGeneral
     */
    public function setTipoGeneral($tipoGeneral)
    {
        $this->tipoGeneral = $tipoGeneral;
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

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(?string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }





}

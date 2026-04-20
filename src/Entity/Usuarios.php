<?php

namespace App\Entity;

use App\Repository\UsuariosRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: UsuariosRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'El email ya está en uso.')]
#[UniqueEntity(fields: ['celular'], message: 'El numero de celular ya está en uso.')]
#[UniqueEntity(fields: ['telefono'], message: 'El numero de telefono ya está en uso.')]
#[UniqueEntity(fields: ['dni'], message: 'Este numero de cedula ya está en uso.')]
#[UniqueEntity(fields: ['username'], message: 'El nombre de usuario ya está en uso. ')]
class Usuarios 

{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDUSUARIO")]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'usuarios', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false,name:"IDLOGIN",referencedColumnName:"IDLOGIN")]
    private ?Login $login = null;

    #[ORM\ManyToOne(inversedBy: 'usuarios')]
    #[ORM\JoinColumn(nullable: false, name:"IDPAIS",referencedColumnName:"IDPAIS")]
    private ?Pais $pais = null;

    #[ORM\Column(length: 30, nullable: true,unique: true,name:"DNI_USUARIO")]
    private ?string $dni = null;

    #[ORM\Column(length: 255, unique:true, name:"EMAIL_USUARIO")]
    private ?string $email = null;

    #[ORM\Column(length: 255,name:"NOMBRE_USUARIO")]
    private ?string $nombre = null;

    #[ORM\Column(length: 255,name:"APELLIDO_USUARIO" ,nullable:true )]
    private ?string $apellido = null;

    #[ORM\Column(nullable: true,unique:true,name:"TELEFONO_USUARIO")]
    private ?int $telefono = null;

    #[ORM\Column(length: 255, nullable: true,unique: true,name:"CELULAR_USUARIO")]
    private ?string $celular = null;
   
    #[ORM\Column(length: 120, nullable: true,name:"AVATAR_USUARIO")]
    private ?string $avatar = null;

    
    #[ORM\Column(length: 120, nullable: true,name:"GEO_USUARIO")]
    private ?string $geo = null;

    #[ORM\Column(length: 120, nullable: true, name:"SELFIE_USUARIO")]
    private ?string $selfie = null;

    #[ORM\Column(length: 255, nullable: true,name:"TIPO_DOCUMENTO_USUARIO")]
    private ?string $tipo_documento = null;
    

    #[ORM\Column(length: 255, nullable: true,name:"GENERO_USUARIO")]
    private ?string $genero = null;

    #[ORM\OneToMany(mappedBy: 'usuario', targetEntity: UsuariosDirecciones::class, orphanRemoval: true)]
    private Collection $usuariosDirecciones;

    #[ORM\ManyToOne(inversedBy: 'usuarios')]
    #[ORM\JoinColumn(nullable: true,name:"IDESTADO", referencedColumnName:"IDESTADO")]
    private ?Estados $estados = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true, name:"FECHA_NACIMIENTO_USUARIO")]
    private ?\DateTimeInterface $fecha_nacimiento = null;

    #[ORM\Column(length: 500, nullable: true,name:"USERNAME",unique:true)]
    private ?string $username = null;

    #[ORM\Column(length: 255, nullable: true, name:"USARIO_DOCUMENTO")]
    private ?string $foto_documento = null;

    #[ORM\Column(nullable: true,name:"USUARIO_HAS_VERIFIED")]
    private ?bool $has_verified = false;

    #[ORM\Column(nullable: true,name:"LIMITE_BIOMETRICO")]
    private ?int $limite_biometrico = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true,name:"FECHA_BIOMETRICO")]
    private ?\DateTimeInterface $fecha_biometrico = null;

    #[ORM\Column(nullable: true,name:"MINIMUM_PURCHASE_BIOMETRIC",options:["default"=>500])]
    private ?int $compra_minima_biometrico = null;

    #[ORM\Column(nullable: true,name:"USUARIO_REQUIERE_BIOMETRICO")]
    private ?bool $requiere_biometrico = null;

    #[ORM\Column(nullable: true, name:"DEFAULT_REFERIDO", options:["default"=>10])]
    private ?float $referido = null;


    public function __construct()
    {
        $this->compra_minima_biometrico = 500;
        $this->referido=10;
        $this->requiere_biometrico= true;
        $this->usuariosDirecciones = new ArrayCollection();
    }

    
   
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

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

  

    

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

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

    public function getGeo(): ?string
    {
        return $this->geo;
    }

    public function setGeo(?string $geo): static
    {
        $this->geo = $geo;

        return $this;
    }

    public function getSelfie(): ?string
    {
        return $this->selfie;
    }

    public function setSelfie(?string $selfie): static
    {
        $this->selfie = $selfie;

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

    public function getCelular(): ?string
    {
        return $this->celular;
    }

    public function setCelular(?string $celular): static
    {
        $this->celular = $celular;

        return $this;
    }

    public function getTelefono(): ?int
    {
        return $this->telefono;
    }

    public function setTelefono(?int $telefono): static
    {
        $this->telefono = $telefono;

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
            $usuariosDireccione->setUsuario($this);
        }

        return $this;
    }

    public function removeUsuariosDireccione(UsuariosDirecciones $usuariosDireccione): static
    {
        if ($this->usuariosDirecciones->removeElement($usuariosDireccione)) {
            // set the owning side to null (unless already changed)
            if ($usuariosDireccione->getUsuario() === $this) {
                $usuariosDireccione->setUsuario(null);
            }
        }

        return $this;
    }

    public function getTipoDocumento(): ?string
    {
        return $this->tipo_documento;
    }

    public function setTipoDocumento(?string $tipo_documento): static
    {
        $this->tipo_documento = $tipo_documento;

        return $this;
    }

    public function getGenero(): ?string
    {
        return $this->genero;
    }

    public function setGenero(?string $genero): static
    {
        $this->genero = $genero;

        return $this;
    }

    public function getPais(): ?Pais
    {
        return $this->pais;
    }

    public function setPais(?Pais $pais): static
    {
        $this->pais = $pais;

        return $this;
    }

    public function getEstados(): ?Estados
    {
        return $this->estados;
    }

    public function setEstados(?Estados $estados): static
    {
        $this->estados = $estados;

        return $this;
    }

    public function getFechaNacimiento(): ?\DateTimeInterface
    {
        return $this->fecha_nacimiento;
    }

    public function setFechaNacimiento(?\DateTimeInterface $fecha_nacimiento): static
    {
        $this->fecha_nacimiento = $fecha_nacimiento;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getFotoDocumento(): ?string
    {
        return $this->foto_documento;
    }

    public function setFotoDocumento(?string $foto_documento): static
    {
        $this->foto_documento = $foto_documento;

        return $this;
    }

    public function isHasVerified(): ?bool
    {
        return $this->has_verified;
    }

    public function setHasVerified(?bool $has_verified): static
    {
        $this->has_verified = $has_verified;

        return $this;
    }

    public function getLimiteBiometrico(): ?int
    {
        return $this->limite_biometrico;
    }

    public function setLimiteBiometrico(?int $limite_biometrico): static
    {
        $this->limite_biometrico = $limite_biometrico;

        return $this;
    }

    public function getFechaBiometrico(): ?\DateTimeInterface
    {
        return $this->fecha_biometrico;
    }

    public function setFechaBiometrico(?\DateTimeInterface $fecha_biometrico): static
    {
        $this->fecha_biometrico = $fecha_biometrico;

        return $this;
    }

    public function getCompraMinimaBiometrico(): ?int
    {
        return $this->compra_minima_biometrico;
    }

    public function setCompraMinimaBiometrico(?int $compra_minima_biometrico): static
    {
        $this->compra_minima_biometrico = $compra_minima_biometrico;

        return $this;
    }

    public function isRequiereBiometrico(): ?bool
    {
        return $this->requiere_biometrico;
    }

    public function setRequiereBiometrico(?bool $requiere_biometrico): static
    {
        $this->requiere_biometrico = $requiere_biometrico;

        return $this;
    }

    public function getReferido(): ?float
    {
        return $this->referido;
    }

    public function setReferido(?float $referido): static
    {
        $this->referido = $referido;

        return $this;
    }


}

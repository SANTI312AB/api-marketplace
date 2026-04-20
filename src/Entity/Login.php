<?php

namespace App\Entity;

use App\Repository\LoginRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: LoginRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'El email ya está en uso.')]
#[UniqueEntity(fields: ['username'], message: 'El nombre de usuario ya está en uso.')]
class Login implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"IDLOGIN")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'login')]
    #[ORM\JoinColumn(nullable:true,name:"IDESTADO", referencedColumnName:"IDESTADO")]
    private ?Estados $estados = null;

    #[ORM\ManyToOne(inversedBy: 'logins')]
    #[ORM\JoinColumn(nullable:true,name:"IDVERIFICACION", referencedColumnName:"IDESTADO")]
    private ?Estados $vericacion = null;

    #[ORM\Column(length: 255, unique: true,name:"EMAIL_LOGIN")]
    private ?string $email = null;

    #[ORM\Column(length: 500,unique: true,name:"USUARIO_LOGIN")]
    private ?string $username = null;


    /**
     * @var string The hashed password
     */
    #[ORM\Column(length: 200,nullable:true,name:"PASSWORD_LOGIN")]
    private ?string $password = null;



    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name:"LAST_LOGIN")]
    private ?\DateTimeInterface $last_login = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true,name:"FECHA_REGISTRO_LOGIN")]
    private ?\DateTimeInterface $fecha_registro = null;

    #[ORM\Column(name:"LOGIN_ROLES")]
    private array $roles = [];

    #[ORM\OneToOne(mappedBy: 'login', cascade: ['persist', 'remove'])]
    private ?Usuarios $usuarios = null;





    #[ORM\OneToOne(mappedBy: 'login', cascade: ['persist', 'remove'])]
    private ?Tiendas $tiendas = null;

    #[ORM\OneToMany(mappedBy: 'login', targetEntity: ProductosFavoritos::class, orphanRemoval: true)]
    private Collection $productosFavoritos;

    #[ORM\OneToMany(mappedBy: 'login', targetEntity: LogsApi::class, orphanRemoval: true)]
    private Collection $logsApis;

    #[ORM\OneToMany(mappedBy: 'login', targetEntity: Token::class, orphanRemoval: true)]
    private Collection $tokens;


    #[ORM\OneToMany(mappedBy: 'login', targetEntity: ProductosComentarios::class, orphanRemoval: true)]
    private Collection $productosComentarios;

    #[ORM\OneToMany(mappedBy: 'login', targetEntity: Carrito::class, orphanRemoval: true)]
    private Collection $carritos;

    #[ORM\OneToMany(mappedBy: 'login', targetEntity: Factura::class, orphanRemoval: true)]
    private Collection $facturas;

    #[ORM\OneToMany(mappedBy: 'login', targetEntity: Pedidos::class, orphanRemoval: true)]
    private Collection $pedidos;

    #[ORM\OneToMany(mappedBy: 'login', targetEntity: Preguntas::class, orphanRemoval: true)]
    private Collection $preguntas;

    #[ORM\OneToOne(mappedBy: 'login', cascade: ['persist', 'remove'])]
    private ?Ganancia $ganancia = null;

    #[ORM\OneToMany(mappedBy: 'login', targetEntity: Banco::class, orphanRemoval: true)]
    private Collection $bancos;

    #[ORM\OneToMany(mappedBy: 'login', targetEntity: Respuestas::class, orphanRemoval: true)]
    private Collection $respuestas;
    
    #[ORM\JoinTable(name: 'cupones_login')]
    #[ORM\JoinColumn(name: 'IDLOGIN', referencedColumnName: 'IDLOGIN')]
    #[ORM\InverseJoinColumn(name: 'IDCUPON', referencedColumnName: 'IDCUPON')]
    #[ORM\ManyToMany(targetEntity: Cupon::class, mappedBy: 'login')]
    private Collection $cupons;

    /**
     * @var Collection<int, Ofertas>
     */
    #[ORM\OneToMany(mappedBy: 'login', targetEntity: Ofertas::class, orphanRemoval: true)]
    private Collection $ofertas;

    #[ORM\Column(nullable: true, name:"TOKEN_VERSION",options:["default"=>1])]
    private ?int $version = 1;

    #[ORM\Column(length: 255, nullable: true, name:"GOOGLE_ID")]
    private ?string $googleId = null;

    #[ORM\Column(length: 255, nullable: true, name:"FACEBOOK_ID")]
    private ?string $facebook_id = null;

    #[ORM\Column(length: 255, nullable: true, name:"APPLE_ID")]
    private ?string $apple_id = null;

    #[ORM\OneToOne(mappedBy: 'login', cascade: ['persist', 'remove'])]
    private ?Saldo $saldo = null;

    /**
     * @var Collection<int, Regateos>
     */
    #[ORM\OneToMany(mappedBy: 'login', targetEntity: Regateos::class, orphanRemoval: true)]
    private Collection $regateos;


    
    public function getSalt()
    {
        // Implement the logic to return a salt
        // This method is not needed for modern hashing techniques like bcrypt
        // You can return null if you are not using this feature
        return null;
    }


    public function __construct()
    {
        $this->fecha_registro = new \DateTime();
        $this->productosFavoritos = new ArrayCollection();
        $this->logsApis = new ArrayCollection();
        $this->tokens = new ArrayCollection();
        $this->productosComentarios = new ArrayCollection();
        $this->carritos = new ArrayCollection();
        $this->facturas = new ArrayCollection();
        $this->pedidos = new ArrayCollection();
        $this->preguntas = new ArrayCollection();
        $this->bancos = new ArrayCollection();
        $this->respuestas = new ArrayCollection();
        $this->cupons = new ArrayCollection();
        $this->ofertas = new ArrayCollection();
        $this->saldos = new ArrayCollection();
        $this->regateos = new ArrayCollection();
    }



    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->last_login;
    }

    public function setLastLogin(?\DateTimeInterface $last_login): static
    {
        $this->last_login = $last_login;

        return $this;
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

    public function getUsuarios(): ?Usuarios
    {
        return $this->usuarios;
    }

    public function setUsuarios(Usuarios $usuarios): static
    {
        // set the owning side of the relation if necessary
        if ($usuarios->getLogin() !== $this) {
            $usuarios->setLogin($this);
        }

        $this->usuarios = $usuarios;

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



    public function getTiendas(): ?Tiendas
    {
        return $this->tiendas;
    }

    public function setTiendas(Tiendas $tiendas): static
    {
        // set the owning side of the relation if necessary
        if ($tiendas->getLogin() !== $this) {
            $tiendas->setLogin($this);
        }

        $this->tiendas = $tiendas;

        return $this;
    }

    public function getVericacion(): ?Estados
    {
        return $this->vericacion;
    }

    public function setVericacion(?Estados $vericacion): static
    {
        $this->vericacion = $vericacion;

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
            $productosFavorito->setLogin($this);
        }

        return $this;
    }

    public function removeProductosFavorito(ProductosFavoritos $productosFavorito): static
    {
        if ($this->productosFavoritos->removeElement($productosFavorito)) {
            // set the owning side to null (unless already changed)
            if ($productosFavorito->getLogin() === $this) {
                $productosFavorito->setLogin(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LogsApi>
     */
    public function getLogsApis(): Collection
    {
        return $this->logsApis;
    }

    public function addLogsApi(LogsApi $logsApi): static
    {
        if (!$this->logsApis->contains($logsApi)) {
            $this->logsApis->add($logsApi);
            $logsApi->setLogin($this);
        }

        return $this;
    }

    public function removeLogsApi(LogsApi $logsApi): static
    {
        if ($this->logsApis->removeElement($logsApi)) {
            // set the owning side to null (unless already changed)
            if ($logsApi->getLogin() === $this) {
                $logsApi->setLogin(null);
            }
        }

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
            $productosComentario->setLogin($this);
        }

        return $this;
    }

    public function removeProductosComentario(ProductosComentarios $productosComentario): static
    {
        if ($this->productosComentarios->removeElement($productosComentario)) {
            // set the owning side to null (unless already changed)
            if ($productosComentario->getLogin() === $this) {
                $productosComentario->setLogin(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Carrito>
     */
    public function getCarritos(): Collection
    {
        return $this->carritos;
    }

    public function addCarrito(Carrito $carrito): static
    {
        if (!$this->carritos->contains($carrito)) {
            $this->carritos->add($carrito);
            $carrito->setLogin($this);
        }

        return $this;
    }

    public function removeCarrito(Carrito $carrito): static
    {
        if ($this->carritos->removeElement($carrito)) {
            // set the owning side to null (unless already changed)
            if ($carrito->getLogin() === $this) {
                $carrito->setLogin(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Factura>
     */
    public function getFacturas(): Collection
    {
        return $this->facturas;
    }

    public function addFactura(Factura $factura): static
    {
        if (!$this->facturas->contains($factura)) {
            $this->facturas->add($factura);
            $factura->setLogin($this);
        }

        return $this;
    }

    public function removeFactura(Factura $factura): static
    {
        if ($this->facturas->removeElement($factura)) {
            // set the owning side to null (unless already changed)
            if ($factura->getLogin() === $this) {
                $factura->setLogin(null);
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
            $pedido->setLogin($this);
        }

        return $this;
    }

    public function removePedido(Pedidos $pedido): static
    {
        if ($this->pedidos->removeElement($pedido)) {
            // set the owning side to null (unless already changed)
            if ($pedido->getLogin() === $this) {
                $pedido->setLogin(null);
            }
        }

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
            $pregunta->setLogin($this);
        }

        return $this;
    }

    public function removePregunta(Preguntas $pregunta): static
    {
        if ($this->preguntas->removeElement($pregunta)) {
            // set the owning side to null (unless already changed)
            if ($pregunta->getLogin() === $this) {
                $pregunta->setLogin(null);
            }
        }

        return $this;
    }

    

    public function getGanancia(): ?Ganancia
    {
        return $this->ganancia;
    }

    public function setGanancia(Ganancia $ganancia): static
    {
        // set the owning side of the relation if necessary
        if ($ganancia->getLogin() !== $this) {
            $ganancia->setLogin($this);
        }

        $this->ganancia = $ganancia;

        return $this;
    }

    /**
     * @return Collection<int, Banco>
     */
    public function getBancos(): Collection
    {
        return $this->bancos;
    }

    public function addBanco(Banco $banco): static
    {
        if (!$this->bancos->contains($banco)) {
            $this->bancos->add($banco);
            $banco->setLogin($this);
        }

        return $this;
    }

    public function removeBanco(Banco $banco): static
    {
        if ($this->bancos->removeElement($banco)) {
            // set the owning side to null (unless already changed)
            if ($banco->getLogin() === $this) {
                $banco->setLogin(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Respuestas>
     */
    public function getRespuestas(): Collection
    {
        return $this->respuestas;
    }

    public function addRespuesta(Respuestas $respuesta): static
    {
        if (!$this->respuestas->contains($respuesta)) {
            $this->respuestas->add($respuesta);
            $respuesta->setLogin($this);
        }

        return $this;
    }

    public function removeRespuesta(Respuestas $respuesta): static
    {
        if ($this->respuestas->removeElement($respuesta)) {
            // set the owning side to null (unless already changed)
            if ($respuesta->getLogin() === $this) {
                $respuesta->setLogin(null);
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
            $cupon->addLogin($this);
        }

        return $this;
    }

    public function removeCupon(Cupon $cupon): static
    {
        if ($this->cupons->removeElement($cupon)) {
            $cupon->removeLogin($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Ofertas>
     */
    public function getOfertas(): Collection
    {
        return $this->ofertas;
    }

    public function addOferta(Ofertas $oferta): static
    {
        if (!$this->ofertas->contains($oferta)) {
            $this->ofertas->add($oferta);
            $oferta->setLogin($this);
        }

        return $this;
    }

    public function removeOferta(Ofertas $oferta): static
    {
        if ($this->ofertas->removeElement($oferta)) {
            // set the owning side to null (unless already changed)
            if ($oferta->getLogin() === $this) {
                $oferta->setLogin(null);
            }
        }

        return $this;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function setVersion(?int $version): static
    {
        $this->version = $version;

        return $this;
    }


    public function incrementVersion(): self
    {
        $this->version++;

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function getFacebookId(): ?string
    {
        return $this->facebook_id;
    }

    public function setFacebookId(?string $facebook_id): static
    {
        $this->facebook_id = $facebook_id;

        return $this;
    }

    public function getAppleId(): ?string
    {
        return $this->apple_id;
    }

    public function setAppleId(?string $apple_id): static
    {
        $this->apple_id = $apple_id;

        return $this;
    }

    public function getSaldo(): ?Saldo
    {
        return $this->saldo;
    }

    public function setSaldo(Saldo $saldo): static
    {
        // set the owning side of the relation if necessary
        if ($saldo->getLogin() !== $this) {
            $saldo->setLogin($this);
        }

        $this->saldo = $saldo;

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
            $regateo->setLogin($this);
        }

        return $this;
    }

    public function removeRegateo(Regateos $regateo): static
    {
        if ($this->regateos->removeElement($regateo)) {
            // set the owning side to null (unless already changed)
            if ($regateo->getLogin() === $this) {
                $regateo->setLogin(null);
            }
        }

        return $this;
    }


        
}

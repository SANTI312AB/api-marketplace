<?php 

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
class PublicUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer',name:"IDUSUARIO")]
    private ?int $id = null;

    #[ORM\Column(type: 'string', unique: true,name:"USERNAME")]
    private string $username;

    #[ORM\Column(type: 'string', name:"PASSWORD")]
    private string $password;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    /**
    * @see PasswordAuthenticatedUserInterface
    */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }


    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        return ['ROLE_FRONT'];
    }

    public function eraseCredentials() {}
}
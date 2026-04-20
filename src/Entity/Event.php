<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:"ID_EVENT")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255, name:"EVENTNAME")]
    private string $eventName;

    #[ORM\Column(type: "datetime_immutable", name:"TIMESTAP")]
    private \DateTimeImmutable $timestamp;

    #[ORM\Column(type: "string", length: 255,nullable:true, name:"USER")]
    private string $user;

    #[ORM\Column(type: "string", length: 255,nullable:true,name:"SESSION_ID")]
    private string $sessionId;

    #[ORM\Column(type: "json", name:"EVENTDATA")]
    private array $eventData = [];

    #[ORM\Column(type: "string", length: 50, nullable: true,name:"SDK_VERSION")]
    private ?string $sdkVersion = null;

    #[ORM\Column(type: "string", length: 255, nullable: true, name:"CLIENT_ID")]
    private ?string $clientId = null;

    #[ORM\Column(type: "string", length: 50, nullable: true, name:"SCHEMA_VERSION")]
    private ?string $schemaVersion = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function setEventName(string $eventName): self
    {
        $this->eventName = $eventName;
        return $this;
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeImmutable $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function setUser(string $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): self
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    public function getEventData(): array
    {
        return $this->eventData;
    }

    public function setEventData(array $eventData): self
    {
        $this->eventData = $eventData;
        return $this;
    }

    public function getSdkVersion(): ?string
    {
        return $this->sdkVersion;
    }

    public function setSdkVersion(?string $sdkVersion): self
    {
        $this->sdkVersion = $sdkVersion;
        return $this;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function setClientId(?string $clientId): self
    {
        $this->clientId = $clientId;
        return $this;
    }

    public function getSchemaVersion(): ?string
    {
        return $this->schemaVersion;
    }

    public function setSchemaVersion(?string $schemaVersion): self
    {
        $this->schemaVersion = $schemaVersion;
        return $this;
    }
}

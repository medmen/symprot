<?php

namespace App\Entity;

use App\Repository\ProtocolRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProtocolRepository::class)]
class Protocol implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $protocolName = null;


    #[ORM\Column(type: 'integer')]
    private ?int $protocolSize = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $protocolMimeType = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $protocolOrigName = null;

    #[ORM\Column()]
    private \DateTimeImmutable $updatedAt;

    #[ORM\JoinColumn(name: 'geraet_id', referencedColumnName: 'geraet_id', nullable: false)]
    #[ORM\ManyToOne(inversedBy: 'protocols')]
    private ?Geraet $geraet = null;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->protocolName;
    }


    public function getProtocolName(): ?string
    {
        return $this->protocolName;
    }

    public function setProtocolName(string $protocolName): self
    {
        $this->protocolName = $protocolName;

        return $this;
    }

    public function getProtocolSize(): ?int
    {
        return $this->protocolSize;
    }

    public function setProtocolSize(int $protocolSize): self
    {
        $this->protocolSize = $protocolSize;

        return $this;
    }

    public function getProtocolMimeType(): ?string
    {
        return $this->protocolMimeType;
    }

    public function setProtocolMimeType(string $protocolMimeType): self
    {
        $this->protocolMimeType = $protocolMimeType;

        return $this;
    }

    public function getProtocolOrigName(): ?string
    {
        return $this->protocolOrigName;
    }

    public function setProtocolOrigName(string $protocolOrigName): self
    {
        $this->protocolOrigName = $protocolOrigName;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getGeraet(): ?Geraet
    {
        return $this->geraet;
    }

    public function setGeraet(?Geraet $geraet): self
    {
        $this->geraet = $geraet;

        return $this;
    }
}

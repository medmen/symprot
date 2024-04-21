<?php

namespace App\Entity;

use App\Repository\ProtocolRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @Vich\Uploadable
 */
#[ORM\Entity(repositoryClass: ProtocolRepository::class)]
class Protocol implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @Vich\UploadableField(mapping="protocol_file", fileNameProperty="protocolName", size="protocolSize", mimeType="protocolMimeType", originalName="protocolOrigName")
     */
    private ?File $protocolFile = null;

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

    public function getProtocolFile(): ?File
    {
        return $this->protocolFile;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     */
    public function setProtocolFile(?File $protocolFile = null): void
    {
        $this->protocolFile = $protocolFile;

        // VERY IMPORTANT:
        // It is required that at least one field changes if you are using Doctrine,
        // otherwise the event listeners won't be called and the file is lost
        if ($this->protocolFile instanceof UploadedFile) {
            // if 'updatedAt' is not defined in your entity, use another property
            $this->updatedAt = new \DateTimeImmutable();
        }
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

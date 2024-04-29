<?php

namespace App\Entity;

use App\Repository\ProtocolRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity(repositoryClass=ProtocolRepository::class)
 *
 * @Vich\Uploadable
 */
class Protocol
{
    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @Vich\UploadableField(mapping="protocol_file", fileNameProperty="protocolName", size="protocolSize", mimeType="protocolMimeType", originalName="protocolOrigName")
     *
     * @var File|null
     */
    private $protocolFile;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var string|null
     */
    private $protocolName;

    /**
     * @ORM\Column(type="integer")
     *
     * @var int|null
     */
    private $protocolSize;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var string|null
     */
    private $protocolMimeType;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var string|null
     */
    private $protocolOrigName;

    /**
     * @ORM\Column(type="datetime")
     *
     * @var \DateTimeInterface|null
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity=Geraet::class, inversedBy="protocols")
     *
     * @ORM\JoinColumn(name="geraet_id", referencedColumnName="geraet_id", nullable=false)
     */
    private $geraet;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString()
    {
        return $this->protocolName;
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
     *
     * @param File|UploadedFile|null $protocolFile
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
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

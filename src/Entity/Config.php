<?php

namespace App\Entity;

use App\Repository\ConfigRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfigRepository::class)]
class Config
{
    // set default values
    public function __construct()
    {
        $this->debug = false;
        $this->limitPages = 0;
        $this->outputFormat = 'md';
        $this->stripUnits = true;
        $this->autoImportParameters = true;
        // $this->keepUploadedFiles = false;
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string')]
    private ?string $limitPages = null;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $stripUnits = true;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $debug = false;

    #[ORM\Column(type: 'string')]
    private ?string $outputFormat = null;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $autoImportParameters = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLimitPages(): ?string
    {
        return $this->limitPages;
    }

    public function setLimitPages(string $limitPages): self
    {
        $this->limitPages = $limitPages;

        return $this;
    }

    public function getStripUnits(): ?bool
    {
        return $this->stripUnits;
    }

    public function setStripUnits(bool $stripUnits): self
    {
        $this->stripUnits = $stripUnits;

        return $this;
    }

    public function getDebug(): ?bool
    {
        return $this->debug;
    }

    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;

        return $this;
    }

    public function getOutputFormat(): ?string
    {
        return $this->outputFormat;
    }

    public function setOutputFormat(string $outputFormat): self
    {
        $this->outputFormat = $outputFormat;

        return $this;
    }

    public function getAutoImportParameters(): ?bool
    {
        return $this->autoImportParameters;
    }

    public function setautoImportParameters(bool $autoImportParameters): self
    {
        $this->autoImportParameters = $autoImportParameters;

        return $this;
    }


    public function getDefaults()
    {
        $this->setDefaults();

        return $this;
    }

    public function setDefaults(): void
    {
        $this->debug = false;
        $this->limitPages = 0;
        $this->outputFormat = 'md';
        $this->stripUnits = true;
    }

    public function isStripUnits(): ?bool
    {
        return $this->stripUnits;
    }

    public function isDebug(): ?bool
    {
        return $this->debug;
    }

    public function isAutoImportParameters(): ?bool
    {
        return $this->autoImportParameters;
    }
}

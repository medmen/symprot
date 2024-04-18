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
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string')]
    private $limitPages;

    #[ORM\Column(type: 'boolean')]
    private $stripUnits;

    #[ORM\Column(type: 'boolean')]
    private $debug;

    #[ORM\Column(type: 'string')]
    private $outputFormat;

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
}

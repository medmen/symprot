<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Geraet.
 */
#[ORM\Entity(repositoryClass: \App\Repository\GeraetRepository::class)]
class Geraet implements \Stringable
{
    #[ORM\Column(name: 'geraet_id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $geraet_id = null;

    #[ORM\Column(name: 'geraet_name', type: 'text', nullable: true, options: ['default' => 'MRT'])]
    private ?string $geraet_name = 'MRT';

    #[ORM\Column(name: 'geraet_beschreibung', type: 'text', nullable: true, options: ['default' => 'Bei mehreren geräten hilfreich zur Unterscheidung'])]
    private ?string $geraet_beschreibung = 'Bei mehreren geräten hilfreich zur Unterscheidung';

    #[ORM\OneToMany(targetEntity: Parameter::class, mappedBy: 'geraet', orphanRemoval: true)]
    private Collection $parameters;

    #[ORM\OneToMany(targetEntity: Protocol::class, mappedBy: 'geraet', orphanRemoval: true)]
    private Collection $protocols;

    public function __construct()
    {
        $this->parameters = new ArrayCollection();
        // $this->helperfields = new ArrayCollection();
        $this->protocols = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->geraet_name;
    }

    public function getGeraetId(): ?int
    {
        return $this->geraet_id;
    }
    public function getId(): ?int
    {
        return $this->geraet_id;
    }

    public function getGeraetName(): ?string
    {
        return $this->geraet_name;
    }

    public function setGeraetName(?string $geraet_name): self
    {
        $this->geraet_name = $geraet_name;

        return $this;
    }

    public function getGeraetBeschreibung(): ?string
    {
        return $this->geraet_beschreibung;
    }

    public function setGeraetBeschreibung(?string $geraet_beschreibung): self
    {
        $this->geraet_beschreibung = $geraet_beschreibung;

        return $this;
    }

    /**
     * @return Collection|Parameter[]
     */
    public function getParameters(): Collection
    {
        return $this->parameters;
    }

    public function addParameter(Parameter $parameter): self
    {
        if (!$this->parameters->contains($parameter)) {
            $this->parameters[] = $parameter;
            $parameter->setGeraet($this);
        }

        return $this;
    }

    public function removeParameter(Parameter $parameter): self
    {
        if ($this->parameters->removeElement($parameter)) {
            // set the owning side to null (unless already changed)
            if ($parameter->getGeraet() === $this) {
                $parameter->setGeraet(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Protocol[]
     */
    public function getProtocols(): Collection
    {
        return $this->protocols;
    }

    public function addProtocol(Protocol $protocol): self
    {
        if (!$this->protocols->contains($protocol)) {
            $this->protocols[] = $protocol;
            $protocol->setGeraet($this);
        }

        return $this;
    }

    public function removeProtocol(Protocol $protocol): self
    {
        if ($this->protocols->removeElement($protocol)) {
            // set the owning side to null (unless already changed)
            if ($protocol->getGeraet() === $this) {
                $protocol->setGeraet(null);
            }
        }

        return $this;
    }
}

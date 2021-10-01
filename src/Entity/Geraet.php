<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Geraet
 *
 * @ORM\Entity(repositoryClass="App\Repository\GeraetRepository")
 */
class Geraet
{
    /**
     * @var int
     *
     * @ORM\Column(name="geraet_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $geraetId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="geraet_name", type="text", nullable=true, options={"default"="MRT"})
     */
    private $geraetName = 'MRT';

    /**
     * @var string|null
     *
     * @ORM\Column(name="geraet_beschreibung", type="text", nullable=true, options={"default"="Bei mehreren geräten hilfreich zur Unterscheidung"})
     */
    private $geraetBeschreibung = 'Bei mehreren geräten hilfreich zur Unterscheidung';

    /**
     * @ORM\OneToMany(targetEntity=Parameter::class, mappedBy="geraet", orphanRemoval=true)
     */
    private $parameters;

    /**
     * @ORM\OneToMany(targetEntity=Helperfields::class, mappedBy="geraet", orphanRemoval=true)
     */
    private $helperfields;

    /**
     * @ORM\OneToMany(targetEntity=Protocol::class, mappedBy="geraet", orphanRemoval=true)
     */
    private $protocols;

    public function __construct()
    {
        $this->parameters = new ArrayCollection();
        $this->helperfields = new ArrayCollection();
        $this->protocols = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->geraetName;
    }

    public function getGeraetId(): ?int
    {
        return $this->geraetId;
    }

    public function getGeraetName(): ?string
    {
        return $this->geraetName;
    }

    public function setGeraetName(?string $geraetName): self
    {
        $this->geraetName = $geraetName;

        return $this;
    }

    public function getGeraetBeschreibung(): ?string
    {
        return $this->geraetBeschreibung;
    }

    public function setGeraetBeschreibung(?string $geraetBeschreibung): self
    {
        $this->geraetBeschreibung = $geraetBeschreibung;

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
     * @return Collection|Helperfields[]
     */
    public function getHelperfields(): Collection
    {
        return $this->helperfields;
    }

    public function addHelperfield(Helperfields $helperfield): self
    {
        if (!$this->helperfields->contains($helperfield)) {
            $this->helperfields[] = $helperfield;
            $helperfield->setGeraet($this);
        }

        return $this;
    }

    public function removeHelperfield(Helperfields $helperfield): self
    {
        if ($this->helperfields->removeElement($helperfield)) {
            // set the owning side to null (unless already changed)
            if ($helperfield->getGeraet() === $this) {
                $helperfield->setGeraet(null);
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
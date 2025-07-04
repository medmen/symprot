<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Parameter.
 */
#[ORM\Entity(repositoryClass: \App\Repository\ParameterRepository::class)]
class Parameter
{
    #[ORM\Column(name: 'parameter_id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $parameter_id;

    #[ORM\Column(name: 'parameter_name', type: 'text', nullable: true)]
    private $parameter_name;

    #[ORM\Column(name: 'parameter_selected', type: 'boolean')]
    private $parameter_selected = false;

    #[ORM\Column(name: 'parameter_default', type: 'boolean')]
    private $parameter_default = false;

    #[ORM\JoinColumn(name: 'geraet_id', referencedColumnName: 'geraet_id', nullable: false)]
    #[ORM\ManyToOne(inversedBy: 'parameters')]
    private ?Geraet $geraet = null;

    #[Gedmo\SortablePosition]
    #[ORM\Column(name: 'sort_position', type: 'integer', nullable: true)]
    private ?int $sort_position = 0;

    public function getParameterId(): ?int
    {
        return $this->parameter_id;
    }

    public function getParameterName(): ?string
    {
        return $this->parameter_name;
    }

    public function setParameterName(?string $parameter_name): self
    {
        $this->parameter_name = $parameter_name;

        return $this;
    }

    public function getParameterSelected(): ?bool
    {
        return $this->parameter_selected;
    }

    public function setParameterSelected(bool $parameter_selected): self
    {
        $this->parameter_selected = $parameter_selected;

        return $this;
    }

    public function getParameterDefault(): ?bool
    {
        return $this->parameter_default;
    }

    public function setParameterDefault(bool $parameter_default): self
    {
        $this->parameter_default = $parameter_default;

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

    public function getSortPosition(): ?int
    {
        return $this->sort_position;
    }

    public function setSortPosition(int $sort_position): static
    {
        $this->sort_position = $sort_position;

        return $this;
    }
}

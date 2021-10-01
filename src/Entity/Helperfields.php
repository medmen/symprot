<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Helperfields
 *
 * @ORM\Entity(repositoryClass="App\Repository\HelperfieldsRepository")
 */

class Helperfields
{
    /**
     * @var int
     *
     * @ORM\Column(name="helperfield_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $helperfield_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="helperfield_name", type="text", nullable=true)
     */
    private $helperfield_name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="helperfield_inputtype", type="text", nullable=true, options={"default"="text"})
     */
    private $helperfield_inputtype = 'text';

    /**
     * @var string|null
     *
     * @ORM\Column(name="helperfield_label", type="text", nullable=true, options={"default"="label"})
     */
    private $helperfield_label = 'label';

    /**
     * @var string|null
     *
     * @ORM\Column(name="helperfield_help", type="text", nullable=true, options={"default"="helpful hint"})
     */
    private $helperfield_help = 'helpful hint';

    /**
     * @var string|null
     *
     * @ORM\Column(name="helperfield_placeholder", type="text", nullable=true, options={"default"="beispiel-Wert"})
     */
    private $helperfield_placeholder = 'beispiel-Wert';

    /**
     * @var string|null
     *
     * @ORM\Column(name="helperfield_value", type="text", nullable=true)
     */
    private $helperfield_value;

    /**
     * @ORM\ManyToOne(targetEntity=Geraet::class, inversedBy="helperfields")
     * @ORM\JoinColumn(name="geraet_id", referencedColumnName="geraet_id", nullable=false)
     */
    private $geraet;

    public function __toString(): string
    {
        return $this->helperfield_name.' ('.$this->geraet->geraet_name.')';
    }

    public function getHelperfieldId(): ?int
    {
        return $this->helperfield_id;
    }

    public function getHelperfieldName(): ?string
    {
        return $this->helperfield_name;
    }

    public function setHelperfieldName(?string $helperfield_name): self
    {
        $this->helperfield_name = $helperfield_name;

        return $this;
    }

    public function getHelperfieldInputtype(): ?string
    {
        return $this->helperfield_inputtype;
    }

    public function setHelperfieldInputtype(?string $helperfield_inputtype): self
    {
        $this->helperfield_inputtype = $helperfield_inputtype;

        return $this;
    }

    public function getHelperfieldLabel(): ?string
    {
        return $this->helperfield_label;
    }

    public function setHelperfieldLabel(?string $helperfield_label): self
    {
        $this->helperfield_label = $helperfield_label;

        return $this;
    }

    public function getHelperfieldHelp(): ?string
    {
        return $this->helperfield_help;
    }

    public function setHelperfieldHelp(?string $helperfield_help): self
    {
        $this->helperfield_help = $helperfield_help;

        return $this;
    }

    public function getHelperfieldPlaceholder(): ?string
    {
        return $this->helperfield_placeholder;
    }

    public function setHelperfieldPlaceholder(?string $helperfield_placeholder): self
    {
        $this->helperfield_placeholder = $helperfield_placeholder;

        return $this;
    }

    public function getHelperfieldValue(): ?string
    {
        return $this->helperfield_value;
    }

    public function setHelperfieldValue(?string $helperfield_value): self
    {
        $this->helperfield_value = $helperfield_value;

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

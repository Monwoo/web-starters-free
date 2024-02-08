<?php

namespace MWS\MoonManagerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use MWS\MoonManagerBundle\Repository\MwsTimeQualifRepository;

#[ORM\Entity(repositoryClass: MwsTimeQualifRepository::class)]
class MwsTimeQualif
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column(nullable: true)]
    private ?int $shortcut = null;

    #[ORM\ManyToMany(targetEntity: MwsTimeTag::class, inversedBy: 'mwsTimeQualifs')]
    private Collection $timeTags;

    public function __construct()
    {
        $this->timeTags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getShortcut(): ?int
    {
        return $this->shortcut;
    }

    public function setShortcut(?int $shortcut): static
    {
        $this->shortcut = $shortcut;

        return $this;
    }

    /**
     * @return Collection<int, MwsTimeTag>
     */
    public function getTimeTags(): Collection
    {
        return $this->timeTags;
    }

    public function addTimeTag(MwsTimeTag $timeTag): static
    {
        if (!$this->timeTags->contains($timeTag)) {
            $this->timeTags->add($timeTag);
        }

        return $this;
    }

    public function removeTimeTag(MwsTimeTag $timeTag): static
    {
        $this->timeTags->removeElement($timeTag);

        return $this;
    }
}

<?php

namespace MWS\MoonManagerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use MWS\MoonManagerBundle\Repository\MwsTimeQualifRepository;
use Symfony\Component\Serializer\Annotation as Serializer;

#[ORM\Entity(repositoryClass: MwsTimeQualifRepository::class)]
class MwsTimeQualif
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Serializer\Groups(['withDeepIds'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $primaryColorRgb = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $primaryColorHex = null;

    // TODO : refactor : should be inside user CONFIG
    //         => not a global shortcut, otherwise, each user
    //         will change shortcuts of every ones...
    #[ORM\Column(nullable: true)]
    private ?int $shortcut = null;

    #[ORM\ManyToMany(targetEntity: MwsTimeTag::class, inversedBy: 'mwsTimeQualifs')]
    private Collection $timeTags;

    #[ORM\ManyToMany(targetEntity: MwsUser::class, inversedBy: 'quickQualifHistory')]
    private Collection $quickUserHistory;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $htmlIcon = null;

    public function __construct()
    {
        $this->timeTags = new ArrayCollection();
        $this->quickUserHistory = new ArrayCollection();
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

    /**
     * @return Collection<int, MwsUser>
     */
    public function getQuickUserHistory(): Collection
    {
        return $this->quickUserHistory;
    }

    public function addQuickUserHistory(MwsUser $quickUserHistory): static
    {
        if (!$this->quickUserHistory->contains($quickUserHistory)) {
            $this->quickUserHistory->add($quickUserHistory);
        }

        return $this;
    }

    public function removeQuickUserHistory(MwsUser $quickUserHistory): static
    {
        $this->quickUserHistory->removeElement($quickUserHistory);

        return $this;
    }

    public function getPrimaryColorRgb(): ?string
    {
        return $this->primaryColorRgb;
    }

    public function setPrimaryColorRgb(?string $primaryColorRgb): static
    {
        $this->primaryColorRgb = $primaryColorRgb;

        return $this;
    }

    public function getPrimaryColorHex(): ?string
    {
        return $this->primaryColorHex;
    }

    public function setPrimaryColorHex(?string $primaryColorHex): static
    {
        $this->primaryColorHex = $primaryColorHex;

        return $this;
    }

    public function getHtmlIcon(): ?string
    {
        return $this->htmlIcon;
    }

    public function setHtmlIcon(?string $htmlIcon): static
    {
        $this->htmlIcon = $htmlIcon;

        return $this;
    }
}

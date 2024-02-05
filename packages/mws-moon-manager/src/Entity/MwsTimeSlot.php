<?php

namespace MWS\MoonManagerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use MWS\MoonManagerBundle\Repository\MwsTimeSlotRepository;

#[ORM\Entity(repositoryClass: MwsTimeSlotRepository::class)]
#[ORM\Index(columns: ['source_time'])]
#[ORM\Index(columns: ['range_day_idx_by10_min'])]
#[ORM\Index(columns: ['range_day_idx_by_custom_norm'])]
class MwsTimeSlot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $sourceTime = null;

    #[ORM\Column]
    private array $source = [];

    #[ORM\Column(nullable: true)]
    private ?int $rangeDayIdxBy10Min = null;

    #[ORM\Column(nullable: true)]
    private ?int $rangeDayIdxByCustomNorm = null;

    #[ORM\ManyToMany(targetEntity: MwsTimeTag::class, inversedBy: 'mwsTimeSlots')]
    private Collection $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }
    // #[ORM\ManyToMany(targetEntity: MwsOfferStatus::class, inversedBy: 'mwsOffers', cascade: ['persist'])]
    // private Collection $tags;

    use TimestampableEntity;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSourceTime(): ?\DateTimeInterface
    {
        return $this->sourceTime;
    }

    public function setSourceTime(?\DateTimeInterface $sourceTime): static
    {
        $this->sourceTime = $sourceTime;

        return $this;
    }

    public function getSource(): array
    {
        return $this->source;
    }

    public function setSource(array $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getRangeDayIdxBy10Min(): ?int
    {
        return $this->rangeDayIdxBy10Min;
    }

    public function setRangeDayIdxBy10Min(?int $rangeDayIdxBy10Min): static
    {
        $this->rangeDayIdxBy10Min = $rangeDayIdxBy10Min;

        return $this;
    }

    public function getRangeDayIdxByCustomNorm(): ?int
    {
        return $this->rangeDayIdxByCustomNorm;
    }

    public function setRangeDayIdxByCustomNorm(?int $rangeDayIdxByCustomNorm): static
    {
        $this->rangeDayIdxByCustomNorm = $rangeDayIdxByCustomNorm;

        return $this;
    }

    /**
     * @return Collection<int, MwsTimeTag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(MwsTimeTag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(MwsTimeTag $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }
}

<?php

namespace MWS\MoonManagerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use MWS\MoonManagerBundle\Repository\MwsTimeTagRepository;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[ORM\Entity(repositoryClass: MwsTimeTagRepository::class)]
#[ORM\Index(columns: ['slug'])]
// #[Serializer\Context(context: [
//     AbstractNormalizer::ATTRIBUTES =>
//     ['projectId', 'owner' => ['id']]
// ])] // Not for entity class....
class MwsTimeTag
{
    // https://github.com/symfony/symfony/issues/32622
    // https://github.com/symfony/symfony/pull/33533
    // https://jmsyst.com/libs/serializer/master/cookbook/exclusion_strategies
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Serializer\Groups(['withDeepIds', '*', 'default'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Serializer\Groups(['withDeepIds'])]
    private ?string $slug = null;

    #[ORM\Column(length: 255)]
    #[Serializer\Groups(['withDeepIds'])]
    private ?string $label = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Serializer\Groups(['withDeepIds'])]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'mwsTimeTags')]
    #[Serializer\Groups(['withDeepIds'])]
    private ?self $category = null;

    #[ORM\Column(nullable: true)]
    #[Serializer\Groups(['withDeepIds'])]
    private ?float $pricePerHr = null;

    // if pricePerHr is not set, check pricePerHrRules...
    // foreach rules, use chosen price if fit rules...
    // TODO : centralized RulesSystemManager.php service ?
    // 0 =>
    //   price => 80
    //   withTags => [ mise-en-oeuvre-experte ]
    // 1 =>
    //   price => 60
    //   withTags => [ mise-en-oeuvre-simple ]
    #[ORM\Column(nullable: true)]
    #[Serializer\Groups(['withDeepIds'])]
    private ?array $pricePerHrRules = null;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: self::class)]
    #[Serializer\Groups(['withDeepIds'])]
    #[Serializer\Context(
        context: [
            // AbstractNormalizer::CALLBACKS => [ // TIPS : no effect here...
            //     'providerAddedPrice' => $toFloatNorm,
            // ],        
            // AbstractNormalizer::IGNORED_ATTRIBUTES => ['id'],
            AbstractNormalizer::ATTRIBUTES => ['id']
            // ['projectId', 'owner' => ['id']]
        ],
        groups: ['withDeepIds', '*', 'default'],
    )]
    #[Serializer\Context(
        context: [
            AbstractNormalizer::ATTRIBUTES => ['id']
        ],
    )]
    #[Serializer\MaxDepth(1)]
    #[Serializer\Ignore] // TODO: advanced serializer for only id, or slug ? avoid deep serialization loops
    private Collection $mwsTimeTags;

    #[ORM\ManyToMany(targetEntity: MwsTimeSlot::class, mappedBy: 'tags')]
    #[Serializer\Groups(['withDeepIds'])]
    #[Serializer\Context(
        context: [
            AbstractNormalizer::ATTRIBUTES => ['id']
        ],
        groups: ['withDeepIds', '*', 'default'],
    )]
    #[Serializer\Context(
        context: [
            AbstractNormalizer::ATTRIBUTES => ['id']
        ],
    )]
    #[Serializer\MaxDepth(1)]
    #[Serializer\Ignore] // TODO: advanced serializer for only id, or slug ? avoid deep serialization loops
    private Collection $mwsTimeSlots;

    #[ORM\ManyToMany(targetEntity: MwsTimeQualif::class, mappedBy: 'timeTags')]
    #[Serializer\Groups(['withDeepIds'])]
    #[Serializer\Context(
        context: [
            AbstractNormalizer::ATTRIBUTES => ['id']
        ],
        groups: ['withDeepIds', '*', 'default'],
    )]
    #[Serializer\Context(
        context: [
            AbstractNormalizer::ATTRIBUTES => ['id']
        ],
    )]
    #[Serializer\MaxDepth(1)]
    #[Serializer\Ignore] // TODO: advanced serializer for only id, or slug ? avoid deep serialization loops
    private Collection $mwsTimeQualifs;

    #[ORM\OneToMany(mappedBy: 'maxPriceTag', targetEntity: MwsTimeSlot::class)]
    #[Serializer\Ignore] // TODO: advanced serializer for only id, or slug ? avoid deep serialization loops
    private Collection $mwsTimeSlotsForMax;

    public function __construct()
    {
        $this->mwsTimeTags = new ArrayCollection();
        $this->mwsTimeSlots = new ArrayCollection();
        $this->mwsTimeQualifs = new ArrayCollection();
        $this->mwsTimeSlotsForMax = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->slug;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCategory(): ?self
    {
        return $this->category;
    }

    public function setCategory(?self $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getMwsTimeTags(): Collection
    {
        return $this->mwsTimeTags;
    }

    public function addMwsTimeTag(self $mwsTimeTag): static
    {
        if (!$this->mwsTimeTags->contains($mwsTimeTag)) {
            $this->mwsTimeTags->add($mwsTimeTag);
            $mwsTimeTag->setCategory($this);
        }

        return $this;
    }

    public function removeMwsTimeTag(self $mwsTimeTag): static
    {
        if ($this->mwsTimeTags->removeElement($mwsTimeTag)) {
            // set the owning side to null (unless already changed)
            if ($mwsTimeTag->getCategory() === $this) {
                $mwsTimeTag->setCategory(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MwsTimeSlot>
     */
    public function getMwsTimeSlots(): Collection
    {
        return $this->mwsTimeSlots;
    }

    public function addMwsTimeSlot(MwsTimeSlot $mwsTimeSlot): static
    {
        if (!$this->mwsTimeSlots->contains($mwsTimeSlot)) {
            $this->mwsTimeSlots->add($mwsTimeSlot);
            $mwsTimeSlot->addTag($this);
        }

        return $this;
    }

    public function removeMwsTimeSlot(MwsTimeSlot $mwsTimeSlot): static
    {
        if ($this->mwsTimeSlots->removeElement($mwsTimeSlot)) {
            $mwsTimeSlot->removeTag($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, MwsTimeQualif>
     */
    public function getMwsTimeQualifs(): Collection
    {
        return $this->mwsTimeQualifs;
    }

    public function addMwsTimeQualif(MwsTimeQualif $mwsTimeQualif): static
    {
        if (!$this->mwsTimeQualifs->contains($mwsTimeQualif)) {
            $this->mwsTimeQualifs->add($mwsTimeQualif);
            $mwsTimeQualif->addTimeTag($this);
        }

        return $this;
    }

    public function removeMwsTimeQualif(MwsTimeQualif $mwsTimeQualif): static
    {
        if ($this->mwsTimeQualifs->removeElement($mwsTimeQualif)) {
            $mwsTimeQualif->removeTimeTag($this);
        }

        return $this;
    }

    public function getPricePerHr(): ?float
    {
        return $this->pricePerHr;
    }

    public function setPricePerHr(?float $pricePerHr): static
    {
        $this->pricePerHr = $pricePerHr;

        return $this;
    }

    public function getPricePerHrRules(): ?array
    {
        return $this->pricePerHrRules;
    }

    public function setPricePerHrRules(?array $pricePerHrRules): static
    {
        $this->pricePerHrRules = $pricePerHrRules;

        return $this;
    }

    /**
     * @return Collection<int, MwsTimeSlot>
     */
    public function getMwsTimeSlotsForMax(): Collection
    {
        return $this->mwsTimeSlotsForMax;
    }

    public function addMwsTimeSlotsForMax(MwsTimeSlot $mwsTimeSlotsForMax): static
    {
        if (!$this->mwsTimeSlotsForMax->contains($mwsTimeSlotsForMax)) {
            $this->mwsTimeSlotsForMax->add($mwsTimeSlotsForMax);
            $mwsTimeSlotsForMax->setMaxPriceTag($this);
        }

        return $this;
    }

    public function removeMwsTimeSlotsForMax(MwsTimeSlot $mwsTimeSlotsForMax): static
    {
        if ($this->mwsTimeSlotsForMax->removeElement($mwsTimeSlotsForMax)) {
            // set the owning side to null (unless already changed)
            if ($mwsTimeSlotsForMax->getMaxPriceTag() === $this) {
                $mwsTimeSlotsForMax->setMaxPriceTag(null);
            }
        }

        return $this;
    }
}

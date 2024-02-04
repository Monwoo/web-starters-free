<?php

namespace MWS\MoonManagerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use MWS\MoonManagerBundle\Repository\MwsTimeTagRepository;

#[ORM\Entity(repositoryClass: MwsTimeTagRepository::class)]
class MwsTimeTag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'mwsTimeTags')]
    private ?self $category = null;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: self::class)]
    private Collection $mwsTimeTags;

    #[ORM\ManyToMany(targetEntity: MwsTimeSlot::class, mappedBy: 'tags')]
    private Collection $mwsTimeSlots;

    public function __construct()
    {
        $this->mwsTimeTags = new ArrayCollection();
        $this->mwsTimeSlots = new ArrayCollection();
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
}

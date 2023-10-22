<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use MWS\MoonManagerBundle\Helper\TranslationExtractOnly;
use MWS\MoonManagerBundle\Repository\MwsOfferStatusRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation as Serializer;

define('trans', function(...$args){return $args[0];});

#[ORM\Entity(repositoryClass: MwsOfferStatusRepository::class)]
#[UniqueEntity(
    fields: ['slug', 'categorySlug'],
    errorPath: 'slug',
    message: MwsOfferStatus::slugAndCatNotUniqueError,
)]
#[ORM\Index(columns: ['slug'])]
#[ORM\Index(columns: ['category_slug'])]
class MwsOfferStatus
{
    // TODO : should do someting like :
    // TranslationExtractOnly->trans('....') for translation auto-extractor to works...
    // BUT SOUNDS like not possible to write 'function call' inside constants...
    // IDEA : DELEGATE ALL translations to SVELTE SIDE. Backend will only send key ?
    //       => same issue as previous : how to auto-generate/sync app translations strings ?
    const slugAndCatNotUniqueError = // trans(
        'mws.offer.errors.tag-slug-and-category-not-unique'
    /*)*/;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $label = null;

    // TODO : DOC : ONE LEVEL of category for now, review query if
    // want multiple, and change this to ManyToMany or use
    // property path alike system in current ONE LEVEL categorySlug system...
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $categorySlug = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bgColor = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $textColor = null;

    #[ORM\ManyToMany(targetEntity: MwsOffer::class, mappedBy: 'tags')]
    #[Serializer\Ignore]
    private Collection $mwsOffers;

    use TimestampableEntity;

    public function __construct()
    {
        $this->mwsOffers = new ArrayCollection();
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

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getCategorySlug(): ?string
    {
        return $this->categorySlug;
    }

    public function setCategorySlug(?string $categorySlug): static
    {
        $this->categorySlug = $categorySlug;

        return $this;
    }

    public function getBgColor(): ?string
    {
        return $this->bgColor;
    }

    public function setBgColor(?string $bgColor): static
    {
        $this->bgColor = $bgColor;

        return $this;
    }

    public function getTextColor(): ?string
    {
        return $this->textColor;
    }

    public function setTextColor(?string $textColor): static
    {
        $this->textColor = $textColor;

        return $this;
    }

    /**
     * @return Collection<int, MwsOffer>
     */
    public function getMwsOffers(): Collection
    {
        return $this->mwsOffers;
    }

    public function addMwsOffer(MwsOffer $mwsOffer): static
    {
        if (!$this->mwsOffers->contains($mwsOffer)) {
            $this->mwsOffers->add($mwsOffer);
            $mwsOffer->addTag($this);
        }

        return $this;
    }

    public function removeMwsOffer(MwsOffer $mwsOffer): static
    {
        if ($this->mwsOffers->removeElement($mwsOffer)) {
            $mwsOffer->removeTag($this);
        }

        return $this;
    }
}

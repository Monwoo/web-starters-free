<?php

namespace MWS\MoonManagerBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use MWS\MoonManagerBundle\Repository\MwsOfferStatusRepository;

#[ORM\Entity(repositoryClass: MwsOfferStatusRepository::class)]
#[ORM\Index(columns: ['slug'])]
#[ORM\Index(columns: ['category_slug'])]
class MwsOfferStatus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $categorySlug = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bgColor = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $textColor = null;

    use TimestampableEntity;

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
}
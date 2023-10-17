<?php

namespace MWS\MoonManagerBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use MWS\MoonManagerBundle\Repository\MwsOfferTrackingRepository;

#[ORM\Entity(repositoryClass: MwsOfferTrackingRepository::class)]
class MwsOfferTracking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'mwsOfferTrackings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MwsOffer $offer = null;

    #[ORM\ManyToOne(inversedBy: 'mwsOfferTrackings')]
    private ?MwsUser $owner = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $offerStatusSlug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOffer(): ?MwsOffer
    {
        return $this->offer;
    }

    public function setOffer(?MwsOffer $offer): static
    {
        $this->offer = $offer;

        return $this;
    }

    public function getOwner(): ?MwsUser
    {
        return $this->owner;
    }

    public function setOwner(?MwsUser $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getOfferStatusSlug(): ?string
    {
        return $this->offerStatusSlug;
    }

    public function setOfferStatusSlug(?string $offerStatusSlug): static
    {
        $this->offerStatusSlug = $offerStatusSlug;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }
}

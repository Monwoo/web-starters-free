<?php

namespace MWS\MoonManagerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use MWS\MoonManagerBundle\Repository\MwsOfferRepository;

#[ORM\Entity(repositoryClass: MwsOfferRepository::class)]
class MwsOffer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $clientUsername = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contact1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contact2 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contact3 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $sourceUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $clientUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $currentBillingNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $currentStatusSlug = null;

    #[ORM\OneToMany(mappedBy: 'offer', targetEntity: MwsOfferTracking::class, cascade: ['persist'])]
    private Collection $mwsOfferTrackings;

    // cascade: ['persist', 'remove']
    #[ORM\ManyToMany(targetEntity: MwsContact::class, inversedBy: 'mwsOffers', cascade: ['persist'])]
    private Collection $contacts;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sourceName = null;

    #[ORM\Column(nullable: true)]
    private ?array $sourceDetail = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    use TimestampableEntity;

    public function __construct()
    {
        $this->mwsOfferTrackings = new ArrayCollection();
        $this->contacts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientUsername(): ?string
    {
        return $this->clientUsername;
    }

    public function setClientUsername(string $clientUsername): static
    {
        $this->clientUsername = $clientUsername;

        return $this;
    }

    public function getContact1(): ?string
    {
        return $this->contact1;
    }

    public function setContact1(?string $contact1): static
    {
        $this->contact1 = $contact1;

        return $this;
    }

    public function getContact2(): ?string
    {
        return $this->contact2;
    }

    public function setContact2(?string $contact2): static
    {
        $this->contact2 = $contact2;

        return $this;
    }

    public function getContact3(): ?string
    {
        return $this->contact3;
    }

    public function setContact3(?string $contact3): static
    {
        $this->contact3 = $contact3;

        return $this;
    }

    public function getSourceUrl(): ?string
    {
        return $this->sourceUrl;
    }

    public function setSourceUrl(?string $sourceUrl): static
    {
        $this->sourceUrl = $sourceUrl;

        return $this;
    }

    public function getClientUrl(): ?string
    {
        return $this->clientUrl;
    }

    public function setClientUrl(?string $clientUrl): static
    {
        $this->clientUrl = $clientUrl;

        return $this;
    }

    public function getCurrentBillingNumber(): ?string
    {
        return $this->currentBillingNumber;
    }

    public function setCurrentBillingNumber(?string $currentBillingNumber): static
    {
        $this->currentBillingNumber = $currentBillingNumber;

        return $this;
    }

    public function getCurrentStatusSlug(): ?string
    {
        return $this->currentStatusSlug;
    }

    public function setCurrentStatusSlug(?string $currentStatusSlug): static
    {
        $this->currentStatusSlug = $currentStatusSlug;

        return $this;
    }

    /**
     * @return Collection<int, MwsOfferTracking>
     */
    public function getMwsOfferTrackings(): Collection
    {
        return $this->mwsOfferTrackings;
    }

    public function addMwsOfferTracking(MwsOfferTracking $mwsOfferTracking): static
    {
        if (!$this->mwsOfferTrackings->contains($mwsOfferTracking)) {
            $this->mwsOfferTrackings->add($mwsOfferTracking);
            $mwsOfferTracking->setOffer($this);
        }

        return $this;
    }

    public function removeMwsOfferTracking(MwsOfferTracking $mwsOfferTracking): static
    {
        if ($this->mwsOfferTrackings->removeElement($mwsOfferTracking)) {
            // set the owning side to null (unless already changed)
            if ($mwsOfferTracking->getOffer() === $this) {
                $mwsOfferTracking->setOffer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MwsContact>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(MwsContact $contact): static
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts->add($contact);
        }

        return $this;
    }

    public function removeContact(MwsContact $contact): static
    {
        $this->contacts->removeElement($contact);

        return $this;
    }

    public function getSourceName(): ?string
    {
        return $this->sourceName;
    }

    public function setSourceName(?string $sourceName): static
    {
        $this->sourceName = $sourceName;

        return $this;
    }

    public function getSourceDetail(): ?array
    {
        return $this->sourceDetail;
    }

    public function setSourceDetail(?array $sourceDetail): static
    {
        $this->sourceDetail = $sourceDetail;

        return $this;
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
}

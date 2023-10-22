<?php

namespace MWS\MoonManagerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use MWS\MoonManagerBundle\Repository\MwsContactRepository;
use Symfony\Component\Serializer\Annotation as Serializer;

#[ORM\Entity(repositoryClass: MwsContactRepository::class)]
#[ORM\Index(columns: ['username'])]
#[ORM\Index(columns: ['postalCode'])]
#[ORM\Index(columns: ['city'])]
#[ORM\Index(columns: ['email'])]
#[ORM\Index(columns: ['phone'])]
#[ORM\Index(columns: ['sourceName'])]
class MwsContact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $avatarUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(nullable: true)]
    private ?array $sourceDetail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sourceName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $businessUrl = null;

    #[ORM\OneToMany(mappedBy: 'contact', targetEntity: MwsContactTracking::class, cascade: ['persist'])]
    #[Serializer\Ignore]
    private Collection $mwsContactTrackings;

    #[ORM\ManyToMany(targetEntity: MwsOffer::class, mappedBy: 'contacts')]
    #[Serializer\Ignore]
    private Collection $mwsOffers;

    #[ORM\ManyToMany(targetEntity: MwsUser::class, mappedBy: 'comingFrom')]
    #[Serializer\Ignore]
    private Collection $mwsUsers;

    use TimestampableEntity;

    public function __construct()
    {
        $this->mwsContactTrackings = new ArrayCollection();
        $this->mwsOffers = new ArrayCollection();
        $this->mwsUsers = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->username;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): static
    {
        $this->avatarUrl = $avatarUrl;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

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

    public function getSourceName(): ?string
    {
        return $this->sourceName;
    }

    public function setSourceName(?string $sourceName): static
    {
        $this->sourceName = $sourceName;

        return $this;
    }

    public function getBusinessUrl(): ?string
    {
        return $this->businessUrl;
    }

    public function setBusinessUrl(?string $businessUrl): static
    {
        $this->businessUrl = $businessUrl;

        return $this;
    }

    /**
     * @return Collection<int, MwsContactTracking>
     */
    public function getMwsContactTrackings(): Collection
    {
        return $this->mwsContactTrackings;
    }

    public function addMwsContactTracking(MwsContactTracking $mwsContactTracking): static
    {
        if (!$this->mwsContactTrackings->contains($mwsContactTracking)) {
            $this->mwsContactTrackings->add($mwsContactTracking);
            $mwsContactTracking->setContact($this);
        }

        return $this;
    }

    public function removeMwsContactTracking(MwsContactTracking $mwsContactTracking): static
    {
        if ($this->mwsContactTrackings->removeElement($mwsContactTracking)) {
            // set the owning side to null (unless already changed)
            if ($mwsContactTracking->getContact() === $this) {
                $mwsContactTracking->setContact(null);
            }
        }

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
            $mwsOffer->addContact($this);
        }

        return $this;
    }

    public function removeMwsOffer(MwsOffer $mwsOffer): static
    {
        if ($this->mwsOffers->removeElement($mwsOffer)) {
            $mwsOffer->removeContact($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, MwsUser>
     */
    public function getMwsUsers(): Collection
    {
        return $this->mwsUsers;
    }

    public function addMwsUser(MwsUser $mwsUser): static
    {
        if (!$this->mwsUsers->contains($mwsUser)) {
            $this->mwsUsers->add($mwsUser);
            $mwsUser->addComingFrom($this);
        }

        return $this;
    }

    public function removeMwsUser(MwsUser $mwsUser): static
    {
        if ($this->mwsUsers->removeElement($mwsUser)) {
            $mwsUser->removeComingFrom($this);
        }

        return $this;
    }

}

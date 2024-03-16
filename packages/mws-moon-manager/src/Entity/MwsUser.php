<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use MWS\MoonManagerBundle\Repository\MwsUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer;
// use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: MwsUserRepository::class)]
#[ORM\Index(columns: ['username'])]
#[ORM\Index(columns: ['email'])]
#[ORM\Index(columns: ['roles'])]
class MwsUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public static $ROLE_USER = 'ROLE_USER';
    public static $ROLE_ADMIN = 'ROLE_MWS_ADMIN';
    public static $ROLE_DIRECTOR = 'ROLE_MWS_DIRECTOR';
    public static $ROLE_COMMERCIAL = 'ROLE_MWS_COMMERCIAL';
    public static $ROLE_PROSPECTOR = 'ROLE_MWS_PROSPECTOR';
    public static $ROLE_SUPPLIER = 'ROLE_MWS_SUPPLIER';
    public static $ROLE_CLIENT = 'ROLE_MWS_CLIENT';
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type:"string", length:25, unique:true)]
    private string $username;

    #[ORM\Column(type:"string", length:180, unique:true, nullable:true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type:"json")]
    private $roles = [];

    /**
     * @var string The hashed password
     */
    #[Serializer\Annotation\Ignore]
    #[ORM\Column(type:"string")]
    private $password;

    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'teamOwners')]
    #[Serializer\Annotation\Ignore]
    private Collection $teamMembers;

    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'teamMembers')]
    #[Serializer\Annotation\Ignore]
    private Collection $teamOwners;

    #[ORM\ManyToMany(targetEntity: MwsCalendarEvent::class, mappedBy: 'clients')]
    #[ORM\JoinTable(name: 'mws_client_event_mws_user')]
    #[Serializer\Annotation\Ignore]
    private Collection $mwsClientEvents;

    #[ORM\ManyToMany(targetEntity: MwsCalendarEvent::class, mappedBy: 'observers')]
    #[ORM\JoinTable(name: 'mws_observer_event_mws_user')]
    #[Serializer\Annotation\Ignore]
    private Collection $mwsObserverEvents;

    #[ORM\ManyToMany(targetEntity: MwsCalendarEvent::class, mappedBy: 'owners')]
    #[ORM\JoinTable(name: 'mws_owner_event_mws_user')]
    #[Serializer\Annotation\Ignore]
    private Collection $mwsOwnerEvents;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: MwsCalendarTracking::class)]
    #[Serializer\Annotation\Ignore]
    private Collection $mwsCalendarTrackings;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: MwsOfferTracking::class)]
    #[Serializer\Annotation\Ignore]
    private Collection $mwsOfferTrackings;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: MwsContactTracking::class)]
    #[Serializer\Annotation\Ignore]
    private Collection $mwsContactTrackings;

    #[ORM\ManyToMany(targetEntity: MwsContact::class, inversedBy: 'mwsUsers')]
    #[Serializer\Annotation\Ignore]
    private Collection $comingFrom;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: MwsMessage::class, orphanRemoval: true)]
    #[Serializer\Annotation\Ignore]
    private Collection $mwsMessages;

    #[ORM\ManyToMany(targetEntity: MwsTimeQualif::class, mappedBy: 'quickUserHistory')]
    private Collection $quickQualifHistory;

    use TimestampableEntity;
    // https://symfonycasts.com/screencast/symfony5-doctrine/bad-migrations

    // // Instead of :
    // #[ORM\Column(type: 'datetime_immutable')]
    // // #[Gedmo\Timestampable(on: 'change', field: ["createAt", "id"])]
    // #[Gedmo\Timestampable(on: 'update')]
    // private ?\DateTimeImmutable $updatedAt = null;

    // #[ORM\Column(type: 'datetime_immutable')]
    // #[Gedmo\Timestampable(on: 'create')]
    // private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->teamMembers = new ArrayCollection();
        $this->teamOwners = new ArrayCollection();
        $this->mwsClientEvents = new ArrayCollection();
        $this->mwsObserverEvents = new ArrayCollection();
        $this->mwsOwnerEvents = new ArrayCollection();
        $this->mwsCalendarTrackings = new ArrayCollection();
        $this->mwsOfferTrackings = new ArrayCollection();
        $this->mwsContactTrackings = new ArrayCollection();
        $this->comingFrom = new ArrayCollection();
        $this->mwsMessages = new ArrayCollection();
        $this->quickQualifHistory = new ArrayCollection();
    }

    public function __toString()
    {
        return ucfirst($this->username);
    }

    /*
    That's why in Symfony 5.3 we've decided to avoid this confusion and we've renamed "username" 
    to "user identifier". This might require some changes in your application code 
    (in 5.3 the old names still work but they are deprecated and in Symfony 6.0 they will be removed):
      UserInterface::getUsername() is now UserInterface::getUserIdentifier()
      loadUserByUsername() is now loadUserByUserIdentifier(), both in user loaders and user providers
      UsernameNotFoundException is now UserNotFoundException
    */
    // /**
    //  * @return string
    //  */
    // public function getUsername(): string // depreciated from 5.3
    // {
    //     return $this->username;
    // }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username): void
    {
        $this->username = $username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, self>
     */
    public function getTeamMembers(): Collection
    {
        return $this->teamMembers;
    }

    public function addTeamMember(self $teamMember): static
    {
        if (!$this->teamMembers->contains($teamMember)) {
            $this->teamMembers->add($teamMember);
        }

        return $this;
    }

    public function removeTeamMember(self $teamMember): static
    {
        $this->teamMembers->removeElement($teamMember);

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getTeamOwners(): Collection
    {
        return $this->teamOwners;
    }

    public function addTeamOwner(self $teamOwner): static
    {
        if (!$this->teamOwners->contains($teamOwner)) {
            $this->teamOwners->add($teamOwner);
            $teamOwner->addTeamMember($this);
        }

        return $this;
    }

    public function removeTeamOwner(self $teamOwner): static
    {
        if ($this->teamOwners->removeElement($teamOwner)) {
            $teamOwner->removeTeamMember($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, MwsCalendarEvent>
     */
    public function getMwsClientEvents(): Collection
    {
        return $this->mwsClientEvents;
    }

    public function addMwsClientEvent(MwsCalendarEvent $mwsClientEvent): static
    {
        if (!$this->mwsClientEvents->contains($mwsClientEvent)) {
            $this->mwsClientEvents->add($mwsClientEvent);
            $mwsClientEvent->addClient($this);
        }

        return $this;
    }

    public function removeMwsClientEvent(MwsCalendarEvent $mwsClientEvent): static
    {
        if ($this->mwsClientEvents->removeElement($mwsClientEvent)) {
            $mwsClientEvent->removeClient($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, MwsCalendarEvent>
     */
    public function getMwsObserverEvents(): Collection
    {
        return $this->mwsObserverEvents;
    }

    public function addMwsObserverEvent(MwsCalendarEvent $mwsObserverEvent): static
    {
        if (!$this->mwsObserverEvents->contains($mwsObserverEvent)) {
            $this->mwsObserverEvents->add($mwsObserverEvent);
            $mwsObserverEvent->addObserver($this);
        }

        return $this;
    }

    public function removeMwsObserverEvent(MwsCalendarEvent $mwsObserverEvent): static
    {
        if ($this->mwsObserverEvents->removeElement($mwsObserverEvent)) {
            $mwsObserverEvent->removeObserver($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, MwsCalendarEvent>
     */
    public function getMwsOwnerEvents(): Collection
    {
        return $this->mwsOwnerEvents;
    }

    public function addMwsOwnerEvent(MwsCalendarEvent $mwsOwnerEvent): static
    {
        if (!$this->mwsOwnerEvents->contains($mwsOwnerEvent)) {
            $this->mwsOwnerEvents->add($mwsOwnerEvent);
            $mwsOwnerEvent->addOwner($this);
        }

        return $this;
    }

    public function removeMwsOwnerEvent(MwsCalendarEvent $mwsOwnerEvent): static
    {
        if ($this->mwsOwnerEvents->removeElement($mwsOwnerEvent)) {
            $mwsOwnerEvent->removeOwner($this);
        }

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, MwsCalendarTracking>
     */
    public function getMwsCalendarTrackings(): Collection
    {
        return $this->mwsCalendarTrackings;
    }

    public function addMwsCalendarTracking(MwsCalendarTracking $mwsCalendarTracking): static
    {
        if (!$this->mwsCalendarTrackings->contains($mwsCalendarTracking)) {
            $this->mwsCalendarTrackings->add($mwsCalendarTracking);
            $mwsCalendarTracking->setOwner($this);
        }

        return $this;
    }

    public function removeMwsCalendarTracking(MwsCalendarTracking $mwsCalendarTracking): static
    {
        if ($this->mwsCalendarTrackings->removeElement($mwsCalendarTracking)) {
            // set the owning side to null (unless already changed)
            if ($mwsCalendarTracking->getOwner() === $this) {
                $mwsCalendarTracking->setOwner(null);
            }
        }

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
            $mwsOfferTracking->setOwner($this);
        }

        return $this;
    }

    public function removeMwsOfferTracking(MwsOfferTracking $mwsOfferTracking): static
    {
        if ($this->mwsOfferTrackings->removeElement($mwsOfferTracking)) {
            // set the owning side to null (unless already changed)
            if ($mwsOfferTracking->getOwner() === $this) {
                $mwsOfferTracking->setOwner(null);
            }
        }

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
            $mwsContactTracking->setOwner($this);
        }

        return $this;
    }

    public function removeMwsContactTracking(MwsContactTracking $mwsContactTracking): static
    {
        if ($this->mwsContactTrackings->removeElement($mwsContactTracking)) {
            // set the owning side to null (unless already changed)
            if ($mwsContactTracking->getOwner() === $this) {
                $mwsContactTracking->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MwsContact>
     */
    public function getComingFrom(): Collection
    {
        return $this->comingFrom;
    }

    public function addComingFrom(MwsContact $comingFrom): static
    {
        if (!$this->comingFrom->contains($comingFrom)) {
            $this->comingFrom->add($comingFrom);
        }

        return $this;
    }

    public function removeComingFrom(MwsContact $comingFrom): static
    {
        $this->comingFrom->removeElement($comingFrom);

        return $this;
    }

    /**
     * @return Collection<int, MwsMessage>
     */
    public function getMwsMessages(): Collection
    {
        return $this->mwsMessages;
    }

    public function addMwsMessage(MwsMessage $mwsMessage): static
    {
        if (!$this->mwsMessages->contains($mwsMessage)) {
            $this->mwsMessages->add($mwsMessage);
            $mwsMessage->setOwner($this);
        }

        return $this;
    }

    public function removeMwsMessage(MwsMessage $mwsMessage): static
    {
        if ($this->mwsMessages->removeElement($mwsMessage)) {
            // set the owning side to null (unless already changed)
            if ($mwsMessage->getOwner() === $this) {
                $mwsMessage->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MwsTimeQualif>
     */
    public function getQuickQualifHistory(): Collection
    {
        return $this->quickQualifHistory;
    }

    public function addQuickQualifHistory(MwsTimeQualif $quickQualifHistory): static
    {
        if (!$this->quickQualifHistory->contains($quickQualifHistory)) {
            $this->quickQualifHistory->add($quickQualifHistory);
            $quickQualifHistory->addQuickUserHistory($this);
        }

        return $this;
    }

    public function removeQuickQualifHistory(MwsTimeQualif $quickQualifHistory): static
    {
        if ($this->quickQualifHistory->removeElement($quickQualifHistory)) {
            $quickQualifHistory->removeQuickUserHistory($this);
        }

        return $this;
    }
}

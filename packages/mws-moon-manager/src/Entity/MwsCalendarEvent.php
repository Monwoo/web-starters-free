<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use MWS\MoonManagerBundle\Repository\MwsCalendarEventRepository;
use Gedmo\Mapping\Annotation\Timestampable;

#[ORM\Entity(repositoryClass: MwsCalendarEventRepository::class)]
#[ORM\Index(columns: ['start'])]
#[ORM\Index(columns: ['stop'])]
#[ORM\Index(columns: ['current_status_slug'])]
class MwsCalendarEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $start = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $stop = null;

    #[ORM\ManyToMany(targetEntity: MwsUser::class, inversedBy: 'mwsClientEvents')]
    #[ORM\JoinTable(name: 'mws_client_event_mws_user')]
    private Collection $clients;

    #[ORM\ManyToMany(targetEntity: MwsUser::class, inversedBy: 'mwsObserverEvents')]
    #[ORM\JoinTable(name: 'mws_observer_event_mws_user')]
    private Collection $observers;

    #[ORM\ManyToMany(targetEntity: MwsUser::class, inversedBy: 'mwsOwnerEvents')]
    #[ORM\JoinTable(name: 'mws_owner_event_mws_user')]
    private Collection $owners;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $currentStatusSlug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $currentComment = null;

    #[ORM\OneToMany(mappedBy: 'calendarEvent', targetEntity: MwsCalendarTracking::class)]
    private Collection $mwsCalendarTrackings;

    #[ORM\Column]
    #[Timestampable(on: 'update')]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    #[Timestampable(on: 'create')]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->clients = new ArrayCollection();
        $this->observers = new ArrayCollection();
        $this->owners = new ArrayCollection();
        $this->mwsCalendarTrackings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStart(): ?\DateTimeInterface
    {
        return $this->start;
    }

    public function setStart(?\DateTimeInterface $start): static
    {
        $this->start = $start;

        return $this;
    }

    public function getStop(): ?\DateTimeInterface
    {
        return $this->stop;
    }

    public function setStop(?\DateTimeInterface $stop): static
    {
        $this->stop = $stop;

        return $this;
    }

    /**
     * @return Collection<int, MwsUser>
     */
    public function getClients(): Collection
    {
        return $this->clients;
    }

    public function addClient(MwsUser $client): static
    {
        if (!$this->clients->contains($client)) {
            $this->clients->add($client);
        }

        return $this;
    }

    public function removeClient(MwsUser $client): static
    {
        $this->clients->removeElement($client);

        return $this;
    }

    /**
     * @return Collection<int, MwsUser>
     */
    public function getObservers(): Collection
    {
        return $this->observers;
    }

    public function addObserver(MwsUser $observer): static
    {
        if (!$this->observers->contains($observer)) {
            $this->observers->add($observer);
        }

        return $this;
    }

    public function removeObserver(MwsUser $observer): static
    {
        $this->observers->removeElement($observer);

        return $this;
    }

    /**
     * @return Collection<int, MwsUser>
     */
    public function getOwners(): Collection
    {
        return $this->owners;
    }

    public function addOwner(MwsUser $owner): static
    {
        if (!$this->owners->contains($owner)) {
            $this->owners->add($owner);
        }

        return $this;
    }

    public function removeOwner(MwsUser $owner): static
    {
        $this->owners->removeElement($owner);

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

    public function getCurrentComment(): ?string
    {
        return $this->currentComment;
    }

    public function setCurrentComment(?string $currentComment): static
    {
        $this->currentComment = $currentComment;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

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
            $mwsCalendarTracking->setCalendarEvent($this);
        }

        return $this;
    }

    public function removeMwsCalendarTracking(MwsCalendarTracking $mwsCalendarTracking): static
    {
        if ($this->mwsCalendarTrackings->removeElement($mwsCalendarTracking)) {
            // set the owning side to null (unless already changed)
            if ($mwsCalendarTracking->getCalendarEvent() === $this) {
                $mwsCalendarTracking->setCalendarEvent(null);
            }
        }

        return $this;
    }
}

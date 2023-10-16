<?php

namespace MWS\MoonManagerBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use MWS\MoonManagerBundle\Repository\MwsCalendarTrackingRepository;
use Gedmo\Mapping\Annotation\Timestampable;

#[ORM\Entity(repositoryClass: MwsCalendarTrackingRepository::class)]
class MwsCalendarTracking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'mwsCalendarTrackings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MwsCalendarEvent $calendarEvent = null;

    #[ORM\ManyToOne(inversedBy: 'mwsCalendarTrackings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MwsUser $owner = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statusSlug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column]
    #[Timestampable(on: 'update')]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    #[Timestampable(on: 'create')]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCalendarEvent(): ?MwsCalendarEvent
    {
        return $this->calendarEvent;
    }

    public function setCalendarEvent(?MwsCalendarEvent $calendarEvent): static
    {
        $this->calendarEvent = $calendarEvent;

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

    public function getStatusSlug(): ?string
    {
        return $this->statusSlug;
    }

    public function setStatusSlug(?string $statusSlug): static
    {
        $this->statusSlug = $statusSlug;

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
}

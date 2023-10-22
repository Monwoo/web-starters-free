<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use MWS\MoonManagerBundle\Repository\MwsContactTrackingRepository;

#[ORM\Entity(repositoryClass: MwsContactTrackingRepository::class)]
class MwsContactTracking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'mwsContactTrackings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MwsContact $contact = null;

    #[ORM\ManyToOne(inversedBy: 'mwsContactTrackings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MwsUser $owner = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    use TimestampableEntity;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContact(): ?MwsContact
    {
        return $this->contact;
    }

    public function setContact(?MwsContact $contact): static
    {
        $this->contact = $contact;

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

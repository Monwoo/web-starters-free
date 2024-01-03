<?php

namespace MWS\MoonManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MWS\MoonManagerBundle\Repository\MwsMessageRepository;

#[ORM\Entity(repositoryClass: MwsMessageRepository::class)]
class MwsMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $projectId = null;

    #[ORM\Column(length: 255)]
    private ?string $destId = null;

    #[ORM\Column(nullable: true)]
    private ?float $monwooAmount = null;

    #[ORM\Column(nullable: true)]
    private ?float $projectDelayInOpenDays = null;

    #[ORM\Column(nullable: true)]
    private ?bool $asNewOffer = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sourceId = null;

    #[ORM\Column(nullable: true)]
    private ?array $crmLogs = null;

    #[ORM\Column(nullable: true)]
    private ?array $messages = null;

    #[ORM\ManyToOne(inversedBy: 'mwsMessages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MwsUser $owner = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjectId(): ?string
    {
        return $this->projectId;
    }

    public function setProjectId(?string $projectId): static
    {
        $this->projectId = $projectId;

        return $this;
    }

    public function getDestId(): ?string
    {
        return $this->destId;
    }

    public function setDestId(?string $destId): static
    {
        $this->destId = $destId;

        return $this;
    }

    public function getMonwooAmount(): ?float
    {
        return $this->monwooAmount;
    }

    public function setMonwooAmount(?float $monwooAmount): static
    {
        $this->monwooAmount = $monwooAmount;

        return $this;
    }

    public function getProjectDelayInOpenDays(): ?float
    {
        return $this->projectDelayInOpenDays;
    }

    public function setProjectDelayInOpenDays(?float $projectDelayInOpenDays): static
    {
        $this->projectDelayInOpenDays = $projectDelayInOpenDays;

        return $this;
    }

    public function isAsNewOffer(): ?bool
    {
        return $this->asNewOffer;
    }

    public function setAsNewOffer(?bool $asNewOffer): static
    {
        $this->asNewOffer = $asNewOffer;

        return $this;
    }

    public function getSourceId(): ?string
    {
        return $this->sourceId;
    }

    public function setSourceId(?string $sourceId): static
    {
        $this->sourceId = $sourceId;

        return $this;
    }

    public function getCrmLogs(): ?array
    {
        return $this->crmLogs;
    }

    public function setCrmLogs(?array $crmLogs): static
    {
        $this->crmLogs = $crmLogs;

        return $this;
    }

    public function getMessages(): ?array
    {
        return $this->messages;
    }

    public function setMessages(?array $messages): static
    {
        $this->messages = $messages;

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
}

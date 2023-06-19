<?php

namespace App\Entity;

use App\Repository\BillingConfigRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BillingConfigRepository::class)]
class BillingConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clientName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $quotationNumber = null;

    #[ORM\Column(length: 255)]
    private ?string $clientSlug = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientName(): ?string
    {
        return $this->clientName;
    }

    public function setClientName(string $clientName): static
    {
        $this->clientName = $clientName;

        return $this;
    }

    public function getQuotationNumber(): ?string
    {
        return $this->quotationNumber;
    }

    public function setQuotationNumber(?string $quotationNumber): static
    {
        $this->quotationNumber = $quotationNumber;

        return $this;
    }

    public function getClientSlug(): ?string
    {
        return $this->clientSlug;
    }

    public function setClientSlug(string $clientSlug): static
    {
        $this->clientSlug = $clientSlug;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\BillingConfigRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BillingConfigRepository::class)]
class BillingConfig
{
    // #[ORM\Id]
    // #[ORM\GeneratedValue]
    // #[ORM\Column]
    // private ?int $id = null;
    #[ORM\Id]
    #[ORM\Column(length: 255, unique: true)]
    private ?string $clientSlug = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clientName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $quotationNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clientEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clientTel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clientSIRET = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clientTvaIntracom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clientAddressL1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clientAddressL2 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $clientWebsite = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $clientLogoUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $businessLogo = null;

    #[ORM\Column(nullable: true)]
    private ?float $businessWorkloadHours = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $businessWorkloadDetails = null;

    #[ORM\Column(type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $quotationStartDay = null;

    #[ORM\Column(type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $quotationEndDay = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientName(): ?string
    {
        return $this->clientName;
    }

    public function setClientName(?string $clientName): static
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

    public function getClientEmail(): ?string
    {
        return $this->clientEmail;
    }

    public function setClientEmail(?string $clientEmail): static
    {
        $this->clientEmail = $clientEmail;

        return $this;
    }

    public function getClientTel(): ?string
    {
        return $this->clientTel;
    }

    public function setClientTel(?string $clientTel): static
    {
        $this->clientTel = $clientTel;

        return $this;
    }

    public function getClientSIRET(): ?string
    {
        return $this->clientSIRET;
    }

    public function setClientSIRET(?string $clientSIRET): static
    {
        $this->clientSIRET = $clientSIRET;

        return $this;
    }

    public function getClientTvaIntracom(): ?string
    {
        return $this->clientTvaIntracom;
    }

    public function setClientTvaIntracom(?string $clientTvaIntracom): static
    {
        $this->clientTvaIntracom = $clientTvaIntracom;

        return $this;
    }

    public function getClientAddressL1(): ?string
    {
        return $this->clientAddressL1;
    }

    public function setClientAddressL1(?string $clientAddressL1): static
    {
        $this->clientAddressL1 = $clientAddressL1;

        return $this;
    }

    public function getClientAddressL2(): ?string
    {
        return $this->clientAddressL2;
    }

    public function setClientAddressL2(?string $clientAddressL2): static
    {
        $this->clientAddressL2 = $clientAddressL2;

        return $this;
    }

    public function getClientWebsite(): ?string
    {
        return $this->clientWebsite;
    }

    public function setClientWebsite(?string $clientWebsite): static
    {
        $this->clientWebsite = $clientWebsite;

        return $this;
    }

    public function getClientLogoUrl(): ?string
    {
        return $this->clientLogoUrl;
    }

    public function setClientLogoUrl(?string $clientLogoUrl): static
    {
        $this->clientLogoUrl = $clientLogoUrl;

        return $this;
    }

    public function getBusinessLogo(): ?string
    {
        return $this->businessLogo;
    }

    public function setBusinessLogo(?string $businessLogo): static
    {
        $this->businessLogo = $businessLogo;

        return $this;
    }

    public function getBusinessWorkloadHours(): ?float
    {
        return $this->businessWorkloadHours;
    }

    public function setBusinessWorkloadHours(?float $businessWorkloadHours): static
    {
        $this->businessWorkloadHours = $businessWorkloadHours;

        return $this;
    }

    public function getBusinessWorkloadDetails(): ?string
    {
        return $this->businessWorkloadDetails;
    }

    public function setBusinessWorkloadDetails(?string $businessWorkloadDetails): static
    {
        $this->businessWorkloadDetails = $businessWorkloadDetails;

        return $this;
    }

    public function getQuotationStartDay(): ?\DateTimeInterface
    {
        return $this->quotationStartDay;
    }

    public function setQuotationStartDay(?\DateTimeInterface $quotationStartDay): static
    {
        $this->quotationStartDay = $quotationStartDay;

        return $this;
    }

    public function getQuotationEndDay(): ?\DateTimeInterface
    {
        return $this->quotationEndDay;
    }

    public function setQuotationEndDay(?\DateTimeInterface $quotationEndDay): static
    {
        $this->quotationEndDay = $quotationEndDay;

        return $this;
    }
}

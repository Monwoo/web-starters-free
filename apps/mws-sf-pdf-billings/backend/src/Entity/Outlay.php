<?php

namespace App\Entity;

use App\Repository\OutlayRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OutlayRepository::class)]
class Outlay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $providerName = null;

    #[ORM\Column(nullable: true)]
    private ?bool $insertPageBreakBefore = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $providerShortDescription = null;

    // TODO : doc : percentOnBusinessTotal will give the ProviderAmount WITH TAXES
    // to get from the client to outlay at business quotation owner....
    // $providerTaxes is only some indicator for acountings, provider billings will
    // details taxes if somes have to be paied for this provider...
    #[ORM\Column(nullable: true)]
    private ?float $percentOnBusinessTotal = null;

    // TODO : doc : count ALL Taxes => providerAddedPrice is WITHOUT taxes.
    // providerTaxes Sum Taxes for providerAddedPrice and
    // for possible percentOnBusinessTotal amounts (BusinessTotal will be counted WITHOUT TAXES)
    #[ORM\Column(nullable: true)]
    private ?float $providerTaxes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $providerDetails = null;

    #[ORM\Column(nullable: true)]
    private ?float $providerAddedPrice = null;

    #[ORM\Column(nullable: true)]
    private ?bool $useProviderAddedPriceForBusiness = null;

    // TODO : doc : forseen can be use to give a global budget amount without
    // taking the money at the business domain
    // (letting it to be usable from client domain directly....)
    #[ORM\Column(nullable: true)]
    private ?float $providerTotalWithTaxesForseenForClient = null;

    // #[ORM\ManyToMany(targetEntity: BillingConfig::class, mappedBy: 'outlays', cascade:['remove', 'persist'])]
    #[ORM\ManyToMany(targetEntity: BillingConfig::class, mappedBy: 'outlays', cascade:['persist'])]
    private Collection $billingConfigs;

    // // https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/association-mapping.html#one-to-many-unidirectional-with-join-table
    // #[ORM\ManyToMany(targetEntity: BillingConfig::class, mappedBy: 'outlays')]
    // private Collection $billingConfigs;

    public function __construct()
    {
        $this->billingConfigs = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getProviderName() . " ["
        . $this->getId() . "]";
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProviderName(): ?string
    {
        return $this->providerName;
    }

    public function setProviderName(string $providerName): static
    {
        $this->providerName = $providerName;

        return $this;
    }

    public function getPercentOnBusinessTotal(): ?float
    {
        return $this->percentOnBusinessTotal;
    }

    public function setPercentOnBusinessTotal(?float $percentOnBusinessTotal): static
    {
        $this->percentOnBusinessTotal = $percentOnBusinessTotal;

        return $this;
    }

    public function getProviderAddedPrice(): ?float
    {
        return $this->providerAddedPrice;
    }

    public function setProviderAddedPrice(?float $providerAddedPrice): static
    {
        $this->providerAddedPrice = $providerAddedPrice;

        return $this;
    }

    public function getProviderDetails(): ?string
    {
        return $this->providerDetails;
    }

    public function setProviderDetails(?string $providerDetails): static
    {
        $this->providerDetails = $providerDetails;

        return $this;
    }

    public function getProviderTaxes(): ?float
    {
        return $this->providerTaxes;
    }

    public function setProviderTaxes(?float $providerTaxes): static
    {
        $this->providerTaxes = $providerTaxes;

        return $this;
    }

    /**
     * @return Collection<int, BillingConfig>
     */
    public function getBillingConfigs(): Collection
    {
        return $this->billingConfigs;
    }

    public function addBillingConfig(BillingConfig $billingConfig): static
    {
        if (!$this->billingConfigs->contains($billingConfig)) {
            $this->billingConfigs->add($billingConfig);
            $billingConfig->addOutlay($this);
        }

        return $this;
    }

    public function removeBillingConfig(BillingConfig $billingConfig): static
    {
        if ($this->billingConfigs->removeElement($billingConfig)) {
            $billingConfig->removeOutlay($this);
        }

        return $this;
    }

    public function isUseProviderAddedPriceForBusiness(): ?bool
    {
        return $this->useProviderAddedPriceForBusiness;
    }

    public function setUseProviderAddedPriceForBusiness(?bool $useProviderAddedPriceForBusiness): static
    {
        $this->useProviderAddedPriceForBusiness = $useProviderAddedPriceForBusiness;

        return $this;
    }

    public function getProviderTotalWithTaxesForseenForClient(): ?float
    {
        return $this->providerTotalWithTaxesForseenForClient;
    }

    public function setProviderTotalWithTaxesForseenForClient(?float $providerTotalWithTaxesForseenForClient): static
    {
        $this->providerTotalWithTaxesForseenForClient = $providerTotalWithTaxesForseenForClient;

        return $this;
    }
    

    public function isInsertPageBreakBefore(): ?bool
    {
        return $this->insertPageBreakBefore;
    }

    public function setInsertPageBreakBefore(?bool $insertPageBreakBefore): static
    {
        $this->insertPageBreakBefore = $insertPageBreakBefore;

        return $this;
    }

    public function getProviderShortDescription(): ?string
    {
        return $this->providerShortDescription;
    }

    public function setProviderShortDescription(?string $providerShortDescription): static
    {
        $this->providerShortDescription = $providerShortDescription;

        return $this;
    }

}

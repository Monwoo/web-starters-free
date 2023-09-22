<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace App\Entity;

use App\Entity\Traits\ItemLayoutTrait;
use App\Repository\OutlayRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer;

#[ORM\Entity(repositoryClass: OutlayRepository::class)]
class Outlay
{
    use ItemLayoutTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $providerName = null;

    ///////////////////////////////////
    // 🇺🇸🇺🇸 taken from BUSINESS outlay properties :

    // TODO : doc : percentOnBusinessTotal will give the ProviderAmount WITH TAXES
    // to get from the client to outlay at business quotation owner....
    // $providerAddedPriceTaxes is only some indicator for acountings, provider billings will
    // details taxes if somes have to be paied for this provider...
    // It will NOT COUNT for business total, use providerAddedPrice for that
    #[ORM\Column(nullable: true)]
    private ?float $percentOnBusinessTotal = null;

    // TODO : doc : used to inform clients about possible taxes on the
    // Provider services included in the percentage of business total...
    #[ORM\Column(nullable: true)]
    private ?float $taxesPercentIncludedInPercentOnBusinessTotal = null;

    // TODO : doc : used to inform clients about possible taxes on the
    // Provider services added in the percentage of business total...
    #[ORM\Column(nullable: true)]
    private ?float $taxesPercentAddedToPercentOnBusinessTotal = null;

    ///////////////////////////////////
    // 🇺🇸🇺🇸 given to the PROVIDER outlay properties :

    // TODO : doc : forseen can be use to give a global budget amount without
    // taking the money in the business domain (Monwoo for this starter demonstration)
    // (letting it to be paid and used from the client domain directly....)
    // This kind of outlay is like an independant add for the targeted provider
    #[ORM\Column(nullable: true)]
    private ?float $providerTotalWithTaxesForseenForClient = null;

    ///////////////////////////////////
    // 🇺🇸🇺🇸 given to the PROVIDER through BUSINESS outlay properties :

    // TODO : doc : Price WITHOUT TAXES added to business outlay (will ask for it
    // to be paid to the business bank account that will lay back to the provider)
    #[ORM\Column(nullable: true)]
    private ?float $providerAddedPrice = null;

    // TODO : doc : count ALL Taxes => providerAddedPrice is WITHOUT taxes.
    // providerAddedPriceTaxes Sum Taxes for providerAddedPrice
    // It will NOT BE USED if providerAddedPriceTaxesPercent is other than 0.
    // It will NOT count for possible amounts due to percentOnBusinessTotal values
    // (BusinessTotal will be counted WITHOUT TAXES)
    // use use RAW value, since provider may use MULTIPLE taxes factor, depending of
    // their bills, so we only summary the global TVA amount for client's awarness.
    // The provider bills or quotation will take the détails or change this value
    // so it's not realy some commitment, but we need to know since we want
    // to show the right price for the full package with taxes.
    #[ORM\Column(nullable: true)]
    private ?float $providerAddedPriceTaxes = null;

    // TODO : doc : Percent to use to count taxes on providerAddedPrice 
    // that is without taxes. It will OVERWRITE providerAddedPriceTaxes
    // values since one have to be chosen if both show different results
    // (and/or for optimisation purposes)
    #[ORM\Column(nullable: true)]
    private ?float $providerAddedPriceTaxesPercent = null;

    // TODO : doc : it TRUE, will use the added price be useed
    // to count values at the business total (it will count for
    // other outlay using the percentOnBusinessTotal factor)
    #[ORM\Column(nullable: true)]
    private ?bool $useProviderAddedPriceForBusiness = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $providerShortDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $providerDetails = null;

    // #[ORM\ManyToMany(targetEntity: BillingConfig::class, mappedBy: 'outlays', cascade:['remove', 'persist'])]
    #[Serializer\Annotation\Ignore]
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

    public function getTaxesPercentIncludedInPercentOnBusinessTotal(): ?float
    {
        return $this->taxesPercentIncludedInPercentOnBusinessTotal;
    }

    public function setTaxesPercentIncludedInPercentOnBusinessTotal(?float $taxesPercentIncludedInPercentOnBusinessTotal): static
    {
        $this->taxesPercentIncludedInPercentOnBusinessTotal = $taxesPercentIncludedInPercentOnBusinessTotal;

        return $this;
    }

    public function getTaxesPercentAddedToPercentOnBusinessTotal(): ?float
    {
        return $this->taxesPercentAddedToPercentOnBusinessTotal;
    }

    public function setTaxesPercentAddedToPercentOnBusinessTotal(?float $taxesPercentAddedToPercentOnBusinessTotal): static
    {
        $this->taxesPercentAddedToPercentOnBusinessTotal = $taxesPercentAddedToPercentOnBusinessTotal;

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

    public function getProviderAddedPrice(): ?float
    {
        return $this->providerAddedPrice;
    }

    public function setProviderAddedPrice(?float $providerAddedPrice): static
    {
        $this->providerAddedPrice = $providerAddedPrice;

        return $this;
    }

    public function getProviderAddedPriceTaxes(): ?float
    {
        return $this->providerAddedPriceTaxes;
    }

    public function setProviderAddedPriceTaxes(?float $providerAddedPriceTaxes): static
    {
        $this->providerAddedPriceTaxes = $providerAddedPriceTaxes;

        return $this;
    }

    public function getProviderAddedPriceTaxesPercent(): ?float
    {
        return $this->providerAddedPriceTaxesPercent;
    }

    public function setProviderAddedPriceTaxesPercent(?float $providerAddedPriceTaxesPercent): static
    {
        $this->providerAddedPriceTaxesPercent = $providerAddedPriceTaxesPercent;

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

    public function getProviderShortDescription(): ?string
    {
        return $this->providerShortDescription;
    }

    public function setProviderShortDescription(?string $providerShortDescription): static
    {
        $this->providerShortDescription = $providerShortDescription;

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

}

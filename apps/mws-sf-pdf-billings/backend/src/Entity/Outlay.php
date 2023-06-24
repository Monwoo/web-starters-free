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
    private ?float $percentOnBusinessTotal = null;

    #[ORM\Column(nullable: true)]
    private ?float $providerAddedPrice = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $providerDetails = null;

    #[ORM\Column(nullable: true)]
    private ?float $providerTaxes = null;

    #[ORM\ManyToMany(targetEntity: BillingConfig::class, mappedBy: 'outlays', cascade:['remove', 'persist'])]
    private Collection $billingConfigs;

    // // https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/association-mapping.html#one-to-many-unidirectional-with-join-table
    // #[ORM\ManyToMany(targetEntity: BillingConfig::class, mappedBy: 'outlays')]
    // private Collection $billingConfigs;

    public function __construct()
    {
        $this->billingConfigs = new ArrayCollection();
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
}

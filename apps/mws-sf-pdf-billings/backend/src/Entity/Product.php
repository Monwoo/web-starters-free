<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace App\Entity;

use App\Entity\Traits\ItemLayoutTrait;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $label = null;

    #[ORM\Column(nullable: true)]
    private ?float $quantity = null;

    #[ORM\Column(nullable: true)]
    private ?float $pricePerUnitWithoutTaxes = null;

    #[ORM\Column(nullable: true)]
    private ?float $taxesPercent = null;

    #[ORM\Column(nullable: true)]
    private ?float $discountPercent = null;

    #[ORM\Column(nullable: true)]
    private ?bool $usedForBusinessTotal = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $leftTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $leftDetails = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rightDetails = null;

    #[Serializer\Annotation\Ignore]
    #[ORM\ManyToMany(targetEntity: BillingConfig::class, inversedBy: 'products', cascade:['persist'])]
    private Collection $billings;

    use ItemLayoutTrait;

    public function __construct()
    {
        $this->billings = new ArrayCollection();
    }

    public function getDiscountOfDiscountPercent($discount) {
        // TIPS : force float conversion to ignore about possible discountPercent null value...
        // var_dump((1 - (1.0 - ($discount ?? 0.0)) * (1.0 - $this->getDiscountPercent())));
        return (1 - (1.0 - ($discount ?? 0.0)) * (1.0 - $this->getDiscountPercent()));
    }

    public function getDiscountOfPriceWithoutTaxes($discount) {
        // $discount = $this->getDiscountOfDiscountPercent($discount);
        // var_dump($d); exit;
        return $this->getPricePerUnitWithoutTaxes() * $this->getQuantity()
        * (1 - $discount);
    }

    public function getDiscountOfPriceWithTaxes($discount) {
        $p = $this->getDiscountOfPriceWithoutTaxes($discount);
        return $p * (1 + $this->getTaxesPercent());
    }

    // GENERATED SETTER :

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(?float $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPricePerUnitWithoutTaxes(): ?float
    {
        return $this->pricePerUnitWithoutTaxes;
    }

    public function setPricePerUnitWithoutTaxes(?float $pricePerUnitWithoutTaxes): static
    {
        $this->pricePerUnitWithoutTaxes = $pricePerUnitWithoutTaxes;

        return $this;
    }

    public function getTaxesPercent(): ?float
    {
        return $this->taxesPercent;
    }

    public function setTaxesPercent(?float $taxesPercent): static
    {
        $this->taxesPercent = $taxesPercent;

        return $this;
    }

    public function getDiscountPercent(): ?float
    {
        return $this->discountPercent;
    }

    public function setDiscountPercent(?float $discountPercent): static
    {
        $this->discountPercent = $discountPercent;

        return $this;
    }

    public function isUsedForBusinessTotal(): ?bool
    {
        return $this->usedForBusinessTotal;
    }

    public function setUsedForBusinessTotal(?bool $usedForBusinessTotal): static
    {
        $this->usedForBusinessTotal = $usedForBusinessTotal;

        return $this;
    }

    public function getLeftTitle(): ?string
    {
        return $this->leftTitle;
    }

    public function setLeftTitle(?string $leftTitle): static
    {
        $this->leftTitle = $leftTitle;

        return $this;
    }

    public function getLeftDetails(): ?string
    {
        return $this->leftDetails;
    }

    public function setLeftDetails(?string $leftDetails): static
    {
        $this->leftDetails = $leftDetails;

        return $this;
    }

    public function getRightDetails(): ?string
    {
        return $this->rightDetails;
    }

    public function setRightDetails(?string $rightDetails): static
    {
        $this->rightDetails = $rightDetails;

        return $this;
    }

    /**
     * @return Collection<int, BillingConfig>
     */
    public function getBillings(): Collection
    {
        return $this->billings;
    }

    public function addBilling(BillingConfig $billing): static
    {
        if (!$this->billings->contains($billing)) {
            $this->billings->add($billing);
        }

        return $this;
    }

    public function removeBilling(BillingConfig $billing): static
    {
        $this->billings->removeElement($billing);

        return $this;
    }

}

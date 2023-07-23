<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: '`transaction`')]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(nullable: true)]
    private ?float $priceWithoutTaxes = null;

    #[ORM\Column(nullable: true)]
    private ?float $addedTaxes = null;

    #[ORM\Column(length: 255)]
    private ?string $paymentMethod = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    private ?BillingConfig $billingConfig = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getPriceWithoutTaxes(): ?float
    {
        return $this->priceWithoutTaxes;
    }

    public function setPriceWithoutTaxes(?float $priceWithoutTaxes): static
    {
        $this->priceWithoutTaxes = $priceWithoutTaxes;

        return $this;
    }

    public function getAddedTaxes(): ?float
    {
        return $this->addedTaxes;
    }

    public function setAddedTaxes(?float $addedTaxes): static
    {
        $this->addedTaxes = $addedTaxes;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getBillingConfig(): ?BillingConfig
    {
        return $this->billingConfig;
    }

    public function setBillingConfig(?BillingConfig $billingConfig): static
    {
        $this->billingConfig = $billingConfig;

        return $this;
    }
}

<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: '`transaction`')]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $paymentMethod = null;

    #[ORM\Column(type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $receptionDate = null;

    #[ORM\Column(length: 255)]
    private ?string $receptionNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(nullable: true)]
    private ?float $priceWithoutTaxes = null;

    #[ORM\Column(nullable: true)]
    private ?float $addedTaxes = null;

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

    public function getReceptionDate(): ?\DateTimeInterface
    {
        return $this->receptionDate;
    }

    public function setReceptionDate(\DateTimeInterface $receptionDate): static
    {
        $this->receptionDate = $receptionDate;

        return $this;
    }

    public function getReceptionNumber(): ?string
    {
        return $this->receptionNumber;
    }

    public function setReceptionNumber(string $receptionNumber): static
    {
        $this->receptionNumber = $receptionNumber;

        return $this;
    }
}

<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace App\Entity;

use App\Repository\BillingConfigRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
// use Symfony\Component\Serializer;

#[ORM\Entity(repositoryClass: BillingConfigRepository::class)]
#[ORM\Index(columns: ['client_slug'], name: 'client_slug_idx')]
class BillingConfig
{
    // #[Serializer\Annotation\Ignore]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // #[ORM\Id] // TODO : not good idea to avoid ID duplication ?
    // only using clientSlug and removing id
    // sound like 'clientSlug' need to be called 'id' otherwise all other stuff 
    // assuming entity have id will breaks... (same if ID go from integer to string ?)
    #[ORM\Column(length: 255, unique: true)]
    private ?string $clientSlug = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clientName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $documentType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $quotationTemplate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $quotationNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $quotationSourceNumber = null;

    #[ORM\Column(nullable: true)]
    private ?float $quotationAmount = null;

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
    private ?string $businessAim = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $businessWorkloadDetails = null;

    #[ORM\Column(type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $quotationStartDay = null;

    #[ORM\Column(type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $quotationEndDay = null;

    #[ORM\Column(nullable: true)]
    private ?float $percentDiscount = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $marginBeforeStartItem = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $marginAfterStartItem = null;

    #[ORM\Column(nullable: true)]
    private ?bool $pageBreakAfterStartItem = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $marginBeforeEndItem = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $marginAfterEndItem = null;

    #[ORM\Column(nullable: true)]
    private ?bool $pageBreakAfterEndItem = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $marginBeforeTotal = null;

    #[ORM\Column(nullable: true)]
    private ?bool $hideDefaultOutlaysOnEmptyOutlays = null;

    // TODO : WORK on ManyToMany relationships for Entity WITHOUT id FIELD (replaced by 'client_slug' normally ...)
    // #[ORM\JoinColumn(name: 'billing_config_client_slug', referencedColumnName: 'id')]
    // #[ORM\InverseJoinColumn(name: 'outlay_id', referencedColumnName: 'id')]
    // #[ORM\ManyToMany(targetEntity: Outlay::class, inversedBy: 'billingConfigs', cascade:['persist', 'remove'])]
    #[ORM\ManyToMany(targetEntity: Outlay::class, inversedBy: 'billingConfigs', cascade:['persist'])]
    private Collection $outlays;

    #[ORM\OneToMany(mappedBy: 'billingConfig', targetEntity: Transaction::class, cascade:["persist"])]
    private Collection $transactions;

    #[ORM\ManyToMany(targetEntity: Product::class, mappedBy: 'billings', cascade:['persist'])]
    private Collection $products;

    // // TODO : Why is 'specifying' inversedBy make all migration fail on error : 
    // // Column name "id" referenced for relation from App\Entity\BillingConfig
    // // towards App\Entity\Outlay does not exist.
    // // https://stackoverflow.com/questions/66533631/symfony-doctrine-rename-table-used-for-manytomany
    // // https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/association-mapping.html#many-to-many-bidirectional
    // // #[ORM\ManyToMany(targetEntity: Outlay::class, inversedBy: 'billingConfigs')]
    // // #[JoinTable(name: 'billing_configs_outlays')]
    // #[JoinColumn(name: 'billing_config_client_slug', referencedColumnName: 'id')]
    // #[InverseJoinColumn(name: 'outlay_id', referencedColumnName: 'id')]
    // #[ManyToMany(targetEntity: Outlay::class, inversedBy: 'billingConfigs')]
    // private Collection $outlays;

    public function __construct()
    {
        $this->outlays = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->products = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getClientSlug();
    }

    // For serializer to show app version used to build the data...
    public function getAppInfos()
    {
        $rootPackage = \Composer\InstalledVersions::getRootPackage();

        $packageVersion = $rootPackage['pretty_version'] ?? $rootPackage['version'];
        $packageName = array_slice(explode("monwoo/", $rootPackage['name']), -1)[0];
        $isDev = $rootPackage['dev'] ?? false;

        return [
            "packageVersion" => $packageVersion,
            "packageName" => $packageName,
            "isDev" => $isDev,
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getClientName(): ?string
    {
        return $this->clientName;
    }

    public function setClientName(?string $clientName): static
    {
        $this->clientName = $clientName;

        return $this;
    }

    public function getDocumentType(): ?string
    {
        return $this->documentType;
    }

    public function setDocumentType(?string $documentType): static
    {
        $this->documentType = $documentType;

        return $this;
    }

    public function getQuotationTemplate(): ?string
    {
        return $this->quotationTemplate;
    }

    public function setQuotationTemplate(?string $quotationTemplate): static
    {
        $this->quotationTemplate = $quotationTemplate;

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

    public function getQuotationSourceNumber(): ?string
    {
        return $this->quotationSourceNumber;
    }

    public function setQuotationSourceNumber(?string $quotationSourceNumber): static
    {
        $this->quotationSourceNumber = $quotationSourceNumber;

        return $this;
    }

    public function getQuotationAmount(): ?float
    {
        return $this->quotationAmount;
    }

    public function setQuotationAmount(?float $quotationAmount): static
    {
        $this->quotationAmount = $quotationAmount;

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

    public function getBusinessAim(): ?string
    {
        return $this->businessAim;
    }

    public function setBusinessAim(?string $businessAim): static
    {
        $this->businessAim = $businessAim;

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

    public function getPercentDiscount(): ?float
    {
        return $this->percentDiscount;
    }

    public function setPercentDiscount(?float $percentDiscount): static
    {
        $this->percentDiscount = $percentDiscount;

        return $this;
    }

    public function getMarginBeforeStartItem(): ?string
    {
        return $this->marginBeforeStartItem;
    }

    public function setMarginBeforeStartItem(?string $marginBeforeStartItem): static
    {
        $this->marginBeforeStartItem = $marginBeforeStartItem;

        return $this;
    }

    public function getMarginAfterStartItem(): ?string
    {
        return $this->marginAfterStartItem;
    }

    public function setMarginAfterStartItem(?string $marginAfterStartItem): static
    {
        $this->marginAfterStartItem = $marginAfterStartItem;

        return $this;
    }

    public function isPageBreakAfterStartItem(): ?bool
    {
        return $this->pageBreakAfterStartItem;
    }

    public function setPageBreakAfterStartItem(?bool $pageBreakAfterStartItem): static
    {
        $this->pageBreakAfterStartItem = $pageBreakAfterStartItem;

        return $this;
    }

    public function getMarginBeforeEndItem(): ?string
    {
        return $this->marginBeforeEndItem;
    }

    public function setMarginBeforeEndItem(?string $marginBeforeEndItem): static
    {
        $this->marginBeforeEndItem = $marginBeforeEndItem;

        return $this;
    }

    public function getMarginAfterEndItem(): ?string
    {
        return $this->marginAfterEndItem;
    }

    public function setMarginAfterEndItem(?string $marginAfterEndItem): static
    {
        $this->marginAfterEndItem = $marginAfterEndItem;

        return $this;
    }

    public function isPageBreakAfterEndItem(): ?bool
    {
        return $this->pageBreakAfterEndItem;
    }

    public function setPageBreakAfterEndItem(?bool $pageBreakAfterEndItem): static
    {
        $this->pageBreakAfterEndItem = $pageBreakAfterEndItem;

        return $this;
    }

    public function isHideDefaultOutlaysOnEmptyOutlays(): ?bool
    {
        return $this->hideDefaultOutlaysOnEmptyOutlays;
    }

    public function setHideDefaultOutlaysOnEmptyOutlays(?bool $hideDefaultOutlaysOnEmptyOutlays): static
    {
        $this->hideDefaultOutlaysOnEmptyOutlays = $hideDefaultOutlaysOnEmptyOutlays;

        return $this;
    }

    /**
     * @return Collection<int, Outlay>
     */
    public function getOutlays(): Collection
    {
        return $this->outlays;
    }

    public function addOutlay(Outlay $outlay): static
    {
        if (!$this->outlays->contains($outlay)) {
            $this->outlays->add($outlay);
        }

        return $this;
    }

    public function removeOutlay(Outlay $outlay): static
    {
        $this->outlays->removeElement($outlay);

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setBillingConfig($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getBillingConfig() === $this) {
                $transaction->setBillingConfig(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->addBilling($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            $product->removeBilling($this);
        }

        return $this;
    }

    public function getMarginBeforeTotal(): ?string
    {
        return $this->marginBeforeTotal;
    }

    public function setMarginBeforeTotal(?string $marginBeforeTotal): static
    {
        $this->marginBeforeTotal = $marginBeforeTotal;

        return $this;
    }

}

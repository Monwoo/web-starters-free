<?php

namespace MWS\MoonManagerBundle\Entity;

use Symfony\Component\Serializer;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use MWS\MoonManagerBundle\Repository\MwsMessageRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

// use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[ORM\Entity(repositoryClass: MwsMessageRepository::class)]
// #[Serializer\Annotation\Context(context: [
//     AbstractNormalizer::ATTRIBUTES =>
//     ['projectId', 'owner' => ['id']]
// ])] // Not for entity class....
#[UniqueEntity( // TODO : UNIQUE Entity FAIL, it still save duplicated slugs...
    // fields: ['templateNameSlug', 'templateCategorySlug'],
    fields: ['template_name_slug', 'template_category_slug'],
    // errorPath: 'templateNameSlug',
    errorPath: 'template_name_slug',
    message: MwsMessage::slugAndCatNotUniqueError,
)]
#[ORM\Index(columns: ['project_id'])]
#[ORM\Index(columns: ['dest_id'])]
#[ORM\Index(columns: ['source_id'])]
class MwsMessage
{
    // TODO : should do someting like :
    // TranslationExtractOnly->trans('....') for translation auto-extractor to works...
    // BUT SOUNDS like not possible to write 'function call' inside constants...
    // IDEA : DELEGATE ALL translations to SVELTE SIDE. Backend will only send key ?
    //       => same issue as previous : how to auto-generate/sync app translations strings ?
    const slugAndCatNotUniqueError = // trans(
        'mws.message.errors.template-tag-slug-and-category-not-unique'
    /*)*/;

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

    #[ORM\Column(nullable: true)]
    private ?bool $isDraft = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isTemplate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $templateNameSlug = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $templateCategorySlug = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sourceId = null;

    #[ORM\Column(nullable: true)]
    private ?array $crmLogs = null;

    #[ORM\Column(nullable: true)]
    private ?array $messages = null;

    #[ORM\ManyToOne(inversedBy: 'mwsMessages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MwsUser $owner = null;

    use TimestampableEntity;

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

    public function isIsDraft(): ?bool
    {
        return $this->isDraft;
    }

    public function setIsDraft(bool $isDraft): static
    {
        $this->isDraft = $isDraft;

        return $this;
    }
    public function isIsTemplate(): ?bool
    {
        return $this->isTemplate;
    }

    public function setIsTemplate(?bool $isTemplate): static
    {
        $this->isTemplate = $isTemplate;

        return $this;
    }

    public function getTemplateNameSlug(): ?string
    {
        return $this->templateNameSlug;
    }

    public function setTemplateNameSlug(?string $templateNameSlug): static
    {
        $this->templateNameSlug = $templateNameSlug;

        return $this;
    }

    public function getTemplateCategorySlug(): ?string
    {
        return $this->templateCategorySlug;
    }

    public function setTemplateCategorySlug(?string $templateCategorySlug): static
    {
        $this->templateCategorySlug = $templateCategorySlug;

        return $this;
    }
}

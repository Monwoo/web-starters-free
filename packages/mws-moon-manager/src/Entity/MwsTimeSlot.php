<?php

namespace MWS\MoonManagerBundle\Entity;

use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use MWS\MoonManagerBundle\Repository\MwsTimeSlotRepository;
use MWS\MoonManagerBundle\Services\MaxPriceTagManager;
use Symfony\Component\Serializer\Annotation as Serializer;

#[ORM\Entity(repositoryClass: MwsTimeSlotRepository::class)]
#[ORM\Index(columns: ['source_time_gmt'])]
#[ORM\Index(columns: ['range_day_idx_by10_min'])]
#[ORM\Index(columns: ['range_day_idx_by_custom_norm'])]
class MwsTimeSlot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Serializer\Groups(['withDeepIds'])]
    private ?int $id = null;

    // TIPS : will miss the timezone, for simplicity, will be GMT time...
    #[ORM\Column(type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $sourceTimeGMT = null;

    #[ORM\Column]
    private array $source = [];

    #[ORM\Column(nullable: true)] // TODO : computed field, but hard computed for opti OK ?
    private ?int $rangeDayIdxBy10Min = null;

    // #[ORM\Column(nullable: true)]
    // private ?float $maxPricePerHr = null;
    #[ORM\ManyToOne(inversedBy: 'mwsTimeSlotsForMax', cascade: ["persist"])]
    private ?MwsTimeTag $maxPriceTag = null;

    #[ORM\Column(nullable: true)]
    private ?array $maxPath = null;

    #[ORM\Column(nullable: true)]
    private ?int $rangeDayIdxByCustomNorm = null;

    #[ORM\ManyToMany(targetEntity: MwsTimeTag::class, inversedBy: 'mwsTimeSlots', cascade: ["persist"])]
    private Collection $tags;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $keywords = null;

    #[ORM\Column(length: 512)]
    private ?string $sourceStamp = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $thumbnailJpeg = null;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }
    // #[ORM\ManyToMany(targetEntity: MwsOfferStatus::class, inversedBy: 'mwsOffers', cascade: ['persist'])]
    // private Collection $tags;

    use TimestampableEntity;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getsourceTimeGMT(): ?\DateTimeInterface
    {
        return $this->sourceTimeGMT;
    }

    public function setsourceTimeGMT(?\DateTimeInterface $sourceTimeGMT): static
    {
        $this->sourceTimeGMT = $sourceTimeGMT;

        // TODO : ok here or better using event system ? (strong design will use other design patterns..)
        // TODO : rethink timzone stuff, is gmt date, need update on tz changes ?
        //    => get from CRM root configs... (param or env or db ?)
        // $dt = DateTime::createFromFormat("Y-m-d H:i:s", "2011-07-26 20:05:00");
        // $hours = $dt->format('H'); // '20'
        $localTz = new DateTimeZone('Europe/Paris');

        $localTime = new DateTime($sourceTimeGMT->format(DateTime::ATOM));
        $localTime->setTimezone($localTz);
        $minutes = intval($localTime->format('H')) * 60
            + intval($localTime->format('i'));
        $slotIdxBy10Min = intval($minutes / 10);
        $this->setRangeDayIdxBy10Min($slotIdxBy10Min);


        return $this;
    }

    public function getSource(): array
    {
        return $this->source;
    }

    public function setSource(array $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getRangeDayIdxBy10Min(): ?int
    {
        return $this->rangeDayIdxBy10Min;
    }

    public function setRangeDayIdxBy10Min(?int $rangeDayIdxBy10Min): static
    {
        $this->rangeDayIdxBy10Min = $rangeDayIdxBy10Min;

        return $this;
    }

    public function getRangeDayIdxByCustomNorm(): ?int
    {
        return $this->rangeDayIdxByCustomNorm;
    }

    public function setRangeDayIdxByCustomNorm(?int $rangeDayIdxByCustomNorm): static
    {
        $this->rangeDayIdxByCustomNorm = $rangeDayIdxByCustomNorm;

        return $this;
    }

    /**
     * @return Collection<int, MwsTimeTag>
     */
    public function getTags(): Collection
    {
        // TODO : must use doctrine event for collection clear notification ?
        return $this->tags;
    }

    public function addTag(MwsTimeTag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        // TODO : really NEED event system, need to recompute
        // on tags prices rules updates too...
        // TODO : ok here or better using event system ? (strong design will use other design patterns..)
        [$maxTag, $maxPath] = MaxPriceTagManager::pickMaxOf(
            $this->tags->toArray()
        );
        $this->setMaxPriceTag($maxTag);
        $this->setMaxPath($maxPath);

        return $this;
    }

    public function removeTag(MwsTimeTag $tag): static
    {
        $this->tags->removeElement($tag);

        // TODO : ok here or better using event system ? (strong design will use other design patterns..)
        [$maxTag, $maxPath] = MaxPriceTagManager::pickMaxOf(
            $this->tags->toArray()
        );
        $this->setMaxPriceTag($maxTag);
        $this->setMaxPath($maxPath);

        return $this;
    }

    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    public function setKeywords(?string $keywords): static
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function getSourceStamp(): ?string
    {
        return $this->sourceStamp;
    }

    public function setSourceStamp(string $sourceStamp): static
    {
        $this->sourceStamp = $sourceStamp;

        return $this;
    }

    public function getMaxPriceTag(): ?MwsTimeTag
    {
        return $this->maxPriceTag;
    }

    public function setMaxPriceTag(?MwsTimeTag $maxPriceTag): static
    {
        $this->maxPriceTag = $maxPriceTag;

        return $this;
    }

    public function getMaxPath(): ?array
    {
        return $this->maxPath;
    }

    public function setMaxPath(?array $maxPath): static
    {
        $this->maxPath = $maxPath;

        return $this;
    }

    public function getThumbnailJpeg(): ?string
    {
        return $this->thumbnailJpeg;
    }

    public function setThumbnailJpeg(?string $thumbnailJpeg): static
    {
        $this->thumbnailJpeg = $thumbnailJpeg;

        return $this;
    }
}

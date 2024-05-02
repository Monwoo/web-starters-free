<?php

namespace MWS\MoonManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use MWS\MoonManagerBundle\Repository\MwsMessageTchatUploadRepository;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Entity\File as EmbeddedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: MwsMessageTchatUploadRepository::class)]
#[Vich\Uploadable]
class MwsTimeSlotUpload
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // TODO : for any file... upload from surveyJs data with ajax...
    // https://github.com/dustin10/VichUploaderBundle/blob/master/docs/usage.md
    // 

    // NOTE: This is not a mapped field of entity metadata, just a simple property.
    // #[Vich\UploadableField(mapping: 'timing_slot_thumbnail', fileNameProperty: 'media.name', size: 'media.size')]
    #[Vich\UploadableField(mapping: 'timing_slot_thumbnail', fileNameProperty: 'mediaName', size: 'mediaSize')]
    private ?File $mediaFile = null;
    
    // #[ORM\Embedded(class: 'Vich\UploaderBundle\Entity\File')]
    // #[ORM\Embedded(class: EmbeddedFile::class)]
    // private ?EmbeddedFile $media = null;

    // Duplicate definition error, since using 
    //    #[Vich\UploadableField(.... ?
    #[ORM\Column(nullable: true)]
    private ?string $mediaName = null;
    #[ORM\Column(nullable: true)]
    private ?int $mediaSize = null;
    #[ORM\Column(nullable: true)]
    private ?string $mediaOriginalName = null;

    use TimestampableEntity;


    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $mediaFile
     */
    public function setMediaFile(?File $mediaFile = null): void
    {
        $this->mediaFile = $mediaFile;

        if (null !== $mediaFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getMediaFile(): ?File
    {
        return $this->mediaFile;
    }

    // public function setMedia(EmbeddedFile $media): void
    // {
    //     $this->media = $media;
    // }

    // public function getMedia(): ?EmbeddedFile
    // {
    //     return $this->media;
    // }

    public function setMediaName(?string $mediaName): void
    {
        $this->mediaName = $mediaName;
    }

    public function getMediaName(): ?string
    {
        return $this->mediaName;
    }

    public function setMediaOriginalName(?string $mediaOriginalName): void
    {
        $this->mediaOriginalName = $mediaOriginalName;
    }

    public function getMediaOriginalName(): ?string
    {
        return $this->mediaOriginalName;
    }

    public function setMediaSize(?int $mediaSize): void
    {
        $this->mediaSize = $mediaSize;
    }

    public function getMediaSize(): ?int
    {
        return $this->mediaSize;
    }

    // public function setMedia(?int $media): void
    // {
    //     $this->media = $media;
    // }

    // public function getMedia(): ?int
    // {
    //     return $this->media;
    // }
}

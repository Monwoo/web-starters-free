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
class MwsMessageTchatUpload
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // TODO : for any file... upload from surveyJs data with ajax...
    // https://github.com/dustin10/VichUploaderBundle/blob/master/docs/usage.md
    // 

    // NOTE: This is not a mapped field of entity metadata, just a simple property.
    #[Vich\UploadableField(mapping: 'message_tchats_upload', fileNameProperty: 'imageName', size: 'imageSize')]
    private ?File $imageFile = null;
    
    // #[ORM\Embedded(class: 'Vich\UploaderBundle\Entity\File')]
    // #[ORM\Embedded(class: EmbeddedFile::class)]
    // private ?EmbeddedFile $image = null;

    // Duplicate definition error, since using 
    //    #[Vich\UploadableField(.... ?
    #[ORM\Column(nullable: true)]
    private ?string $imageName = null;
    #[ORM\Column(nullable: true)]
    private ?int $imageSize = null;

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
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $imageFile
     */
    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    // public function setImage(EmbeddedFile $image): void
    // {
    //     $this->image = $image;
    // }

    // public function getImage(): ?EmbeddedFile
    // {
    //     return $this->image;
    // }

    public function setImageName(?string $imageName): void
    {
        $this->imageName = $imageName;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageSize(?int $imageSize): void
    {
        $this->imageSize = $imageSize;
    }

    public function getImageSize(): ?int
    {
        return $this->imageSize;
    }
}

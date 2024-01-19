<?php

namespace MWS\MoonManagerBundle\Naming;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\FileAbstraction\ReplacingFile;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\ConfigurableInterface;
use Vich\UploaderBundle\Naming\NamerInterface;
use Vich\UploaderBundle\Util\Transliterator;

/**
 * OrignalNameNamer.
 *
 * @author Miguel Monwoo <service@monwoo.com>
 */
final class OrignalNameNamer implements NamerInterface, ConfigurableInterface
{
    private bool $transliterate = false;

    public function __construct(private readonly Transliterator $transliterator)
    {
    }

    /**
     * @param array $options Options for this namer. The following options are accepted:
     *                       - transliterate: whether the filename should be transliterated or not
     */
    public function configure(array $options): void
    {
        $this->transliterate = isset($options['transliterate']) ? (bool) $options['transliterate'] : $this->transliterate;
    }

    public function name(object $object, PropertyMapping $mapping): string
    {
        /* @var $file UploadedFile|ReplacingFile */
        $file = $mapping->getFile($object);
        $name = $file->getClientOriginalName();

        if ($this->transliterate) {
            $name = $this->transliterator->transliterate($name);
        }

        // return \uniqid().'/'.$name; // TODO : not enough to create unique directory structure...
        // https://github.com/dustin10/VichUploaderBundle/blob/master/docs/namers.md
        return $name; // So directly send orinial name...
    }
}

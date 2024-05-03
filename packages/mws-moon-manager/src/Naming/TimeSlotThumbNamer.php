<?php

namespace MWS\MoonManagerBundle\Naming;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\FileAbstraction\ReplacingFile;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\ConfigurableInterface;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;
use Vich\UploaderBundle\Naming\NamerInterface;
use Vich\UploaderBundle\Util\Transliterator;

/**
 * TimeSlotThumbNamer.
 *
 * @author Miguel Monwoo <service@monwoo.com>
 */
final class TimeSlotThumbNamer implements 
DirectoryNamerInterface,
NamerInterface,
ConfigurableInterface
{
    private bool $transliterate = false;

    public function __construct (
        private readonly Transliterator $transliterator,
        protected ParameterBagInterface $params,
        protected Filesystem $filesystem = new Filesystem(),
    ) {
    }

    /**
     * @param array $options Options for this namer. The following options are accepted:
     *                       - transliterate: whether the filename should be transliterated or not
     */
    public function configure(array $options): void
    {
        $this->transliterate = isset($options['transliterate']) ? (bool) $options['transliterate'] : $this->transliterate;
    }

    public function directoryName(object|array $object, PropertyMapping $mapping): string {
        return 'timings/thumbs';
    }

    public function name(object $object, PropertyMapping $mapping): string
    {
        /* @var $file UploadedFile|ReplacingFile */
        $file = $mapping->getFile($object);
        // $name = $file->getClientOriginalName();

        // $realPath = $object->getRealPath();
        $realPath = $file->getRealPath();
        $projectDir = $this->params->get('kernel.project_dir');
        $uploadSubFolder = $this->params->get('mws_moon_manager.uploadSubFolder') ?? '';
        $basePath = "$projectDir/$uploadSubFolder/timings/thumbs";
        if (false === strpos($realPath, $basePath)) {
            // dump($realPath);
            // dd($basePath);
            return 'wrong-path.xxx';
        }
        $name = str_replace("$basePath/", '', $realPath);
        $name = str_replace("/", '_', $name);
        // dump($name);
        // dump($basePath);
        // dd($object);
        if ($this->transliterate) {
            $name = $this->transliterator->transliterate($name);
        }
        // TODO : trying to build folder do not work, error :
        // Could not copy file
        // in vendor/vich/uploader-bundle/src/Storage/FileSystemStorage.php (line 34)
        // $realDir = dirname($realPath);
        // if (!file_exists($realDir)) {
        //     $this->filesystem->mkdir($realDir);
        // }

        // dump($name);
        // return \uniqid().'/'.$name; // TODO : not enough to create unique directory structure...
        // https://github.com/dustin10/VichUploaderBundle/blob/master/docs/namers.md
        return $name; // So directly send orinial name...
    }
}

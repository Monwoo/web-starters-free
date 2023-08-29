<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

/*
# Display info about twig :
php bin/console debug:twig
# Display info about specific filter :
php bin/console debug:twig --filter=price
*/

// Biblio :
// https://symfony.com/doc/5.3/templating/twig_extension.html
namespace AppBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    // public function getFilters()
    // {
    //     return [
    //         new TwigFilter('price', [$this, 'formatPrice']),
    //     ];
    // }

    // public function formatPrice($number, $decimals = 0, $decPoint = '.', $thousandsSep = ',')
    // {
    //     $price = number_format($number, $decimals, $decPoint, $thousandsSep);
    //     $price = '$'.$price;

    //     return $price;
    // }


    // public function getFunctions()
    // {
    //     return [
    //         new TwigFunction('area', [$this, 'calculateArea']),
    //     ];
    // }

    // public function calculateArea(int $width, int $length)
    // {
    //     return $width * $length;
    // }

    // Lazy load :
    // public function getFilters()
    // {
    //     return [
    //         // the logic of this filter is now implemented in a different class
    //         new TwigFilter('price', [AppRuntime::class, 'formatPrice']),
    //     ];
    // }

    public function getGlobals(): array
    {
      $rootPackage = \Composer\InstalledVersions::getRootPackage();

      $packageVersion = $rootPackage['pretty_version'] ?? $rootPackage['version'];
      $packageName = array_slice(explode("monwoo/", $rootPackage['name']), -1)[0];
      $isDev = $rootPackage['dev'] ?? false;
      return [
        'packageVersion' => $packageVersion,
        'packageName' => $packageName,
        'isDev' => $isDev,
      ];
    }
}
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
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;

class AppEnvExtension extends AbstractExtension implements GlobalsInterface
{
  public function getFilters()
  {
    return [
      // new TwigFilter('humanSize', [$this, 'humanSize']),
      // new TwigFilter('bytesSize', [$this, 'bytesSize']),
      // new TwigFilter('price', [$this, 'formatPrice']),
    ];
  }

  // public function humanSize($size)
  // {
  //   $size = intval($size);
  //   // Then, humanize :
  //   if ($size < 1024) {
  //     $size = $size . " Bytes";
  //   } elseif (($size < 1048576) && ($size > 1023)) {
  //     $size = round($size / 1024, 1) . " KB";
  //   } elseif (($size < 1073741824) && ($size > 1048575)) {
  //     $size = round($size / 1048576, 1) . " MB";
  //   } else {
  //     $size = round($size / 1073741824, 1) . " GB";
  //   }
  //   return $size;
  // }

  // public function bytesSize($size)
  // {
  //   // dump($size);
  //   // $sizeParts = explode(' ', $size);
  //   $m = [];
  //   $size = str_replace(' ', '', $size);
  //   preg_match('/([0-9]+)([^0-9]*)/', $size, $m);
  //   // $sizeInt = intval(substr($size, );
  //   $intPart = intval($m[1] ?? 0);
  //   $factor = [
  //     'bytes' => 1,
  //     'kb' => 1023,
  //     'mb' => 1048575,
  //     'gb' => 1073741824,
  //     // https://canada.lenovo.com/fr/ca/en/glossary/kilobyte/
  //     // Bien que les kilooctets (Ko) et les kilobits (Ko) soient 
  //     // des unités de l'information numérique, elles mesurent
  //     // différents aspects. Les kilooctets mesurent la
  //     // capacité de stockage ou la taille des données,
  //     // tandis que les kilobits mesurent le débit de 
  //     // transfert ou la vitesse de transmission des données.
  //     'ko' => 1023,
  //     'mo' => 1048575,
  //     'go' => 1073741824,
  //   ][strtolower(trim($m[2] ?? 'Bytes'))];
  //   // dump($m);
  //   // dump($intPart);
  //   // dd($factor);
  //   return $intPart * $factor;
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

<?php
// 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Services;

use MWS\MoonManagerBundle\Entity\MwsTimeTag;
use Twig\Node\Expression\Binary\FloorDivBinary;

class MaxPriceTagManager
{
  // TODO : static for quick code, bring back to normal
  // and use https://symfonycasts.com/screencast/api-platform2-extending/post-load-listener
  // for entity services injection

  /**
   * @ param MwsTimeTag[] $tags
   * https://phpstan.org/writing-php-code/phpdoc-types#array-shapes
   * @param array{0: MwsTimeTag, 1?: array}
   *      0: maxTag
   *      1: maxPath
   */
  static public function pickMaxOf(array $tags): ?array
  {
    $rootMaxPath = [];

    /**
     * @param MwsTimeTag $t
     */
    $maxTag = array_reduce($tags, function ($acc, $t)
    use (&$tags, &$rootMaxPath) {
      $tMaxValue = self::getMaxValueOf($t, $tags);
      if ( $tMaxValue
      > self::getMaxValueOf($acc, $tags)) {
        $acc = $t;
        $maxPath = [
          // 'ruleIndex' => 
          'tagSlug' => $t->getSlug(),
          'deepLvl' => 1,
        ];
        $rootMaxPath[$t->getSlug()] = $maxPath;
        $rootMaxPath['_:MaxValue:_'] = $tMaxValue;
      }
      return $acc;
    }, null);

    // dump($tags);
    dump($maxTag);
    dd($rootMaxPath);
    // TODO : save getMaxValueOf inside $maxTag for opti ? or php cache used/is enough ? 
    return [$maxTag, $rootMaxPath];
  }

  static public function getMaxValueOf(?MwsTimeTag $tag, array &$tags): float
  {
    if (!$tag) {
      return 0;
    }
    if ( $tag->getPricePerHrRules()) {
      foreach ( $tag->getPricePerHrRules() as $idx => $rule) {
        // TODO : rules system....
        // $rule['withTags']
        return $rule['price'];
      }
    }
    // TODO : check max limit rules
    return $tag->getPricePerHr() ?? 0;
  }
}

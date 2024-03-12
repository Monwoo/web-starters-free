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
      $accMaxValue = self::getMaxValueOf($acc, $tags);
      if (
        $tMaxValue[0] > $accMaxValue[0]
        || (
          $tMaxValue[0] === $accMaxValue[0]
          && $tMaxValue[1] > $accMaxValue[1]
        )
      ) {
        $acc = $t;
        // $rootMaxPath['_:MaxTagSlug:_'] = $t->getSlug();
        // $rootMaxPath['_:MaxRuleIndex:_'] = $tMaxValue[3] ?? -1;
        // $rootMaxPath['_:MaxSubTags:_'] = $tMaxValue[2] ?? null;
        // // $rootMaxPath[$t->getSlug()] = $maxPath;
        // $rootMaxPath['_:MaxLimitPriority:_'] = $tMaxValue[0];
        // $rootMaxPath['_:MaxValue:_'] = $tMaxValue[1];
        $rootMaxPath['maxTagSlug'] = $t->getSlug();
        $rootMaxPath['maxRuleIndex'] = $tMaxValue[3] ?? -1;
        $rootMaxPath['maxSubTags'] = $tMaxValue[2] ?? null;
        $rootMaxPath['maxLimitPriority'] = $tMaxValue[0];
        $rootMaxPath['maxValue'] = $tMaxValue[1];
      }
      return $acc;
    }, null);

    // dump($tags);
    // dump($maxTag);
    // dd($rootMaxPath);
    // TODO : save getMaxValueOf inside $maxTag for opti ? or php cache used/is enough ? 
    return [$maxTag, $rootMaxPath];
  }

  static public function isRuleValid($rule, &$tags, &$checkedTagSlugs): bool
  {
    $fit = false;
    if (!$rule) {
      $fit = true;
    }
    if ($rule['withTags'] ?? false) {
      foreach ($rule['withTags'] as $tagSlug) {
        // dd($rule);
        $fit = array_reduce($tags, function ($acc, $t)
        use ($tagSlug) {
          return $acc || ($tagSlug == $t->getSlug());
        }, false);
        $checkedTagSlugs[] = $tagSlug;
        if (!$fit) {
          break;
        }
        # code...
      }
    }

    return $fit;
  }

  /**
   * @param array{0: float, 1: array, 2?: array}
   *      0: priority
   *      1: max Value For priority level
   *      2: subTagSlugs checked ok for rules test
   *      3: rule index
   */
  static public function getMaxValueOf(?MwsTimeTag $tag, array &$tags): array
  {
    if (!$tag) {
      return [0, 0];
    }
    if ($tag->getPricePerHrRules()) {
      $lastMaxRule = [];
      $lastMaxIdx = -1;
      $lastCheckedTagSlugs = [];
      foreach ($tag->getPricePerHrRules() as $idx => $rule) {
        $checkedTagSlugs = [];
        if (self::isRuleValid($rule, $tags, $checkedTagSlugs)
        && floatval($rule['price']) > ($lastMaxRule['price'] ?? 0)) {
          $lastMaxRule = $rule;
          $lastCheckedTagSlugs = $checkedTagSlugs;
          $lastMaxIdx = $idx;
        }
      }
      return [
        $lastMaxRule['maxLimitPriority'] ?? 0,
        $lastMaxRule['price'] ?? 0,
        $lastCheckedTagSlugs, $lastMaxIdx
      ];
    }
    // TODO : check max limit rules
    if ($tag->getPricePerHr()) {
      return [0, $tag->getPricePerHr()];
    }
    return [0, 0];
  }
}

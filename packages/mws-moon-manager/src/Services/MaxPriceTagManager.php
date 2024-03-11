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
   * @param MwsTimeTag[] $tags
   */
  static public function pickMaxOf(array $tags): ?MwsTimeTag
  {

    /**
     * @param MwsTimeTag $t
     */
    $maxTag = array_reduce($tags, function ($acc, $t) use (&$tags) {
      if ($this->getMaxValueOf($t, $tags)
      > $this->getMaxValueOf($acc, $tags)) {
        $acc = $t;
      }
      return $acc;
    }, null);

    // TODO : save getMaxValueOf inside $maxTag for opti ? or php cache used/is enough ? 
    return $maxTag;
  }

  static public function getMaxValueOf(MwsTimeTag $tag, array &$tags): ?float
  {
    // TODO : check max limit rules
    return $tag->getPricePerHr();
  }
}

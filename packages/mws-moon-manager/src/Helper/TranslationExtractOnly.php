<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\Helper;

class TranslationExtractOnly
{
  // const trans = function() {}; // NOP, not valid in PHP, invalid expression...

  // https://symfony.com/doc/current/validation/translations.html
  public static function trans(...$args) {
      // TIPS : only for text string extractor to work,
      // dummy function for i18n simple extractions
      return $args[0] ?? null;
  }
}
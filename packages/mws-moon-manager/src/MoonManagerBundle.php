<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖,
// build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * MoonManagerBundle
 */
class MoonManagerBundle extends Bundle
{
  public function build(ContainerBuilder $container): void
  {
    // echo("BundleBuild");exit;
    // dd("bundle loaded");
  }
  // Needed for SF 6 upgrades (to load twig templates, etc...):
  public function getPath(): string
  {
      return \dirname(__DIR__);
  }
}
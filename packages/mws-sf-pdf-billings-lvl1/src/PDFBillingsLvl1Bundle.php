<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖,
// build by Miguel Monwoo, service@monwoo.com

namespace MWS\PDFBillingsLvl1Bundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * PDFBillingsLvl1Bundle
 */
class PDFBillingsLvl1Bundle extends Bundle
{
  public function build(ContainerBuilder $container): void
  {
    // echo("BundleBuild");exit;
  }
  // Needed for SF 6 upgrades (to load twig templates, etc...):
  public function getPath(): string
  {
      return \dirname(__DIR__);
  }
    
}
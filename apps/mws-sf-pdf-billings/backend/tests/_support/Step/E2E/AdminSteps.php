<?php

/**
 * 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo
 * service@monwoo.com
 */

declare(strict_types=1);

namespace App\Tests\Step\E2E;

class AdminSteps extends \App\Tests\AcceptanceTester
{
  public static $backupMenuSelector = '#dropdownNavbar a[href="/mws/fr/mws-config/backup"]';
  public static $backupNameFieldSelector = 'form[name="mainBackup"] input[name="backupRawName"]';
  public static $backupDownloadClickSelector = 'form[name="mainBackup"] button[type="submit"]';
  public static $downloadFolderPath = 'tests/_output/chrome-download';

  public static function initVars()
  {
  }

  public function doBackup()
  {
    $I = $this;
    $I->comment("🇫🇷🇫🇷 Faire un backup");
    $I->click(UserSteps::$userMenuSelector);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
    $I->click(AdminSteps::$backupMenuSelector);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
    
    $I->scrollToWithNav(AdminSteps::$backupNameFieldSelector);
    $I->fillField(AdminSteps::$backupNameFieldSelector, "e2e-test");
    $I->scrollToWithNav(AdminSteps::$backupDownloadClickSelector);
    $I->click(AdminSteps::$backupDownloadClickSelector);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
  }

  public function doGDPRReset()
  {
    $I = $this;
    $I->comment("🇫🇷🇫🇷 Forcer un reset GDPR");
    $urlGenerator = $I->grabService('router.default');
    $gdprResetUrl = $urlGenerator->generate('app_factory_reset', [
      'forceTimeout' => true
    ]);
    $I->amOnPage($gdprResetUrl);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
  }

  // mws_config_backup_internal_use_as_gdpr_reset

}

AdminSteps::initVars();

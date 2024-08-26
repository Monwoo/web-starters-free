<?php

/**
 * 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo.
 * service@monwoo.com
 * 
 * @since 2.0.11
 * @package
 * @filesource
 * @author service@monwoo.com
 **/

namespace App\Tests\e2e\t00_backups;

use App\Repository\UserRepository;
use App\Tests\AcceptanceTester;
use App\Tests\Step\E2E\AdminSteps;
use App\Tests\Step\E2E\DataSteps;
use App\Tests\Step\E2E\UserSteps;
use Codeception\Util\Locator;

use function Psy\debug;

// UserSteps::initVars();
class E2E_SaveReloadResetOkCest
{
  public function _before(AcceptanceTester $I, UserSteps $userSteps)
  {
    // TIPS : reduce bottom debug bar :
    $I->amOnPage("/");
    $I->waitHumanDelay();
    $hideDebugButton = '//button[contains(@id, "sfToolbarHideButton")]';
    // if (count($I->grabMultiple($hideDebugButton)) === 1) {
    if (count($I->matchVisible($hideDebugButton)) === 1) {
      // $I->scrollTo($hideDebugButton); // no need, fixed button always on top
      try {
        $I->click($hideDebugButton);
      } catch (\Exception $e) {
        $I->debug("Close debug fail : ", $e);
      }
    }

    $userSteps->ensureUser(UserSteps::$userAdminInit);
  }

  // public function _after(AcceptanceTester $I, HomePage $home)
  // {
  // }

  public function specification01Test(
    AcceptanceTester $I,
    AdminSteps $adminSteps,
    DataSteps $dataSteps,
  ): void {
    $I->comment("🇫🇷🇫🇷 Sauvegarder un backup");
    $dataSteps->addOffer01();
    $adminSteps->doBackup();
    // TODO : get download file name
    $I->makeScreenshot('01-01-backup-save');
  }

  public function specification02Test(AcceptanceTester $I, UserSteps $userSteps): void
  {
    $I->comment("🇫🇷🇫🇷 Test le reset GDPR");
    $I->amOnPage("/");
    $I->makeScreenshot('02-01-backup-reset');
  }

  public function specification03Test(AcceptanceTester $I, UserSteps $userSteps): void
  {
    $I->comment("🇫🇷🇫🇷 Recharger les modifications avant le reset GDPR");
    $I->amOnPage("/");
    $I->makeScreenshot('03-01-reload-before-reset-save');
  }

  public function specification04Test(AcceptanceTester $I, UserSteps $userSteps): void
  {
    $I->comment("🇫🇷🇫🇷 Télécharger un backup ZIP");
    $I->amOnPage("/");
    $I->makeScreenshot('04-01-backup-download');
  }

  public function specification05Test(AcceptanceTester $I, UserSteps $userSteps): void
  {
    $I->comment("🇫🇷🇫🇷 Remettre à jour avec un backup ZIP");
    $I->amOnPage("/");
    $I->makeScreenshot('05-01-backup-upload');
  }
}

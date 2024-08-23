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
use App\Tests\Step\E2E\UserSteps;
use Codeception\Util\Locator;

use function Psy\debug;

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

    // $userSteps->ensureUser(self::$userEmail, self::$userPass, self::$userInit);
  }

  // public function _after(AcceptanceTester $I, HomePage $home)
  // {
  // }

  public function specification01Test(AcceptanceTester $I, UserSteps $userSteps): void
  {
    $I->comment("🇫🇷🇫🇷 Se connecter / déconnecter");
    $I->amOnPage("/");
    $I->makeScreenshot('00-01-backup-save');
  }
}

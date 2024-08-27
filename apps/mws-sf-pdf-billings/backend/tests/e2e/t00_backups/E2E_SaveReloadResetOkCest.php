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
    // $I->amOnPage("/");
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
    $I->comment("🇫🇷🇫🇷 Sauvegarder une offre 01 dans un backup");
    if (!$dataSteps->haveOffer01()) {
      $dataSteps->addOffer01();
    }
    $I->assertTrue($dataSteps->haveOffer01(), "Missing Test offer 01 for backup");
    $I->scrollToWithNav($dataSteps->locatorListOffer01());
    $I->makeScreenshot('01-01-backup-add-test-offer');

    $downloadFiles = $I->grabFilenames(AdminSteps::$downloadFolderPath);

    $dataSteps->haveOffer01();
    $adminSteps->doBackup();

    // reload to see backup on screen :
    $I->click(UserSteps::$userMenuSelector);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
    $I->click(AdminSteps::$backupMenuSelector);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
    $I->scrollToWithNav(Locator::contains('h1', 'Liste des backups'));

    // Ensure zip is present :
    $lastDownloadFiles = $I->grabFilenames(AdminSteps::$downloadFolderPath);
    $lastDownloadFile = array_diff($lastDownloadFiles, $downloadFiles);
    $I->assertTrue(count($lastDownloadFile) === 1, 'Should have download last backup in download folder.');
    // dd($lastDownloadFile);
    [ $lastDownloadFile ] = $lastDownloadFile;
    $I->comment("🇫🇷🇫🇷 Backup OK at : $lastDownloadFile");

    $I->makeScreenshot('01-02-backup-save');
  }

  public function specification02Test(AcceptanceTester $I,
    AdminSteps $adminSteps,
    DataSteps $dataSteps,
  ): void {
    $I->comment("🇫🇷🇫🇷 Sauvegarder une offre 02 avant reset GDPR");
    if (!$dataSteps->haveOffer02()) {
      $dataSteps->addOffer02();
    }
    $I->assertTrue($dataSteps->haveOffer02(), "Missing Test offer 02 before GDPR reset");
    $I->scrollToWithNav($dataSteps->locatorListOffer02());
    $I->makeScreenshot('02-01-GDPR-add-before-reset');

    $I->comment("🇫🇷🇫🇷 GDPR Reset OK");

    $I->makeScreenshot('02-02-GDPR-reset-ok');
  }

  public function specification03Test(AcceptanceTester $I, UserSteps $userSteps): void
  {
    $I->comment("🇫🇷🇫🇷 Rechargement du backup automatique avant reset GDPR");

  }

  public function specification04Test(AcceptanceTester $I, UserSteps $userSteps): void
  {
    $lastDownloadFiles = $I->grabFilenames(AdminSteps::$downloadFolderPath);
    $I->assertTrue(count($lastDownloadFiles) > 0, 'Previous steps should have download some backups.');
    $lastDownloadFile = $lastDownloadFiles[0];

    $I->comment("🇫🇷🇫🇷 Recharger le premier backup initial via $lastDownloadFile");
    $I->makeScreenshot('03-04-reload-first-zip');
  }

  public function specification05Test(AcceptanceTester $I, UserSteps $userSteps): void
  {
    $I->comment("🇫🇷🇫🇷 Définir le reset GDPR sur un backup précédent");
  }

  public function specification06Test(AcceptanceTester $I, UserSteps $userSteps): void
  {
    $I->comment("🇫🇷🇫🇷 Importer un backup depuis l'historique des backups");
  }

  public function specification07Test(AcceptanceTester $I, UserSteps $userSteps): void
  {
    $I->comment("🇫🇷🇫🇷 Télécharger un backup zip depuis l'historique des backups");
    $I->comment("🇫🇷🇫🇷 Importer ce backup");
  }

}

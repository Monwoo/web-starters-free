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
use App\Tests\Steps\E2E\AdminSteps;
use App\Tests\Steps\E2E\DataSteps;
use App\Tests\Steps\E2E\UserSteps;
use Codeception\Util\Locator;

use function Psy\debug;

// UserSteps::initVars();
class E2E_SaveReloadResetOkCest
{
  public function _before(AcceptanceTester $I, UserSteps $userSteps)
  {
    // TIPS : reduce bottom debug bar :
    $I->waitHumanDelay(); // TODO : Wait for libs loads before injected JS
    $I->amOnPage("/");
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...

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
    $I->comment("🇫🇷🇫🇷 🎯🎯 01 - Sauvegarder un backup 🎯🎯");
    $I->comment("🇫🇷🇫🇷 Sauvegarde d'une offre 01 dans un backup");

    if ($dataSteps->haveOffer02()) {
      $dataSteps->removeOffer02();
    }
    $I->assertFalse($dataSteps->haveOffer02(), "Should not have test offer 02");

    if (!$dataSteps->haveOffer01()) {
      $dataSteps->addOffer01();
    }
    $I->assertTrue($dataSteps->haveOffer01(), "Test offer 01 should exist for backup");
    $I->scrollToWithNav($dataSteps->locatorListOffer01());
    $I->makeScreenshot('01-01-backup-add-test-offer');

    // TIPS : save download list BEFORE doing any downloads to get the downloaded filename...
    $downloadFiles = $I->grabFilenames(AdminSteps::$downloadFolderPath);

    $adminSteps->doBackup();

    $I->assertTrue($dataSteps->haveOffer01(), "Test offer 01 should exist after backup");
    $I->assertFalse($dataSteps->haveOffer02(), "Should not have test offer 02 after backup");

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
    $I->makeScreenshot('01-02-backup-save-ok');

    // dd($lastDownloadFile);
    [$lastDownloadFile] = $lastDownloadFile;

    $I->comment("🇫🇷🇫🇷 Backup OK at : $lastDownloadFile");
  }

  public function specification02Test(
    AcceptanceTester $I,
    AdminSteps $adminSteps,
    DataSteps $dataSteps,
    UserSteps $userSteps,
  ): void {
    $I->comment("🇫🇷🇫🇷 🎯🎯 02 - Faire un reset GDPR 🎯🎯");
    $I->comment("🇫🇷🇫🇷 Sauvegarde d'une offre 02 avant reset GDPR");
    if (!$dataSteps->haveOffer02()) {
      $dataSteps->addOffer02();
    }
    $I->assertTrue($dataSteps->haveOffer02(), "Test offer 02 should exist before GDPR reset");
    $I->scrollToWithNav($dataSteps->locatorListOffer02());
    $I->makeScreenshot('02-01-GDPR-add-before-reset');

    $adminSteps->doGDPRReset();
    $I->makeScreenshot('02-02-GDPR-reset-response');

    $userSteps->ensureUser(UserSteps::$userAdminInit);

    $adminSteps->amOnBackupPage();
    $I->scrollToWithNav(Locator::contains('h1', 'Liste des backups'));
    $I->makeScreenshot('02-03-GDPR-backup-list');

    $I->assertFalse($dataSteps->haveOffer01(), "Should not have test offer 01");
    $I->assertFalse($dataSteps->haveOffer02(), "Should not have test offer 02");

    $I->makeScreenshot('02-04-GDPR-offers-ok');
  }

  public function specification03Test(
    AcceptanceTester $I,
    AdminSteps $adminSteps,
    DataSteps $dataSteps,
    UserSteps $userSteps,
  ): void {
    $I->comment("🇫🇷🇫🇷 🎯🎯 03 - Recharger les données nettoyés par le reset GDPR 🎯🎯");
    $I->comment("🇫🇷🇫🇷 Rechargement du backup automatique avant reset GDPR");
    $backups = $adminSteps->grabInternalBackups();
    $I->assertNotEmpty($backups, 'Missing backup list.');
    $adminSteps->doImportInternalBackup($backups[0]);
    $userSteps->ensureUser(UserSteps::$userAdminInit);

    $adminSteps->amOnBackupPage();
    $I->scrollToWithNav(Locator::contains('h1', 'Liste des backups'));
    $I->makeScreenshot('03-01-reload-before-GDPR-backup-list');

    $I->assertTrue($dataSteps->haveOffer01(), "Missing expected test offer 01");
    $I->assertTrue($dataSteps->haveOffer02(), "Missing expected test offer 02");

    $I->makeScreenshot('03-02-reload-before-GDPR-reset-ok');
  }

  public function specification04Test(
    AcceptanceTester $I,
    AdminSteps $adminSteps,
    DataSteps $dataSteps,
    UserSteps $userSteps,
  ): void {
    $I->comment("🇫🇷🇫🇷 🎯🎯 04 - Recharger le premier backup initial 🎯🎯");
    $lastDownloadFiles = $I->grabFilenames(AdminSteps::$downloadFolderPath);
    $I->assertTrue(count($lastDownloadFiles) > 0, 'Previous steps should have download some backups.');
    $lastDownloadFile = $lastDownloadFiles[0];
    $I->comment("🇫🇷🇫🇷 Backup initial : $lastDownloadFile");

    $backups = $adminSteps->grabInternalBackups();
    $adminSteps->doUploadBackup($lastDownloadFile, '../_output/chrome-download/');
    $I->makeScreenshot('04-01-reload-first-zip-response');

    $userSteps->ensureUser(UserSteps::$userAdminInit);

    $adminSteps->amOnBackupPage();
    $I->scrollToWithNav(Locator::contains('h1', 'Liste des backups'));
    $I->makeScreenshot('04-02-reload-first-zip-backup-list');

    $backupsAfterZipImport = $adminSteps->grabInternalBackups();
    $newBackups = array_diff($backupsAfterZipImport, $backups);
    $I->debug("autosave one backup : ", $newBackups, $backups, $backupsAfterZipImport);
    $I->assertTrue(count($newBackups) === 1, 'Should have autosave one backup after zip import.');

    $I->assertFalse($dataSteps->haveOffer02(), "Should not have test offer 02");
    $I->assertTrue($dataSteps->haveOffer01(), "Missing expected test offer 01");

    $I->makeScreenshot('04-03-reload-first-zip-ok');
  }

  public function specification05Test(
    AcceptanceTester $I,
    AdminSteps $adminSteps,
    DataSteps $dataSteps,
    UserSteps $userSteps,
  ): void {
    $I->comment("🇫🇷🇫🇷 🎯🎯 05 - Définir le reset GDPR sur un backup précédent");

    $backups = $adminSteps->grabInternalBackups();
    $I->assertNotEmpty($backups, 'Missing backup list.');
    // TIPS : $backups[0] contains GDPR data
    //        (saved before last test zip upload)
    //        $backups[1] backup contains offer 01 (from zip)
    //        $backups[2] backup contains offer 01 and 02 (edits before zip import)
    //        same as $backups[count($backups) - 2] backup contains offer 01 and 02 (edits before zip import)
    // since auto-save from previous test uploading zip backup....
    // $adminSteps->doGdprSrcOnInternalBackup($backups[2]);
    $adminSteps->doGdprSrcOnInternalBackup($backups[count($backups) - 2]);
    $I->makeScreenshot('05-01-GDPR-on-internal-bckup-ok');

    $adminSteps->doGDPRReset();
    $I->makeScreenshot('05-02-GDPR-on-internal-bckup-reset-response');
    $userSteps->ensureUser(UserSteps::$userAdminInit);

    $adminSteps->amOnBackupPage();
    $I->scrollToWithNav(Locator::contains('h1', 'Liste des backups'));
    $I->makeScreenshot('05-03-GDPR-on-internal-bckup-backup-list');

    $I->assertTrue($dataSteps->haveOffer01(), "Missing expected test offer 01");
    $I->assertTrue($dataSteps->haveOffer02(), "Missing expected test offer 02");

    $I->makeScreenshot('05-04-GDPR-on-internal-bckup-ok');
  }

  public function specification06Test(
    AcceptanceTester $I,
    AdminSteps $adminSteps,
  ): void {
    $I->comment("🇫🇷🇫🇷 🎯🎯 06 - Supprimer un backup depuis l'historique des backups");
    $backups = $adminSteps->grabInternalBackups();
    $I->assertNotEmpty($backups, 'Missing backup list.');

    $adminSteps->doInternalBackupDelete($backups[count($backups) - 2]);
    $I->makeScreenshot('06-01-backup-delete-response');

    $adminSteps->amOnBackupPage();
    $I->scrollToWithNav(Locator::contains('h1', 'Liste des backups'));
    $I->makeScreenshot('06-02-backup-delete-response-backup-list');

    $backupsAfterZipImport = $adminSteps->grabInternalBackups();
    $missingBackups = array_diff($backups, $backupsAfterZipImport);
    $I->debug("delete one backup : ", $missingBackups, $backups, $backupsAfterZipImport);
    $I->assertTrue(count($missingBackups) === 1, 'Should have delete one backup after delete action.');
  }

  public function specification07Test(
    AcceptanceTester $I,
    AdminSteps $adminSteps,
    UserSteps $userSteps,
    DataSteps $dataSteps,
  ): void {
    $I->comment("🇫🇷🇫🇷 🎯🎯 07 - Télécharger un backup zip depuis l'historique des backups");

    $downloadFiles = $I->grabFilenames(AdminSteps::$downloadFolderPath);

    $backups = $adminSteps->grabInternalBackups();
    $I->assertNotEmpty($backups, 'Missing backup list.');
    // We did remove custom one, this one is backup of initial gdpr db data :
    $adminSteps->doInternalBackupDownload($backups[count($backups) - 2]);

    // Ensure zip is present :
    $lastDownloadFiles = $I->grabFilenames(AdminSteps::$downloadFolderPath);
    $newDownloadFiles = array_diff($lastDownloadFiles, $downloadFiles);
    $I->assertTrue(count($newDownloadFiles) === 1, 'Should have download internal backup in download folder.');
    $I->makeScreenshot('07-01-internal-backup-download-ok');

    $I->comment("🇫🇷🇫🇷 Teste le backup téléchargé");

    $lastDownloadFile = $newDownloadFiles[0];
    $I->comment("🇫🇷🇫🇷 Backup à tester : $lastDownloadFile");
    $adminSteps->doUploadBackup($lastDownloadFile, '../_output/chrome-download/');
    $I->makeScreenshot('07-02-internal-backup-download-verif-response');
    $userSteps->ensureUser(UserSteps::$userAdminInit);

    $backupsAfterZipImport = $adminSteps->grabInternalBackups();
    $newBackups = array_diff($backupsAfterZipImport, $backups);
    $I->debug("autosave one backup : ", $newBackups, $backups, $backupsAfterZipImport);
    $I->assertTrue(count($newBackups) === 1, 'Should have autosave one backup before zip import.');

    $I->assertFalse($dataSteps->haveOffer01(), "Should not have test offer 01");
    $I->assertFalse($dataSteps->haveOffer02(), "Should not have test offer 02");

    $I->makeScreenshot('07-03-internal-backup-download-verif-ok');
  }
}

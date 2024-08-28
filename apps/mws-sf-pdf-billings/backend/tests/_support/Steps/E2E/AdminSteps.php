<?php

/**
 * 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo
 * service@monwoo.com
 */

declare(strict_types=1);

namespace App\Tests\Steps\E2E;

class AdminSteps extends \App\Tests\AcceptanceTester
{
  public static $backupMenuSelector = '#dropdownNavbar a[href="/mws/fr/mws-config/backup"]';
  public static $backupNameFieldSelector = 'form[name="mainBackup"] input[name="backupRawName"]';
  public static $backupDownloadClickSelector = 'form[name="mainBackup"] button[type="submit"]';
  public static $backupUploadFieldSelector = '#config-backup-form input[type="file"]';
  public static $backupUploadSubmitSelector = '#config-backup-form input[value="Importer le backup"]';
  public static $downloadFolderPath = 'tests/_output/chrome-download';
  public static $attachFileFolderPath = '';

  public static function initVars() {}

  public function amOnBackupPage() {
    $I = $this;
    $I->click(UserSteps::$userMenuSelector);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
    $I->click(AdminSteps::$backupMenuSelector);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
  }

  public function doBackup()
  {
    $I = $this;
    $I->comment("🇫🇷🇫🇷 Fait un backup");
    $I->amOnBackupPage();

    $I->scrollToWithNav(AdminSteps::$backupNameFieldSelector);
    $I->fillField(AdminSteps::$backupNameFieldSelector, "e2e-test");
    $I->scrollToWithNav(AdminSteps::$backupDownloadClickSelector);
    $I->click(AdminSteps::$backupDownloadClickSelector);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
  }

  public function doGDPRReset()
  {
    $I = $this;
    $I->comment("🇫🇷🇫🇷 Force un reset GDPR");
    $urlGenerator = $I->grabService('router.default');
    $gdprResetUrl = $urlGenerator->generate('app_factory_reset', [
      'forceTimeout' => true
    ]);
    $I->amOnPage($gdprResetUrl);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
  }

  public function grabInternalBackups() {
    $I = $this;
    $I->click(UserSteps::$userMenuSelector);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
    $I->click(AdminSteps::$backupMenuSelector);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...

    return $I->grabMultiple(".mws-local-backup-dir", "data-name");
  }

  public function doImportInternalBackup($backupName) {
    $I = $this;
    $I->comment("🇫🇷🇫🇷 Importe le backup $backupName");
    $I->click(UserSteps::$userMenuSelector);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
    $I->click(AdminSteps::$backupMenuSelector);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...

    // https://tobiasahlin.com/blog/previous-sibling-css-has/
    // .box:has(+ .circle) {

    $bkupImportSelector = "form:nth-child(2) input[value=\"$backupName\"] + button";
    $I->scrollToWithNav($bkupImportSelector);
    // $I->scrollTo($bkupImportSelector);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
    $I->clickAndAcceptPopup($bkupImportSelector);
    // $I->acceptPopup();
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
  }
  // mws_config_backup_internal_use_as_gdpr_reset

  public function doUploadBackup($backupFilename, $baseFolder = null) {
    $I = $this;
    $baseFolder = $baseFolder ?? AdminSteps::$attachFileFolderPath;
    $filename = "$baseFolder$backupFilename";
    $I->comment("🇫🇷🇫🇷 Importe le zip $filename");

    $I->amOnBackupPage();

    $I->attachFile(AdminSteps::$backupUploadFieldSelector, $filename);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
    $I->click(AdminSteps::$backupUploadSubmitSelector);
    // $I->clickAndAcceptPopup(AdminSteps::$backupUploadSubmitSelector);
    // $I->acceptPopup();
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
  }

  public function doGdprSrcOnInternalBackup($backupName) {
    $I = $this;
    $I->comment("🇫🇷🇫🇷 Source GRPR via : $backupName");
    $I->click(UserSteps::$userMenuSelector);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
    $I->click(AdminSteps::$backupMenuSelector);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...

    // https://tobiasahlin.com/blog/previous-sibling-css-has/
    // .box:has(+ .circle) {

    $bkupImportSelector = "form:nth-child(4) input[value=\"$backupName\"] + button";
    $I->scrollToWithNav($bkupImportSelector);
    // $I->scrollTo($bkupImportSelector);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
    $I->clickAndAcceptPopup($bkupImportSelector);
    // $I->acceptPopup();
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
  }

}

AdminSteps::initVars();

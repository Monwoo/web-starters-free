<?php

/**
 * 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo
 * service@monwoo.com
 */

declare(strict_types=1);

namespace App\Tests\Steps\E2E;

use App\Tests\AcceptanceTester;
use Codeception\Util\Locator;

class DataSteps extends AcceptanceTester
{
  // public $addOfferMenuSelector = '.mws-nav-bar .mws-add-offer';
  // public $addOfferModalIdFieldSelector = null;
  // public $addOfferModalSubmitBtnSelector = '.mws-add-modal input.sd-navigation__complete-btn';

  // TIPS : DESIGN issue without static : 
  // other actors will overwrite initVars inside I 
  // if using same pattern...
  // protected function initVars()
  // {
  //   $I = $this;
  //   // $registry = $I->grabService('doctrine'); // Get EntityManager
  //   // dd($registry);
  //   $I->addOfferModalIdFieldSelector =
  //   Locator::elementAt('.mws-add-modal input', 2);  
  // }
  // public function __construct()
  // {
  //   // $this->initVars(); // TIPS : will error : 
  //   // Codeception\Actor::$scenario must not be accessed before initialization
  // }

  // public function wantTo(string $text): void {
  //   parent::wantTo($text);
  //   $this->initVars(); // Not called before test if not called manually...
  // }
  // public function wantToTest(string $text): void {
  //   parent::wantTo($text);
  //   $this->initVars(); // Not called before test if not called manually...
  // }

  public static $addOfferMenuSelector = '.mws-nav-bar .mws-add-offer';
  public static $addOfferModalIdFieldSelector = null;
  public static $addOfferModalClientNameFieldSelector = null;
  public static $addOfferModalSubmitBtnSelector = '.mws-add-modal input.sd-navigation__complete-btn';
  public static $listOffersMenuSelector = '.mws-nav-bar .mws-list-offers';
  public static function initVars()
  {
    // $registry = $I->grabService('doctrine'); // Get EntityManager
    // dd($registry);
    DataSteps::$addOfferModalIdFieldSelector =
      Locator::elementAt('.mws-add-modal input', 2);
    DataSteps::$addOfferModalClientNameFieldSelector =
      Locator::elementAt('.mws-add-modal input', 4);
  }

  // TODO : use DataProfider design pattern ?
  public function haveOffer01()
  {
    $I = $this;
    $I->click(DataSteps::$listOffersMenuSelector);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
    return count($I->grabMultiple($this->locatorListOffer01())) > 0;
  }

  public function locatorListOffer01() {
    $offer01ListTestSelector = 'a[href="/mws/fr/mws-offer/view/e2e-test"]';
    return $offer01ListTestSelector;
  }

  public function addOffer01()
  {
    // $this->initVars(); ok but too heavy to repeat... + DESIGN issue for I
    $I = $this;
    $I->comment("🇫🇷🇫🇷 Ajoute l'offre 01");
    $I->click(DataSteps::$addOfferMenuSelector);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...

    $I->scrollToWithNav(DataSteps::$addOfferModalIdFieldSelector);
    $I->fillField(DataSteps::$addOfferModalIdFieldSelector, "e2e-test");
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
    $I->scrollToWithNav(DataSteps::$addOfferModalClientNameFieldSelector);
    $I->fillField(DataSteps::$addOfferModalClientNameFieldSelector, "T E2e");
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...

    $I->scrollToWithNav(DataSteps::$addOfferModalSubmitBtnSelector);
    $uri = $I->grabFromCurrentUrl();
    $isOffersListPage = false !== preg_match(',mws-offer/lookup,', $uri);
    $I->debug('grabFromCurrentUrl : ', $uri, '$isOffersListPage', $isOffersListPage);

    // TODO : bugfix popup should not show if is offerListPage AND O offer
    // quick hack fix for test pass for now :
    $count = $I->grabTextFrom('.summary .count');
    $I->debug('hack on count : ', $count);
    if ($count === '[ 0 éléments]') {
      $I->comment('Hack on count actif pour : ' . $count);
      $isOffersListPage = false; // Will alert when 0 offers for now... known bug
    }

    // TODO : sf router routes match ?
    if (!$isOffersListPage) {
      $I->debug('will clickAndCancelPopup');
      // $I->clickAndAcceptPopup(DataSteps::$addOfferModalSubmitBtnSelector);
      // $I->clickAndCancelPopup(DataSteps::$addOfferModalSubmitBtnSelector);
      $I->click(DataSteps::$addOfferModalSubmitBtnSelector, false, false);
      // $I->waitHumanDelay(0.00); // TODO : listen to load event to know when popup will comme (server + network latency...)

      // * $I->performOn('.model', ActionSequence::build()
      // *     ->see('Warning')
      // *     ->see('Are you sure you want to delete this?')
      // *     ->click('Yes')
      // * );

      // $I->waitForJS("
      //   let confirm = window._e2e_offer_add_popup_confirm_open;
      //   if (confirm) {
      //     window._e2e_offer_add_popup_confirm_open = false;
      //   }
      //   return confirm;
      // ");

      // $I->cancelPopup(); // sometime slow and need double accept ?

      $I->wait(0.25); // TODO : wait for event... (without JS Injection since popup block js run until accept...)
      $I->cancelPopup(); // sometime slow and need double accept ?
      // $I->acceptPopup(); // sometime slow and need double accept ?
      // $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
      $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
    } else {
      $I->click(DataSteps::$addOfferModalSubmitBtnSelector);
    }
    // $I->acceptPopup(); // TODO : popup removed or saved config removing it ?...
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...

    // TODO : ensure offer did write ? 
  }

  public function haveOffer02()
  {
    $I = $this;
    $I->click(DataSteps::$listOffersMenuSelector);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
    return count($I->grabMultiple($this->locatorListOffer02())) > 0;
  }

  public function locatorListOffer02() {
    $offer01ListTestSelector = 'a[href="/mws/fr/mws-offer/view/e2e-test-02"]';
    return $offer01ListTestSelector;
  }

  public function addOffer02()
  {
    // $this->initVars(); ok but too heavy to repeat... + DESIGN issue for I
    $I = $this;
    $I->comment("🇫🇷🇫🇷 Ajoute l'offre 02");
    $I->click(DataSteps::$addOfferMenuSelector);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...

    $I->scrollToWithNav(DataSteps::$addOfferModalIdFieldSelector);
    $I->fillField(DataSteps::$addOfferModalIdFieldSelector, "e2e-test-02");
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
    $I->scrollToWithNav(DataSteps::$addOfferModalClientNameFieldSelector);
    $I->fillField(DataSteps::$addOfferModalClientNameFieldSelector, "T E2e 02");
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...

    $I->scrollToWithNav(DataSteps::$addOfferModalSubmitBtnSelector);
    $uri = $I->grabFromCurrentUrl();
    $isOffersListPage = false !== preg_match(',mws-offer/lookup,', $uri);
    $I->debug('grabFromCurrentUrl : ', $uri, '$isOffersListPage', $isOffersListPage);

    // TODO : bugfix popup should not show if is offerListPage AND O offer
    // quick hack fix for test pass for now :
    $count = $I->grabTextFrom('.summary .count');
    $I->debug('hack on count : ', $count);
    if ($count === '[ 0 éléments]') {
      $isOffersListPage = false; // Will alert when 0 offers for now... known bug
    }

    // TODO : sf router routes match ?
    if (!$isOffersListPage) {
      $I->debug('will clickAndCancelPopup');
      // $I->clickAndCancelPopup(DataSteps::$addOfferModalSubmitBtnSelector);
      $I->click(DataSteps::$addOfferModalSubmitBtnSelector);
      $I->acceptPopup();
    } else {
      $I->click(DataSteps::$addOfferModalSubmitBtnSelector);
    }
    // $I->acceptPopup(); // TODO : no popup on 02 ? refactor to remove popup seen on 01 ?
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...

    // TODO : ensure offer did write ? 
  }

  public function removeOffer02()
  {
    // $this->initVars(); ok but too heavy to repeat... + DESIGN issue for I
    $I = $this;
    $I->comment("🇫🇷🇫🇷 Nettoie l'offre 02");
    $I->click(DataSteps::$listOffersMenuSelector);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...

    // $editLocator = 'tr:has(td:contains("T E2e 02")) td a[href*="/mws-offer/view/"]'; // TODO : update css parser ? :has not handled by current codeception...
    // TIPS : XPath parser is more advanced, use it :
    $editLocator = '//tr/' . Locator::contains('td', "T E2e 02")
    // . '/parent::tr/' . $I->convertToXPath('a[href*="/mws-offer/view/"]') . '[1]'; // Err : not interactive + to view offer, not direct delete...
    . '/parent::tr/' . $I->convertToXPath('button.mws-edit-offer');
    $I->scrollToWithNav($editLocator);
    $I->click($editLocator);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...

    // $deleteLocator = 'label:has(input[name="_shouldDelete"]) div:contains("Oui")'; // TODO : update css parser ? :has not handled by current codeception...
    // TIPS : XPath parser is more advanced, use it :
    $deleteLocator = '//label/' . $I->convertToXPath('input[name="_shouldDelete"]')
    . '/parent::label/' . Locator::contains('div', "Oui");
    $I->scrollTo($deleteLocator);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
    $I->click($deleteLocator);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...

    $submitLocator = 'input[value="Enregistrer l\'offre"]';
    $I->scrollTo($submitLocator);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
    $I->click($submitLocator);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
  }
}

DataSteps::initVars();

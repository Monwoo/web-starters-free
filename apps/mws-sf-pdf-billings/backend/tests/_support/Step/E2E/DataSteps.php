<?php

/**
 * 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo
 * service@monwoo.com
 */

declare(strict_types=1);

namespace App\Tests\Step\E2E;

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
    $I->click(DataSteps::$addOfferModalSubmitBtnSelector);
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
    $I->click(DataSteps::$addOfferModalSubmitBtnSelector);
    // $I->acceptPopup(); // TODO : no popup on 02 ? refactor to remove popup seen on 01 ?
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...

    // TODO : ensure offer did write ? 
  }
}

DataSteps::initVars();

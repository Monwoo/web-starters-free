<?php

/**
 * 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo
 * service@monwoo.com
 */

declare(strict_types=1);

namespace App\Tests\Step\E2E;

use App\Tests\AcceptanceTester;
use Codeception\Util\HttpCode;
use Codeception\Util\Locator;
use MWS\MoonManagerBundle\Entity\MwsUser;

class DataSteps extends AcceptanceTester
{
  public $addOfferMenuSelector = '#dropdownNavbar a[href="/mws/fr/mws-config/backup"]';
  public $addOfferModalIdFieldSelector = null;
  public $addOfferModalSubmitBtnSelector = '.mws-add-modal input.sd-navigation__complete-btn';

  public function initVars()
  {
    $I = $this;
    // $registry = $I->grabService('doctrine'); // Get EntityManager
    // dd($registry);
    $I->addOfferModalIdFieldSelector =
    Locator::elementAt('.mws-add-modal input', 2);  
  }

  // public function __construct()
  // {
  //   // $this->initVars(); //will error : 
  //   // Codeception\Actor::$scenario must not be accessed before initialization
  // }
  
  // public function wantTo(string $text): void {
  //   parent::wantTo($text);
  //   $this->initVars(); //will error : 
  //   // Codeception\Actor::$scenario must not be accessed before initialization
  // }

  public function addOffer01()
  {
    $I = $this;
    $I->comment("🇫🇷🇫🇷 Ajoute une offre (01)");
    $I->click($I->addOfferMenuSelector);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...

    $I->scrollToWithNav($I->addOfferModalIdFieldSelector);
    $I->fillField($I->addOfferModalIdFieldSelector, "e2e-test");
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...

    $I->click($I->addOfferModalSubmitBtnSelector);
    $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...

    // TODO : ensure offer did write ? 
  }
}

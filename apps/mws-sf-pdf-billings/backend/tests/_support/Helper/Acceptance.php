<?php

namespace App\Tests\Helper;

use Codeception\Module\WebDriver;
use ReflectionMethod;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends \Codeception\Module
{
  /**
   * @see \Codeception\Module\WebDriver::makeScreenshot()
   */
  public function makeScreenshot(?string $name = NULL): void
  {
    /** @var \Codeception\Module\WebDriver */
    $I = $this->getModule('WebDriver');
    $this->appendInfoJs($I);
    // $I->waitHumanDelay();
    $I->wait(0.1);
    $I->makeScreenshot($name);
    // $this->getScenario()->runStep(new \Codeception\Step\Action('makeScreenshot', func_get_args()));
  }

  // https://codeception.com/docs/ModulesAndHelpers
  // HOOK: used after configuration is loaded
  public function _initialize()
  {
    // $this->getModule('PhpBrowser')->_reconfigure(['url' => 'http://localhost/admin']);
    // $this->getModule('WebDriver')->_restart();
  }
  public function matchVisible($selector)
  {
    // TODO : should extend and use custom WebDriver instead ?
    // https://stackoverflow.com/questions/17174139/can-i-how-to-call-a-protected-function-outside-of-a-class-in-php
    $r = new ReflectionMethod(WebDriver::class, 'matchVisible');
    $r->setAccessible(true); // Was protected, force access
    $webDriver = $this->getModule('WebDriver');
    return $r->invoke($webDriver, $selector);
  }

  public function getConfigUrl()
  {
    return $this->getModule('WebDriver')->_getConfig('url');
  }

  // protected $didLoadEnvs = false;
  /**
   * Get current config parameter value
   */
  public function getConfigParam($paramKey)
  {
    // https://stackoverflow.com/questions/22259269/need-to-use-codeception-parameters-in-test-code
    // $config = \Codeception\Configuration::suiteSettings("acceptance", \Codeception\Configuration::config());
    // return $config['env'][$paramKey];

    // if (!$this->didLoadEnvs) {
    //     $config = \Codeception\Configuration::config();
    //     $dotenv = new Dotenv();
    //     // var_dump($config);exit;
    //     // var_dump(array_keys($config));exit;
    //     foreach ($config['params'] as $envFile) {
    //         if ('env' === $envFile) { continue; } // Ignore 'env' that load env to params
    //         $this->debug("Will load env : $envFile");
    //         $dotenv->load(dirname(__DIR__).'/../../'.$envFile);
    //     }
    //     $this->didLoadEnvs = true;
    // }

    // $paramVal = getEnv[$paramKey]; // only command line ENVs
    $paramVal = $_ENV[$paramKey] ?? null; // TODO : warn if param don't exist ?
    $this->debug("Did read param : $paramKey as $paramVal");
    return $paramVal;
  }

  public ?string $testInfo = '';
  public function setCustomTestsInformations($testInfo): void
  {
    if ($testInfo) {
      $this->testInfo = $testInfo;
    }
    $this->debug('addCustomTestsInformations with :');
    $this->debug($testInfo);
    $this->debug($this->testInfo);
    $I = $this->getModule('WebDriver');
    // $this->appendInfoJs($I); WRONG : too early, jquery js will not be loaded yet
  }

  public function appendInfoJs($I)
  {
    // $I->wait(2);
    $I->debug('appendInfoJs with :');
    $tInfo = $this->testInfo;
    $I->debug($tInfo);
    $js = "
    const initE2E = () => {
        $('.e2e-test-info').remove();
        $('body').append(window.$(`
            <div class='e2e-test-info' style='position:fixed; z-index:999999; bottom:21px; right:7px; pointer-events: none; background: white; font-size:14pt; opacity: 0.7; text-align: right; '>
            $tInfo<br/><strong>Copyright</strong> Monwoo<strong>. Confidentiel</strong>, merci de respecter le <strong>secret professionnel</srong>.
            </div>
        `));
    };
    if (window.$ ?? false) {
        initE2E();
    } else {
        // leave 1 minute load
        setTimeout(initE2E, 1000);
    }
    ";
    $I->debug($js);
    $I->executeJS($js);
    // $I->executeAsyncJS($js);
    $I->wait(0.1);
  }

  public function amOnPage($page): void
  {
    /** @var \Codeception\Module\WebDriver */
    $I = $this->getModule('WebDriver');
    $I->amOnPage($page);
    $this->appendInfoJs($I);
  }

  public function click($page, $waitForLoads = true): void
  {
    /** @var \Codeception\Module\WebDriver */
    $I = $this->getModule('WebDriver');
    // $this->appendInfoJs($I); 
    $this->appendInfoJs($I);
    $I->click($page);
    if ($waitForLoads) {
      $I->wait(0.1); // TODO : load event listener with timeout...
      $this->appendInfoJs($I);
    }
  }

  public function waitHumanDelay($minTime = 0.1)
  {
    /** @var \Codeception\Module\WebDriver */
    $I = $this->getModule('WebDriver');
    $this->appendInfoJs($I);
    $I->wait(
      $minTime // TODO : add random time for more stress tests ?
    );
  }
}

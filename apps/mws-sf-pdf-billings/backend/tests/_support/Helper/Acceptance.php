<?php

namespace App\Tests\Helper;

use App\Tests\AcceptanceTester;
use Codeception\Module\WebDriver;
use ReflectionMethod;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends \Codeception\Module
{
  protected $mwsCurrentTest = null;
  // public $mwsTestReport = []; Nop, need to be in actor class
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
    AcceptanceTester::mwsAddToReport([ // TODO : actor not accessible....
      'type' => 'screenshot',
      'data' => [
        'name' => $name,
      ],
    ]);
    // $this->getScenario()->runStep(new \Codeception\Step\Action('makeScreenshot', func_get_args()));
  }

  // public function comment(string $description): void {
  //   /** @var \Codeception\Module\WebDriver */
  //   $I = $this->getModule('WebDriver');
  //   $I->comment($description);
  //   // $this->getScenario()->comment($description);
  //   // parent::comment($description);

  //   $this->mwsTestReport[] = [
  //     'type' => 'comment',
  //     'data' => [
  //       'name' => $description,
  //     ],
  //   ];
  // }

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
    $this->debug('setCustomTestsInformations with :');
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
            <div class='e2e-test-info' style='position:fixed; z-index:999999; bottom:0px; right:0px; padding: 3px 7px; pointer-events: none; background: white; font-size:8pt; opacity: 0.5; text-align: right; '>
            $tInfo<br/><strong>Copyright</strong> Monwoo<strong>. Confidentiel</strong>, merci de respecter le <strong>secret privé et professionnel</srong>.
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

  public function clickAndAcceptPopup($page, $waitForLoads = true): void
  {
    /** @var \Codeception\Module\WebDriver */
    $I = $this->getModule('WebDriver');
    
    // $this->appendInfoJs($I); 
    $this->appendInfoJs($I);
    $I->click($page);
    $I->acceptPopup();
    if ($waitForLoads) {
      $I->wait(0.1); // TODO : load event listener with timeout...
      $this->appendInfoJs($I);
    }
  }

  public function grabFilenames($path)
  {
    $filesystem = new Filesystem();

    if (!$filesystem->exists($path)) {
      return [];
    }

    $finder = new Finder();
    $finder->files()->in($path)
        ->ignoreDotFiles(true)
        ->ignoreUnreadableDirs()
        ->sortByModifiedTime()
        ->reverseSorting()
        ->depth(0);
    return array_map(function(SplFileInfo $f) {
      return $f->getFilename();
    }, iterator_to_array($finder, false));
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

  // HOOK: before test
  public function _before(\Codeception\TestInterface $test)
  {
    $meta = $test->getMetadata();
    $name = $meta->getCurrent('name') ?? $meta->getName();
    $filename = $meta->getCurrent('filename') ?? $meta->getFilename();
    $feature = $meta->getCurrent('feature') ?? $meta->getFeature();
    $index = $meta->getCurrent('index') ?? $meta->getIndex();
    // $this->mwsCurrentTest = $test;
    $this->mwsCurrentTest = $test;
    // $this->env = $test->getScenario()->current('env');
    /** @var AcceptanceTester */

    $I = $this;
    // var_dump(json_encode($this->mwsCurrentTest));
    // var_dump(json_encode($meta));
    // exit;

    // $I->setCustomTestsInformations(json_encode($this->mwsCurrentTest));
    // $info = "[$index] $filename $name $feature";
    // $testFile = dirname($filename)."/".basename($filename);
    // $testFile = implode("/", array_slice(explode("/", $filename), -2));
    // $testFile = implode("/", array_slice(explode("/tests/", $filename), -1));
    $testFile = implode("/", array_slice(explode("/e2e/", $filename), -1));
    $info = "$testFile - $feature";
    $I->setCustomTestsInformations("$info");
    $this->debug('setCustomTestsInformations with : ' . $info);
    $this->debug($this->testInfo);
  }

  // HOOK: after test
  public function _after(\Codeception\TestInterface $test) {
    // dd('ok after tests');
  }

  public function _afterSuite() {
    // TODO : save to json
    // dd(AcceptanceTester::getMwsReport());
  }


  // HOOK: on fail
  public function _failed(\Codeception\TestInterface $test, $fail)
  {
    // https://stackoverflow.com/questions/64006235/is-it-possible-to-get-the-filename-of-the-failed-test-in-the-failed-hook
    // https://codeception.com/extensions#RunFailed
    // $fullName = Descriptor::getTestFullName($test);
  }
}

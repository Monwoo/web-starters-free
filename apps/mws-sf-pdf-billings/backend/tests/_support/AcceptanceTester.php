<?php

namespace App\Tests;

use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\CssSelector\Exception\ParseException;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    protected static $mwsTestReport = [];
    public static function mwsAddToReport($reportItem) {
        self::$mwsTestReport[] = $reportItem;
    }

    public static function getMwsReport() {
        return self::$mwsTestReport;
    }

    public function comment(string $description): \Codeception\Actor
    {
        // dd(  $this->getScenario()->current('modules'));
        /** @var \Codeception\Module\WebDriver */
        // $I = $this->getModule('WebDriver');
        // $I->comment($description);
        // $this->getScenario()->comment($description);
        parent::comment($description);

        self::mwsAddToReport([
            'type' => 'comment',
            'data' => [
                'name' => $description,
            ],
        ]);

        return $this;
    }

    // public function makeScreenshot(?string $name = NULL): void
    // {
    // //   dd(  $this->getScenario()->current('modules'));
    // //   dd(  $this->getScenario()->makeScreenshot('WebDriver'));

    //   /** @var \Codeception\Module\WebDriver */
    //   $I = $this->getModule('WebDriver'); // TODO : missing getModule...
    //   $this->appendInfoJs($I);
    //   // $I->waitHumanDelay();
    //   $I->wait(0.1);
    //   $I->makeScreenshot($name);
    // //   $this->getScenario()->makeScreenshot($name);
    //   $this->mwsAddToReport([
    //     'type' => 'screenshot',
    //     'data' => [
    //       'name' => $name,
    //     ],
    //   ]);
    //   // $this->getScenario()->runStep(new \Codeception\Step\Action('makeScreenshot', func_get_args()));
    // }

    /**
     * Define custom actions here
     */

    /**
     * Debug from tests :
     */
    public function debug(...$datas)
    {
        if (!\Codeception\Util\Debug::isEnabled()) {
            return; // Keep spped, nothing to do if not in debug state
        }

        // https://stackoverflow.com/questions/2110732/how-to-get-name-of-calling-function-method-in-php
        // $caller = debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1];
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $position = $backtrace[0];
        $caller = $backtrace[1];
        $source = "[{$caller['function']}] at {$position['file']}:{$position['line']}";
        codecept_debug($source);
        // codecept_debug(array_keys(get_object_vars($caller)));
        // codecept_debug(array_keys($caller));

        $len = count($datas);
        $strBulk = "";
        $idx = 0;
        while ($idx < $len && is_string($datas[0])) {
            $strBulk .= array_shift($datas) . " ";
            $idx++;
        }
        // $prettyPrint = "[$source] $strBulk" . print_r($datas, true);
        // codecept_debug([$source => $datas]);
        // codecept_debug($prettyPrint);
        // \Codeception\Util\Debug::debug($datas);
        codecept_debug($strBulk);
        $len = count($datas);
        $idx = 0;
        while ($idx < $len) {
            codecept_debug($datas[$idx]);
            $idx++;
        }
    }

    /**
     * If element is found return true if not return false
     * @param $element
     * @return bool
     */
    public function testIfPresent($element)
    {
        $I = $this;
        try {
            $I->scrollToWithNav($element);
            $I->seeElement($element);
            $isFound = true;
            // } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
        } catch (\Exception $e) {
            $I->comment(
                'testIfPresent Fail for : ' .
                    json_encode($element) .
                    '. Not found. detail : ' .
                    $e->getMessage()
            );
            $isFound = false;
        }
        return $isFound;
    }

    /**
     * If locator is found return true if not return false
     * @param $locator Can be XPath or CSS or text or Locator class...
     * @return bool
     */
    public function testIfISee($locator)
    {
        $I = $this;
        $isFound = true;
        try {
            $navHeight = $I->executeJS("return parseInt($('.mws-nav-bar').outerHeight()) || 0;");
            $I->scrollToWithNav($locator);
            $I->seeElement($locator); // with WebDriver, MUST be VISIBLE at screen to work...
        } catch (\Exception $e) {
            $I->debug($e);
            $I->dump("[AcceptanceTester] Fail to test if I See : ", $locator, $e);
            $isFound = false;
        }
        return $isFound;
    }

    // /**
    //  * Wait a "human delay" time (to let human have a visual feedback of tested actions)
    //  * @param float $minTime minimum time in seconds
    //  * @return void
    //  */
    // public function waitHumanDelay($minTime = 0.1){
    //     $this->wait(
    //         $minTime +
    //         $this->getConfigParam('E2E_HUMAN_DELAY') // TODO : add random time for more stress tests ?
    //     );
    // }

    public function convertToXPath(string $selector): ?string
    {
        try {
            return (new CssSelectorConverter())->toXPath($selector);
        } catch (ParseException $parseException) {
            // if (self::isXPath($selector)) {
            //     return $selector;
            // }
        }
        // return null;
        return $selector;
    }

    public function scrollToWithNav($selector, ?int $offsetX = NULL, ?int $offsetY = NULL): void
    {
        $I = $this;
        $navSelector = ".mws-nav-bar";
        $navHeight = intval($I->executeJS("return parseInt($('" . $navSelector . "').outerHeight()) || 0;"));
        $offsetY -= $navHeight;
        $offsetYStr = str_pad($offsetY, 1, "0", STR_PAD_LEFT);
        $offsetXStr = str_pad($offsetX, 1, "0", STR_PAD_LEFT);
        $xPath = $I->convertToXPath($selector);
        $scrollScript = "
            const t = $(\$x(\"$xPath\"));
            t[0].scrollIntoView({ // Take for CSS props
                behavior: 'instant', // WRONG, work with srollTo this way only
            });
            window.scroll(window.scrollX + $offsetXStr, window.scrollY + $offsetYStr);
            return ! t.is(':offscreen');
        ";

        // TODO : ensure scroll script : ? or keep with no scroll anims UX ?
        $scrollScript = "
            const t = $(\$x(\"$xPath\"));
            $('html').css('scroll-behavior', 'unset');
            t[0].scrollIntoView();
            window.scroll(window.scrollX + $offsetXStr, window.scrollY + $offsetYStr);
        ";
        $I->debug($scrollScript);
        $I->executeJS($scrollScript);

        try {
            // $I->debug($waitScript, $I->executeJS($waitScript));
            // $I->waitForJs($scrollScript, 7);
            $I->waitForJs($scrollScript, 1);
        } catch (\Exception $e) {
            $I->debug($e);
            $I->comment("Scroll timeout reached... might break");
        }
        $I->waitHumanDelay();
    }

    // public function _afterSuite() {
    //     dd($this->getMwsReport()); // TODO : not called... use custom report for custom json generation for html reports...
    // }   
}

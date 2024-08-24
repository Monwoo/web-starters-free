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

   /**
    * Define custom actions here
    */

    /**
     * Debug from tests :
     */
    public function debug(...$datas) {
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
        while($idx < $len && is_string($datas[0])) {
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
        while($idx < $len) {
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
            $I->debug('testIfPresent Fail. Not found. detail : ' . $e->getMessage());
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
            $navHeight = $I->executeJS("return parseInt($('#mainNavbar').outerHeight()) || 0;");
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

}

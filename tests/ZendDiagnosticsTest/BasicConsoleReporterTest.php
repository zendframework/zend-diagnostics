<?php
namespace ZFToolTest\Diagnostics\Reporter;


use ZFTool\Diagnostics\Reporter\BasicConsole;
use ZFTool\Diagnostics\Result\Collection;
use ZFTool\Diagnostics\Result\Failure;
use ZFTool\Diagnostics\Result\Success;
use ZFTool\Diagnostics\Result\Warning;
use ZFTool\Diagnostics\Result\Unknown;
use ZFTool\Diagnostics\RunEvent;
use ZFToolTest\Diagnostics\TestAsset\AlwaysSuccessTest;
use ZFToolTest\Diagnostics\TestAssets\ConsoleAdapter;
use ZFToolTest\Diagnostics\TestAssets\DummyReporter;
use Zend\Console\Charset\Ascii;
use Zend\EventManager\EventManager;

require_once __DIR__.'/../TestAsset/AlwaysSuccessTest.php';
require_once __DIR__.'/../TestAsset/ConsoleAdapter.php';

class BasicConsoleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \ZFToolTest\Diagnostics\TestAssets\ConsoleAdapter
     */
    protected $console;

    /**
     * @var \ZFTool\Diagnostics\Reporter\BasicConsole
     */
    protected $reporter;

    /**
     * @var \Zend\EventManager\EventManager;
     */
    protected $em;

    public function setUp()
    {
        $this->em = new EventManager();
        $this->console = new ConsoleAdapter();
        $this->console->setCharset(new Ascii());
        $this->reporter = new BasicConsole($this->console);
        $this->em->attachAggregate($this->reporter);
    }

    public function testDummyReporter()
    {
        $reporter = new DummyReporter();

    }
    public function testConsoleSettingGetting()
    {
        $this->assertSame($this->console, $this->reporter->getConsole());

        $newConsole = new ConsoleAdapter();
        $this->reporter->setConsole($newConsole);
        $this->assertSame($newConsole, $this->reporter->getConsole());
    }

    public function testStartMessage()
    {
        $e = new RunEvent();
        $tests = array(
            new AlwaysSuccessTest()
        );
        $e->setParam('tests',$tests);

        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        $this->assertStringMatchesFormat('Starting%A', ob_get_clean());
    }

    public function testProgressDots()
    {
        $e = new RunEvent();
        $tests = array_fill(0,5, new AlwaysSuccessTest());
        $e->setParam('tests', $tests);
        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_clean();

        foreach($tests as $test){
            $result = new Success();
            $e->setTarget($test);
            $e->setLastResult($result);
            $this->em->trigger(RunEvent::EVENT_AFTER_RUN, $e);
        }

        $this->assertEquals('.....', ob_get_clean());
    }

    public function testWarningSymbols()
    {
        $e = new RunEvent();
        $tests = array_fill(0,5, new AlwaysSuccessTest());
        $e->setParam('tests', $tests);
        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_get_clean();

        ob_start();
        foreach($tests as $test){
            $result = new Warning();
            $e->setTarget($test);
            $e->setLastResult($result);
            $this->em->trigger(RunEvent::EVENT_AFTER_RUN, $e);
        }

        $this->assertEquals('!!!!!', ob_get_clean());
    }

    public function testFailureSymbols()
    {
        $e = new RunEvent();
        $tests = array_fill(0,5, new AlwaysSuccessTest());
        $e->setParam('tests', $tests);
        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_get_clean();

        ob_start();
        foreach($tests as $test){
            $result = new Failure();
            $e->setTarget($test);
            $e->setLastResult($result);
            $this->em->trigger(RunEvent::EVENT_AFTER_RUN, $e);
        }

        $this->assertEquals('FFFFF', ob_get_clean());
    }

    public function testUnknownSymbols()
    {
        $e = new RunEvent();
        $tests = array_fill(0,5, new AlwaysSuccessTest());
        $e->setParam('tests', $tests);
        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_get_clean();

        ob_start();
        foreach($tests as $test){
            $result = new Unknown();
            $e->setTarget($test);
            $e->setLastResult($result);
            $this->em->trigger(RunEvent::EVENT_AFTER_RUN, $e);
        }

        $this->assertEquals('?????', ob_get_clean());
    }

    public function testProgressDotsNoGutter()
    {
        $e = new RunEvent();
        $this->console->setTestWidth(40);
        $tests = array_fill(0,40, new AlwaysSuccessTest());
        $e->setParam('tests', $tests);

        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_get_clean();

        ob_start();
        foreach($tests as $test){
            $result = new Success();
            $e->setTarget($test);
            $e->setLastResult($result);
            $this->em->trigger(RunEvent::EVENT_AFTER_RUN, $e);
        }

        $this->assertEquals(str_repeat('.', 40), ob_get_clean());
    }

    public function testProgressOverflow()
    {
        $e = new RunEvent();
        $this->console->setTestWidth(40);
        $tests = array_fill(0,80, new AlwaysSuccessTest());
        $e->setParam('tests', $tests);

        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_get_clean();

        ob_start();
        foreach($tests as $test){
            $result = new Success();
            $e->setTarget($test);
            $e->setLastResult($result);
            $this->em->trigger(RunEvent::EVENT_AFTER_RUN, $e);
        }

        $expected  = '......................... 25 / 80 ( 31%)';
        $expected .= '......................... 50 / 80 ( 63%)';
        $expected .= '......................... 75 / 80 ( 94%)';
        $expected .= '.....';

        $this->assertEquals($expected, ob_get_clean());
    }

    public function testProgressOverflowMatch()
    {
        $e = new RunEvent();
        $this->console->setTestWidth(40);
        $tests = array_fill(0,75, new AlwaysSuccessTest());
        $e->setParam('tests', $tests);

        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_get_clean();

        ob_start();
        foreach($tests as $test){
            $result = new Success();
            $e->setTarget($test);
            $e->setLastResult($result);
            $this->em->trigger(RunEvent::EVENT_AFTER_RUN, $e);
        }


        $expected  = '......................... 25 / 75 ( 33%)';
        $expected .= '......................... 50 / 75 ( 67%)';
        $expected .= '......................... 75 / 75 (100%)';

        $this->assertEquals($expected, ob_get_clean());
    }

    public function testSummaryAllSuccessful()
    {
        $e = new RunEvent();
        $tests = array();
        $test = null;
        $results = new Collection();
        for($x = 0; $x < 20; $x++){
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Success();
        }

        $e->setParam('tests', $tests);
        $e->setResults($results);

        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_clean();

        $this->em->trigger(RunEvent::EVENT_FINISH, $e);
        $this->assertStringStartsWith('OK (20 diagnostic tests)', trim(ob_get_clean()));
    }

    public function testSummaryWithWarnings()
    {
        $e = new RunEvent();
        $tests = array();
        $test = null;
        $results = new Collection();
        for ($x = 0; $x < 15; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Success();
        }

        for ($x = 0; $x < 5; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Warning();
        }

        $e->setParam('tests', $tests);
        $e->setResults($results);

        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_clean();

        $this->em->trigger(RunEvent::EVENT_FINISH, $e);
        $this->assertStringStartsWith('5 warnings, 15 successful tests', trim(ob_get_clean()));
    }

    public function testSummaryWithFailures()
    {
        $e = new RunEvent();
        $tests = array();
        $test = null;
        $results = new Collection();
        for ($x = 0; $x < 15; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Success();
        }

        for ($x = 0; $x < 5; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Warning();
        }

        for ($x = 0; $x < 5; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Failure();
        }

        $e->setParam('tests', $tests);
        $e->setResults($results);

        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_clean();

        $this->em->trigger(RunEvent::EVENT_FINISH, $e);
        $this->assertStringStartsWith('5 failures, 5 warnings, 15 successful tests', trim(ob_get_clean()));
    }

    public function testSummaryWithUnknowns()
    {
        $e = new RunEvent();
        $tests = array();
        $test = null;
        $results = new Collection();
        for ($x = 0; $x < 15; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Success();
        }

        for ($x = 0; $x < 5; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Warning();
        }

        for ($x = 0; $x < 5; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Unknown();
        }

        $e->setParam('tests', $tests);
        $e->setResults($results);

        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_clean();

        $this->em->trigger(RunEvent::EVENT_FINISH, $e);
        $this->assertStringMatchesFormat('%A5 unknown test results%A', trim(ob_get_clean()));
    }

    public function testWarnings()
    {
        $e = new RunEvent();
        $tests = array();
        $test = null;
        $results = new Collection();
        for ($x = 0; $x < 15; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Success();
        }

        $tests[] = $test = new AlwaysSuccessTest();
        $results[$test] = new Warning('foo');

        $e->setParam('tests', $tests);
        $e->setResults($results);

        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_clean();

        $this->em->trigger(RunEvent::EVENT_FINISH, $e);
        $this->assertStringMatchesFormat(
            '%AWarning: Always Successful Test%wfoo',
            trim(ob_get_clean())
        );
    }

    public function testFailures()
    {
        $e = new RunEvent();
        $tests = array();
        $test = null;
        $results = new Collection();
        for ($x = 0; $x < 15; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Success();
        }

        $tests[] = $test = new AlwaysSuccessTest();
        $results[$test] = new Failure('bar');

        $e->setParam('tests', $tests);
        $e->setResults($results);

        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_clean();

        $this->em->trigger(RunEvent::EVENT_FINISH, $e);
        $this->assertStringMatchesFormat(
            '%AFailure: Always Successful Test%wbar',
            trim(ob_get_clean())
        );
    }

    public function testUnknowns()
    {
        $e = new RunEvent();
        $tests = array();
        $test = null;
        $results = new Collection();
        for ($x = 0; $x < 15; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Success();
        }

        $tests[] = $test = new AlwaysSuccessTest();
        $results[$test] = new Unknown('baz');

        $e->setParam('tests', $tests);
        $e->setResults($results);

        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_clean();

        $this->em->trigger(RunEvent::EVENT_FINISH, $e);
        $this->assertStringMatchesFormat(
            '%AUnknown result ZFTool\Diagnostics\Result\Unknown: Always Successful Test%wbaz%A',
            trim(ob_get_clean())
        );
    }

    public function testStoppedNotice()
    {
        $e = new RunEvent();
        $tests = array();
        $test = null;
        $results = new Collection();
        for ($x = 0; $x < 15; $x++) {
            $tests[] = $test = new AlwaysSuccessTest();
            $results[$test] = new Success();
        }

        $e->setParam('tests', $tests);
        $e->setResults($results);

        ob_start();
        $this->em->trigger(RunEvent::EVENT_START, $e);
        ob_clean();

        $this->em->trigger(RunEvent::EVENT_STOP, $e);

        $this->em->trigger(RunEvent::EVENT_FINISH, $e);
        $this->assertStringMatchesFormat('%ADiagnostics aborted%A', trim(ob_get_clean()));
    }


}

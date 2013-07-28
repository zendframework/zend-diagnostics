<?php
namespace ZendDiagnosticsTest;

use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Warning;
use ZendDiagnostics\Runner\Runner;
use ZendDiagnostics\Runner\Reporter\BasicConsole;
use ZendDiagnosticsTest\TestAsset\Result\Unknown;
use ZendDiagnosticsTest\TestAsset\Check\AlwaysFailure;
use ZendDiagnosticsTest\TestAsset\Check\AlwaysSuccess;
use ZendDiagnosticsTest\TestAsset\Check\ReturnThis;
use ZendDiagnosticsTest\TestAsset\Check\ThrowException;
use ZendDiagnosticsTest\TestAsset\Check\TriggerUserError;
use ZendDiagnosticsTest\TestAsset\Check\TriggerWarning;

class RunnerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Runner
     */
    protected $runner;

    public function setUp()
    {
        $this->runner = new Runner();
    }

    public function checksAndResultsProvider()
    {
        return array(
            array(
                $success = new Success(),
                $success,
            ),
            array(
                $warning = new Warning(),
                $warning,
            ),
            array(
                $failure = new Failure(),
                $failure,
            ),
            array(
                $unknown = new Unknown(),
                $unknown,
            ),
            array(
                true,
                'ZendDiagnostics\Result\Success'
            ),
            array(
                false,
                'ZendDiagnostics\Result\Failure'
            ),
            array(
                null,
                'ZendDiagnostics\Result\Failure',
            ),
            array(
                new \stdClass(),
                'ZendDiagnostics\Result\Failure',
            ),
            array(
                'abc',
                'ZendDiagnostics\Result\Warning',
            ),
        );
    }

    public function testConfig()
    {
        $this->assertFalse($this->runner->getBreakOnFailure());
        $this->assertTrue(is_numeric($this->runner->getCatchErrorSeverity()));

        $this->runner->setConfig(array(
            'break_on_failure'     => true,
            'catch_error_severity' => 100
        ));

        $this->assertTrue($this->runner->getBreakOnFailure());
        $this->assertSame(100, $this->runner->getCatchErrorSeverity());

        $this->runner->setBreakOnFailure(false);
        $this->runner->setCatchErrorSeverity(200);

        $this->assertFalse($this->runner->getBreakOnFailure());
        $this->assertSame(200, $this->runner->getCatchErrorSeverity());

        $this->runner = new Runner(array(
            'break_on_failure'     => true,
            'catch_error_severity' => 300
        ));

        $this->assertTrue($this->runner->getBreakOnFailure());
        $this->assertSame(300, $this->runner->getCatchErrorSeverity());
        $this->assertEquals(array(
            'break_on_failure'     => true,
            'catch_error_severity' => 300
        ), $this->runner->getConfig());
    }

    public function testInvalidValueForSetConfig()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->runner->setConfig(10);
    }

    public function testUnknownValueInConfig()
    {
        $this->setExpectedException('BadMethodCallException');
        $this->runner->setConfig(array(
            'foo' => 'bar'
        ));
    }

    public function testManagingChecks()
    {
        $check1 = new AlwaysSuccess();
        $check2 = new AlwaysSuccess();
        $check3 = new AlwaysSuccess();
        $this->runner->addCheck($check1);
        $this->runner->addChecks(array(
            $check2,
            $check3
        ));
        $this->assertContains($check1, $this->runner->getChecks());
        $this->assertContains($check2, $this->runner->getChecks());
        $this->assertContains($check3, $this->runner->getChecks());
    }

    public function testManagingChecksWithAliases()
    {
        $check1 = new AlwaysSuccess();
        $check2 = new AlwaysSuccess();
        $check3 = new AlwaysSuccess();
        $this->runner->addCheck($check1, 'foo');
        $this->runner->addCheck($check2, 'bar');
        $this->assertSame($check1, $this->runner->getCheck('foo'));
        $this->assertSame($check2, $this->runner->getCheck('bar'));

        $this->runner->addChecks(array(
            'baz' => $check3,
        ));
        $this->assertSame($check3, $this->runner->getCheck('baz'));
    }

    public function testGetNonExistentAliasThrowsException()
    {
        $this->setExpectedException('RuntimeException');
        $this->runner->getCheck('non-existent-check');
    }

    public function testConstructionWithChecks()
    {
        $check1 = new AlwaysSuccess();
        $check2 = new AlwaysSuccess();
        $this->runner = new Runner(array(), array($check1, $check2));
        $this->assertEquals(2, count($this->runner->getChecks()));
        $this->assertContains($check1, $this->runner->getChecks());
        $this->assertContains($check2, $this->runner->getChecks());
    }

    public function testConstructionWithReporter()
    {
        $reporter = $this->getMock('ZendDiagnosticsTest\TestAsset\Reporter\AbstractReporter');
        $this->runner = new Runner(array(), array(), $reporter);
        $this->assertEquals(1, count($this->runner->getReporters()));
        $this->assertContains($reporter, $this->runner->getReporters());
    }

    public function testAddInvalidCheck()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->runner->addChecks(array( new \stdClass()));
    }

    public function testAddWrongParam()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->runner->addChecks('foo');
    }

    public function testAddReporter()
    {
        $reporter = new BasicConsole();
        $this->runner->addReporter($reporter);
        $this->assertContains($reporter, $this->runner->getReporters());
    }

    public function testRemoveReporter()
    {
        $reporter1 = new BasicConsole();
        $reporter2 = new BasicConsole();
        $this->runner->addReporter($reporter1);
        $this->runner->addReporter($reporter2);
        $this->assertContains($reporter1, $this->runner->getReporters());
        $this->assertContains($reporter2, $this->runner->getReporters());
        $this->runner->removeReporter($reporter1);
        $this->assertNotContains($reporter1, $this->runner->getReporters());
        $this->assertContains($reporter2, $this->runner->getReporters());
    }

    public function testStart()
    {
        $this->runner->addCheck(new AlwaysSuccess());
        $mock = $this->getMock('ZendDiagnosticsTest\TestAsset\Reporter\AbstractReporter', array('onStart'));
        $mock->expects($this->once())->method('onStart')->with($this->isInstanceOf('\ArrayObject'), $this->isType('array'));
        $this->runner->addReporter($mock);
        $this->runner->run();
    }

    public function testBeforeRun()
    {
        $check = new AlwaysSuccess();
        $this->runner->addCheck($check);
        $mock = $this->getMock('ZendDiagnosticsTest\TestAsset\Reporter\AbstractReporter', array('onBeforeRun'));
        $mock->expects($this->once())->method('onBeforeRun')->with($this->identicalTo($check));
        $this->runner->addReporter($mock);
        $this->runner->run();
    }

    public function testAfterRun()
    {
        $check = new AlwaysSuccess();
        $this->runner->addCheck($check);
        $mock = $this->getMock('ZendDiagnosticsTest\TestAsset\Reporter\AbstractReporter', array('onAfterRun'));
        $mock->expects($this->once())->method('onAfterRun')->with($this->identicalTo($check));
        $this->runner->addReporter($mock);
        $this->runner->run();
    }

    /**
     * @dataProvider checksAndResultsProvider
     */
    public function testStandardResults($value, $expectedResult)
    {
        $check = new ReturnThis($value);
        $this->runner->addCheck($check);
        $results = $this->runner->run();

        if (is_string($expectedResult)) {
            $this->assertInstanceOf($expectedResult, $results[$check]);
        } else {
            $this->assertSame($expectedResult, $results[$check]);
        }
    }

    public function testGetLastResult()
    {
        $this->runner->addCheck(new AlwaysSuccess());
        $result = $this->runner->run();
        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $result);
        $this->assertSame($result, $this->runner->getLastResults());
    }

    public function testExceptionResultsInFailure()
    {
        $exception = new \Exception();
        $check = new ThrowException($exception);
        $this->runner->addCheck($check);
        $results = $this->runner->run();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $results[$check]);
    }

    public function testPHPWarningResultsInFailure()
    {
        $check = new TriggerWarning();
        $this->runner->addCheck($check);
        $results = $this->runner->run();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $results[$check]);
        $this->assertInstanceOf('ErrorException', $results[$check]->getData());
        $this->assertEquals(E_WARNING, $results[$check]->getData()->getSeverity());
    }

    public function testPHPUserErrorResultsInFailure()
    {
        $check = new TriggerUserError('error', E_USER_ERROR);
        $this->runner->addCheck($check);
        $results = $this->runner->run();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $results[$check]);
        $this->assertInstanceOf('ErrorException', $results[$check]->getData());
        $this->assertEquals(E_USER_ERROR, $results[$check]->getData()->getSeverity());
    }

    public function testBreakOnFirstFailure()
    {
        $check1 = new AlwaysFailure();
        $check2 = new AlwaysSuccess();
        $this->runner->addCheck($check1);
        $this->runner->addCheck($check2);
        $this->runner->setBreakOnFailure(true);

        $results = $this->runner->run();

        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $results);
        $this->assertEquals(1, $results->count());
        $this->assertFalse($results->offsetExists($check2));
        $this->assertInstanceOf('ZendDiagnostics\Result\FailureInterface', $results->offsetGet($check1));
    }

    public function testBeforeRunSkipTest()
    {
        $check1 = new AlwaysSuccess();
        $check2 = new AlwaysSuccess();
        $this->runner->addCheck($check1);
        $this->runner->addCheck($check2);

        $mock = $this->getMock('ZendDiagnosticsTest\TestAsset\Reporter\AbstractReporter', array('onBeforeRun'));
        $mock->expects($this->atLeastOnce())
            ->method('onBeforeRun')
            ->with($this->isInstanceOf('ZendDiagnostics\Check\CheckInterface'))
            ->will($this->onConsecutiveCalls(
                false, true
            ))
        ;
        $this->runner->addReporter($mock);

        $results = $this->runner->run();

        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $results);
        $this->assertEquals(1, $results->count());
        $this->assertFalse($results->offsetExists($check1));
        $this->assertInstanceOf('ZendDiagnostics\Result\SuccessInterface', $results->offsetGet($check2));
    }

    public function testAfterRunStopTesting()
    {
        $check1 = new AlwaysSuccess();
        $check2 = new AlwaysSuccess();
        $this->runner->addCheck($check1);
        $this->runner->addCheck($check2);

        $mock = $this->getMock('ZendDiagnosticsTest\TestAsset\Reporter\AbstractReporter', array('onAfterRun'));
        $mock->expects($this->atLeastOnce())
            ->method('onAfterRun')
            ->with($this->isInstanceOf('ZendDiagnostics\Check\CheckInterface'))
            ->will($this->onConsecutiveCalls(
                false, true
            ))
        ;
        $this->runner->addReporter($mock);

        $results = $this->runner->run();

        $this->assertInstanceOf('ZendDiagnostics\Result\Collection', $results);
        $this->assertEquals(1, $results->count());
        $this->assertFalse($results->offsetExists($check2));
        $this->assertInstanceOf('ZendDiagnostics\Result\SuccessInterface', $results->offsetGet($check1));
    }
}

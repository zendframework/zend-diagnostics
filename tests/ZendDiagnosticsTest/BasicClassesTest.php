<?php
namespace ZendDiagnosticsTest;

use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Result\Warning;
use ZendDiagnostics\Result\Failure;
use ZendDiagnosticsTest\TestAsset\Check\AlwaysSuccess;

class BasicClassesTest extends \PHPUnit_Framework_TestCase
{
    public function testCoreClassTree()
    {
        foreach (array(
            'ZendDiagnostics\Check\CheckInterface',
            'ZendDiagnostics\Result\SuccessInterface',
            'ZendDiagnostics\Result\FailureInterface',
            'ZendDiagnostics\Result\WarningInterface',
        ) as $class) {
            $this->assertTrue(interface_exists($class, true), 'Class "' . $class . '" exists.');
        }

        foreach (array(
            'ZendDiagnostics\Check\AbstractCheck',
            'ZendDiagnostics\Result\AbstractResult',
            'ZendDiagnostics\Result\Success',
            'ZendDiagnostics\Result\Failure',
            'ZendDiagnostics\Result\Warning',
        ) as $class) {
            $this->assertTrue(class_exists($class, true), 'Class "' . $class . '" exists.');
        }
        foreach (array(
            'ZendDiagnostics\Result\Success',
            'ZendDiagnostics\Result\Failure',
            'ZendDiagnostics\Result\Warning',
            'ZendDiagnostics\Result\SuccessInterface',
            'ZendDiagnostics\Result\FailureInterface',
            'ZendDiagnostics\Result\WarningInterface',
        ) as $class) {
            $reflection = new \ReflectionClass($class);
            $this->assertTrue($reflection->implementsInterface('ZendDiagnostics\Result\ResultInterface'));
        }
    }

    public function testConstructor()
    {
        $result = new Success('foo', 'bar');
        $this->assertInstanceOf('ZendDiagnostics\Result\ResultInterface', $result);
        $this->assertSame('foo', $result->getMessage());
        $this->assertSame('bar', $result->getData());

        $result = new Failure('foo', 'bar');
        $this->assertInstanceOf('ZendDiagnostics\Result\ResultInterface', $result);
        $this->assertSame('foo', $result->getMessage());
        $this->assertSame('bar', $result->getData());

        $result = new Warning('foo', 'bar');
        $this->assertInstanceOf('ZendDiagnostics\Result\ResultInterface', $result);
        $this->assertSame('foo', $result->getMessage());
        $this->assertSame('bar', $result->getData());
    }

    public function testSetters()
    {
        $result = new Success();
        $this->assertSame(null, $result->getMessage());
        $this->assertSame(null, $result->getData());

        $result->setMessage('foo');
        $result->setData('bar');
        $this->assertSame('foo', $result->getMessage());
        $this->assertSame('bar', $result->getData());
    }

    public function testSimpleCheck()
    {
        $alwaysSuccess = new AlwaysSuccess();
        $this->assertNotNull($alwaysSuccess->getLabel());
        $this->assertSame($alwaysSuccess->getName(), $alwaysSuccess->getLabel());
        $this->assertSame('Always Success', trim($alwaysSuccess->getLabel()), 'Class-deferred label');

        $alwaysSuccess->setLabel('foobar');
        $this->assertSame('foobar', $alwaysSuccess->getName(), 'Explicitly set label');
        $this->assertSame($alwaysSuccess->getName(), $alwaysSuccess->getLabel());

        $result = $alwaysSuccess->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\ResultInterface', $result);
        $this->assertNotNull($result->getMessage());
    }
}

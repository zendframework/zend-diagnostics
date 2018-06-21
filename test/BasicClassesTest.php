<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2013-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

namespace ZendDiagnosticsTest;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ZendDiagnostics\Check\AbstractCheck;
use ZendDiagnostics\Check\CheckInterface;
use ZendDiagnostics\Result\AbstractResult;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\FailureInterface;
use ZendDiagnostics\Result\ResultInterface;
use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Result\SuccessInterface;
use ZendDiagnostics\Result\Warning;
use ZendDiagnostics\Result\WarningInterface;
use ZendDiagnosticsTest\TestAsset\Check\AlwaysSuccess;

class BasicClassesTest extends TestCase
{
    public function testCoreClassTree()
    {
        foreach ([
            CheckInterface::class,
            SuccessInterface::class,
            FailureInterface::class,
            WarningInterface::class,
        ] as $class) {
            $this->assertTrue(interface_exists($class, true), 'Class "' . $class . '" exists.');
        }

        foreach ([
            AbstractCheck::class,
            AbstractResult::class,
            Success::class,
            Failure::class,
            Warning::class,
        ] as $class) {
            $this->assertTrue(class_exists($class, true), 'Class "' . $class . '" exists.');
        }
        foreach ([
            Success::class,
            Failure::class,
            Warning::class,
            SuccessInterface::class,
            FailureInterface::class,
            WarningInterface::class,
        ] as $class) {
            $reflection = new ReflectionClass($class);
            $this->assertTrue($reflection->implementsInterface(ResultInterface::class));
        }
    }

    public function testConstructor()
    {
        $result = new Success('foo', 'bar');
        $this->assertInstanceOf(ResultInterface::class, $result);
        $this->assertSame('foo', $result->getMessage());
        $this->assertSame('bar', $result->getData());

        $result = new Failure('foo', 'bar');
        $this->assertInstanceOf(ResultInterface::class, $result);
        $this->assertSame('foo', $result->getMessage());
        $this->assertSame('bar', $result->getData());

        $result = new Warning('foo', 'bar');
        $this->assertInstanceOf(ResultInterface::class, $result);
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
        $this->assertInstanceOf(ResultInterface::class, $result);
        $this->assertNotNull($result->getMessage());
    }
}

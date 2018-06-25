<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2013-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

namespace ZendDiagnosticsTest;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use ZendDiagnostics\Check\CheckInterface;
use ZendDiagnostics\Check\DirWritable;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;

class DirWritableTest extends TestCase
{
    /**
     * @var string
     */
    private $checkClass = DirWritable::class;

    /**
     * @test
     */
    public function shouldImplementCheckInterface()
    {
        $this->assertInstanceOf(
            CheckInterface::class,
            $this->prophesize($this->checkClass)->reveal()
        );
    }

    /**
     * @dataProvider providerValidConstructorArguments
     */
    public function testConstructor($arguments)
    {
        $object = new DirWritable($arguments);
    }

    public function providerValidConstructorArguments()
    {
        return [
            [__DIR__],
            [vfsStream::setup()->url()],
            [[__DIR__, vfsStream::setup()->url()]]
        ];
    }

    public function testCheckSuccessSinglePath()
    {
        $object = new DirWritable(vfsStream::setup()->url());
        $r = $object->check();
        $this->assertInstanceOf(Success::class, $r);
        $this->assertEquals('The path is a writable directory.', $r->getMessage());
    }

    public function testCheckSuccessMultiplePaths()
    {
        $object = new DirWritable([__DIR__, vfsStream::setup()->url()]);
        $r = $object->check();
        $this->assertInstanceOf(Success::class, $r);
        $this->assertEquals('All paths are writable directories.', $r->getMessage());
    }

    public function testCheckFailureSingleInvalidDir()
    {
        $object = new DirWritable('notadir');
        $r = $object->check();
        $this->assertInstanceOf(Failure::class, $r);
        $this->assertContains('notadir is not a valid directory.', $r->getMessage());
    }

    public function testCheckFailureMultipleInvalidDirs()
    {
        $object = new DirWritable(['notadir1', 'notadir2']);
        $r = $object->check();
        $this->assertInstanceOf(Failure::class, $r);
        $this->assertContains('The following paths are not valid directories: notadir1, notadir2.', $r->getMessage());
    }

    public function testCheckFailureSingleUnwritableDir()
    {
        $root = vfsStream::setup();
        $unwritableDir = vfsStream::newDirectory('unwritabledir', 000)->at($root);
        $object = new DirWritable($unwritableDir->url());
        $r = $object->check();
        $this->assertInstanceOf(Failure::class, $r);
        $this->assertEquals('vfs://root/unwritabledir directory is not writable.', $r->getMessage());
    }

    public function testCheckFailureMultipleUnwritableDirs()
    {
        $root = vfsStream::setup();
        $unwritableDir1 = vfsStream::newDirectory('unwritabledir1', 000)->at($root);
        $unwritableDir2 = vfsStream::newDirectory('unwritabledir2', 000)->at($root);

        $object = new DirWritable([$unwritableDir1->url(), $unwritableDir2->url()]);
        $r = $object->check();
        $this->assertInstanceOf(Failure::class, $r);
        $this->assertEquals(
            'The following directories are not writable: vfs://root/unwritabledir1, vfs://root/unwritabledir2.',
            $r->getMessage()
        );
    }
}

<?php
namespace ZendDiagnosticsTest;

use org\bovigo\vfs\vfsStream;
use ZendDiagnostics\Check\DirWritable;

class DirWritableTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $checkClass = 'ZendDiagnostics\Check\DirWritable';

    /**
     * @test
     */
    public function shouldImplementCheckInterface()
    {
        $this->assertInstanceOf(
            'ZendDiagnostics\Check\CheckInterface',
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
        return array(
            array(__DIR__),
            array(vfsStream::setup()->url()),
            array(array(__DIR__, vfsStream::setup()->url()))
        );
    }

    public function testCheckSuccessSinglePath()
    {
        $object = new DirWritable(vfsStream::setup()->url());
        $r = $object->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $r);
        $this->assertEquals('The path is a writable directory.', $r->getMessage());
    }

    public function testCheckSuccessMultiplePaths()
    {
        $object = new DirWritable(array(__DIR__, vfsStream::setup()->url()));
        $r = $object->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $r);
        $this->assertEquals('All paths are writable directories.', $r->getMessage());
    }

    public function testCheckFailureSingleInvalidDir()
    {
        $object = new DirWritable('notadir');
        $r = $object->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $r);
        $this->assertContains('notadir is not a valid directory.', $r->getMessage());
    }

    public function testCheckFailureMultipleInvalidDirs()
    {
        $object = new DirWritable(array('notadir1', 'notadir2'));
        $r = $object->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $r);
        $this->assertContains('The following paths are not valid directories: notadir1, notadir2.', $r->getMessage());
    }

    public function testCheckFailureSingleUnwritableDir()
    {
        $root = vfsStream::setup();
        $unwritableDir = vfsStream::newDirectory('unwritabledir', 000)->at($root);
        $object = new DirWritable($unwritableDir->url());
        $r = $object->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $r);
        $this->assertEquals('vfs://root/unwritabledir directory is not writable.', $r->getMessage());
    }

    public function testCheckFailureMultipleUnwritableDirs()
    {
        $root = vfsStream::setup();
        $unwritableDir1 = vfsStream::newDirectory('unwritabledir1', 000)->at($root);
        $unwritableDir2 = vfsStream::newDirectory('unwritabledir2', 000)->at($root);

        $object = new DirWritable(array($unwritableDir1->url(), $unwritableDir2->url()));
        $r = $object->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $r);
        $this->assertEquals('The following directories are not writable: vfs://root/unwritabledir1, vfs://root/unwritabledir2.', $r->getMessage());
    }
}

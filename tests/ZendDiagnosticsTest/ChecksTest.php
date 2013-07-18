<?php
namespace ZendDiagnosticsTest\Check;

use ZendDiagnostics\Check\Callback;
use ZendDiagnostics\Check\ClassExists;
use ZendDiagnostics\Check\CpuPerformance;
use ZendDiagnostics\Check\DirReadable;
use ZendDiagnostics\Check\DirWritable;
use ZendDiagnostics\Check\ExtensionLoaded;
use ZendDiagnostics\Check\PhpVersion;
use ZendDiagnostics\Check\StreamWrapperExists;
use ZendDiagnostics\Result\Success;
use ZendDiagnosticsTest\Check\AlwaysSuccess;

class BasicTestsTest extends \PHPUnit_Framework_TestCase
{
    public function testLabels()
    {
        $label = md5(rand());
        $check = new AlwaysSuccess();
        $check->setLabel($label);
        $this->assertEquals($label, $check->getLabel());
    }

    public function testCpuPerformance()
    {
        $check = new CpuPerformance(0); // minimum threshold
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $result);

        $check = new CpuPerformance(999999999); // improbable to archive
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $result);
    }

    public function testClassExists()
    {
        $check = new ClassExists(__CLASS__);
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $check->check());

        $check = new ClassExists('improbableClassNameInGlobalNamespace999999999999999999');
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $check->check());

        $check = new ClassExists(array(
            __CLASS__,
            'ZendDiagnostics\Result\Success',
            'ZendDiagnostics\Result\Failure',
            'ZendDiagnostics\Result\Warning',
        ));
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $check->check());

        $check = new ClassExists(array(
            __CLASS__,
            'ZendDiagnostics\Result\Success',
            'improbableClassNameInGlobalNamespace999999999999999999',
            'ZendDiagnostics\Result\Failure',
            'ZendDiagnostics\Result\Warning',
        ));
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $check->check());
    }

    public function testClassExistsExplanation()
    {
        $check = new ClassExists(array(
            __CLASS__,
            'ZendDiagnostics\Result\Success',
            'improbableClassNameInGlobalNamespace888',
            'improbableClassNameInGlobalNamespace999',
            'ZendDiagnostics\Result\Failure',
            'ZendDiagnostics\Result\Warning',
        ));
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $result);
        $this->assertStringMatchesFormat('%simprobableClassNameInGlobalNamespace888%s', $result->getMessage());
        $this->assertStringMatchesFormat('%simprobableClassNameInGlobalNamespace999', $result->getMessage());
    }

    public function testPhpVersion()
    {
        $check = new PhpVersion(PHP_VERSION); // default operator
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $check->check());

        $check = new PhpVersion(PHP_VERSION, '='); // explicit equal
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $check->check());

        $check = new PhpVersion(PHP_VERSION, '<'); // explicit less than
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $check->check());
    }

    public function testPhpVersionArray()
    {
        $check = new PhpVersion(array(PHP_VERSION)); // default operator
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $check->check());

        $check = new PhpVersion(array(
            '1.0.0',
            '1.1.0',
            '1.1.1',
        ), '<'); // explicit less than
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $check->check());

        $check = new PhpVersion(new \ArrayObject(array(
            '40.0.0',
            '41.0.0',
            '42.0.0',
        )), '<'); // explicit less than
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $check->check());

        $check = new PhpVersion(new \ArrayObject(array(
            '41.0.0',
            PHP_VERSION,
        )), '!='); // explicit less than
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $check->check());

    }

    public function testCallback()
    {
        $called = false;
        $expectedResult = new Success();
        $check = new Callback(function () use (&$called, $expectedResult) {
            $called = true;

            return $expectedResult;
        });
        $result = $check->check();
        $this->assertTrue($called);
        $this->assertSame($expectedResult, $result);
    }

    public function testExtensionLoaded()
    {
        $allExtensions = get_loaded_extensions();
        $ext1 = $allExtensions[array_rand($allExtensions)];

        $check = new ExtensionLoaded($ext1);
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $check->check());

        $check = new ExtensionLoaded('improbableExtName999999999999999999');
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $check->check());

        $extensions = array();
        foreach (array_rand($allExtensions, 3) as $key) {
            $extensions[] = $allExtensions[$key];
        }

        $check = new ExtensionLoaded($extensions);
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $check->check());

        $extensions[] = 'improbableExtName9999999999999999999999';

        $check = new ExtensionLoaded($extensions);
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $check->check());

        $extensions = array(
            'improbableExtName9999999999999999999999',
            'improbableExtName0000000000000000000000',
        );

        $check = new ExtensionLoaded($extensions);
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $check->check());
    }

    public function testStreamWrapperExists()
    {
        $allWrappers = stream_get_wrappers();
        $wrapper = $allWrappers[array_rand($allWrappers)];

        $check = new StreamWrapperExists($wrapper);
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $check->check());

        $check = new StreamWrapperExists('improbableName999999999999999999');
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $check->check());

        $wrappers = array();
        foreach (array_rand($allWrappers, 3) as $key) {
            $wrappers[] = $allWrappers[$key];
        }

        $check = new StreamWrapperExists($wrappers);
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $check->check());

        $wrappers[] = 'improbableName9999999999999999999999';

        $check = new StreamWrapperExists($wrappers);
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $check->check());

        $wrappers = array(
            'improbableName9999999999999999999999',
            'improbableName0000000000000000000000',
        );

        $check = new StreamWrapperExists($wrappers);
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $check->check());
    }

    public function testDirReadable()
    {
        $check = new DirReadable(__DIR__);
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $result);

        $check = new DirReadable(array(
            __DIR__,
            __DIR__ . '/../'
        ));
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $result, 'Array of valid dirs');

        $check = new DirReadable(__FILE__);
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $result, 'An existing file');

        $check = new DirReadable(__DIR__ . '/improbabledir99999999999999999999999999999999999999');
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $result, 'Single non-existent dir');

        $check = new DirReadable(__DIR__ . '/improbabledir999999999999');
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $result);
        $this->assertStringMatchesFormat('%simprobabledir999999999999%s', $result->getMessage());

        $check = new DirReadable(array(
            __DIR__ . '/improbabledir888888888888',
            __DIR__ . '/improbabledir999999999999',
        ));
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $result);
        $this->assertStringMatchesFormat('%simprobabledir888888888888%s', $result->getMessage());
        $this->assertStringMatchesFormat('%simprobabledir999999999999', $result->getMessage());

        // create a barrage of unreadable directories
        $tmpDir = sys_get_temp_dir();
        if (!is_dir($tmpDir) || !is_writable($tmpDir)) {
            $this->markTestSkipped('Cannot access writable system temp dir to perform the test... ');

            return;
        }

        // generate a random dir name
        while (($dir1 = $tmpDir . '/test' . mt_rand(1, PHP_INT_MAX)) && file_exists($dir1)) {
        }
        while (($dir2 = $tmpDir . '/test' . mt_rand(1, PHP_INT_MAX)) && file_exists($dir2)) {
        }

        // create temporary unreadable directories
        if (
            !mkdir($dir1) || !chmod($dir1, 0000) ||
            !mkdir($dir2) || !chmod($dir2, 0000)
        ) {
            $this->markTestSkipped('Cannot create unreadable temporary directory to perform the test... ');

            return;
        }

        // run the check
        $check = new DirReadable(array(
            $dir1, // unreadable
            $dir2, // unreadable
            $tmpDir, // valid one
            __DIR__ . '/simprobabledir999999999999', // non existent
        ));
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $result);
        $this->assertStringMatchesFormat('%s' . $dir1 . '%s', $result->getMessage());
        $this->assertStringMatchesFormat('%s' . $dir2 . '%s', $result->getMessage());
        $this->assertStringMatchesFormat('%simprobabledir999999999999', $result->getMessage());

        chmod($dir1, 0777);
        chmod($dir2, 0777);
        rmdir($dir1);
        rmdir($dir2);
    }

    public function testDirWritable()
    {
        // single non-existent dir
        $path = __DIR__ . '/simprobabledir999999999999';
        $check = new DirWritable($path);
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $result, 'Non-existent path');
        $this->assertStringMatchesFormat($path . '%s', $result->getMessage());

        // non-dir path
        $path = __FILE__;
        $check = new DirWritable($path);
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $result, 'Non-dir path');
        $this->assertStringMatchesFormat($path . '%s', $result->getMessage());

        // multiple non-dir paths
        $path1 = __FILE__;
        $path2 = __DIR__ . '/BasicClassesTest.php';
        $check = new DirWritable(array($path1, $path2));
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $result, 'Non-dir path');
        $this->assertStringMatchesFormat('%s' . $path1 . '%s', $result->getMessage());
        $this->assertStringMatchesFormat('%s' . $path2, $result->getMessage());

        // create a barrage of unwritable directories
        $tmpDir = sys_get_temp_dir();
        if (!is_dir($tmpDir) || !is_writable($tmpDir)) {
            $this->markTestSkipped('Cannot access writable system temp dir to perform the test... ');

            return;
        }

        // this should succeed
        $check = new DirWritable($tmpDir);
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $result, 'Single writable dir');

        // generate a random dir name
        while (($dir1 = $tmpDir . '/test' . mt_rand(1, PHP_INT_MAX)) && file_exists($dir1)) {
        }
        while (($dir2 = $tmpDir . '/test' . mt_rand(1, PHP_INT_MAX)) && file_exists($dir2)) {
        }

        // create temporary writable directories
        if (!mkdir($dir1) || !mkdir($dir2)) {
            $this->markTestSkipped('Cannot create unreadable temporary directory to perform the test... ');

            return;
        }

        // we should now have 3 writable directories
        $check = new DirWritable(array(
            $tmpDir,
            $dir1,
            $dir2,
        ));
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $result, 'Multiple writable dirs');

        // make temporary dirs unwritable
        if (!chmod($dir1, 0000) || !chmod($dir2, 0000)) {
            $this->markTestSkipped('Cannot create unreadable temporary directory to perform the test... ');

            return;
        }

        // single unwritable dir
        $check = new DirWritable($dir1);
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $result);
        $this->assertStringMatchesFormat($dir1 . '%s', $result->getMessage());

        // this should now fail
        $check = new DirWritable(array(
            $dir1, // unwritable
            $dir2, // unwritable
            $tmpDir, // valid one
            __DIR__ . '/simprobabledir999999999999', // non existent
        ));
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $result);
        $this->assertStringMatchesFormat('%s' . $dir1 . '%s', $result->getMessage());
        $this->assertStringMatchesFormat('%s' . $dir2 . '%s', $result->getMessage());
        $this->assertStringMatchesFormat('%simprobabledir999999999999', $result->getMessage());

        chmod($dir1, 0777);
        chmod($dir2, 0777);
        rmdir($dir1);
        rmdir($dir2);
    }

    public function testPhpVersionInvalidVersion()
    {
        $this->setExpectedException('InvalidArgumentException');
        new PhpVersion(new \stdClass());
    }

    public function testPhpVersionInvalidVersion2()
    {
        $this->setExpectedException('InvalidArgumentException');
        new PhpVersion(fopen('php://memory', 'r'));
    }

    public function testPhpVersionInvalidOperator()
    {
        $this->setExpectedException('InvalidArgumentException');
        new PhpVersion('1.0.0', array());
    }

    public function testPhpVersionInvalidOperator2()
    {
        $this->setExpectedException('InvalidArgumentException');
        new PhpVersion('1.0.0', 'like');
    }

    public function testClassExistsInvalidArgument()
    {
        $this->setExpectedException('InvalidArgumentException');
        new ClassExists(new \stdClass);
    }

    public function testClassExistsInvalidArgument2()
    {
        $this->setExpectedException('InvalidArgumentException');
        new ClassExists(15);
    }

    public function testExtensionLoadedInvalidArgument()
    {
        $this->setExpectedException('InvalidArgumentException');
        new ExtensionLoaded(new \stdClass);
    }

    public function testExtensionLoadedInvalidArgument2()
    {
        $this->setExpectedException('InvalidArgumentException');
        new ExtensionLoaded(15);
    }

    public function testDirReadableInvalidArgument()
    {
        $this->setExpectedException('InvalidArgumentException');
        new DirReadable(new \stdClass);
    }

    public function testDirReadableInvalidArgument2()
    {
        $this->setExpectedException('InvalidArgumentException');
        new DirReadable(15);
    }

    public function testDirWritableInvalidArgument()
    {
        $this->setExpectedException('InvalidArgumentException');
        new DirWritable(new \stdClass);
    }

    public function testDirWritableInvalidArgument2()
    {
        $this->setExpectedException('InvalidArgumentException');
        new DirWritable(15);
    }

    public function testStreamWrapperInvalidArgument()
    {
        $this->setExpectedException('InvalidArgumentException');
        new StreamWrapperExists(new \stdClass);
    }

    public function testStreamWrapperInvalidInvalidArgument2()
    {
        $this->setExpectedException('InvalidArgumentException');
        new StreamWrapperExists(15);
    }

    public function testCallbackInvalidArgument()
    {
        $this->setExpectedException('InvalidArgumentException');
        new Callback(15);
    }

    public function testCallbackInvalidArgument2()
    {
        $this->setExpectedException('InvalidArgumentException');
        new Callback(array($this, 'foobarbar'));
    }

    public function testCpuPerformanceInvalidArgument()
    {
        $this->setExpectedException('InvalidArgumentException');
        new CpuPerformance(-1);
    }

}

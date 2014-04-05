<?php
namespace ZendDiagnosticsTest;

use ArrayObject;
use Exception;
use SensioLabs\Security\SecurityChecker;
use ZendDiagnostics\Check\Callback;
use ZendDiagnostics\Check\ClassExists;
use ZendDiagnostics\Check\CpuPerformance;
use ZendDiagnostics\Check\DirReadable;
use ZendDiagnostics\Check\DirWritable;
use ZendDiagnostics\Check\ExtensionLoaded;
use ZendDiagnostics\Check\IniFile;
use ZendDiagnostics\Check\JsonFile;
use ZendDiagnostics\Check\PhpFlag;
use ZendDiagnostics\Check\PhpVersion;
use ZendDiagnostics\Check\ProcessRunning;
use ZendDiagnostics\Check\RabbitMQ;
use ZendDiagnostics\Check\Redis;
use ZendDiagnostics\Check\StreamWrapperExists;
use ZendDiagnostics\Check\XmlFile;
use ZendDiagnostics\Check\YamlFile;
use ZendDiagnostics\Result\Success;
use ZendDiagnosticsTest\TestAsset\Check\AlwaysSuccess;
use ZendDiagnosticsTest\TestAsset\Check\SecurityAdvisory;

class ChecksTest extends \PHPUnit_Framework_TestCase
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

    public function testRabbitMQ()
    {
        $check = new RabbitMQ();
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $result);

        $check = new RabbitMQ('127.0.0.250', 9999);
        $this->setExpectedException('PhpAmqpLib\Exception\AMQPRuntimeException');
        $check->check();
    }

    public function testRedis()
    {
        $check = new Redis();
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $result);

        $check = new Redis('127.0.0.250', 9999);
        $this->setExpectedException('Predis\Connection\ConnectionException');
        $check->check();
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

    public function testPhpFlag()
    {
        // Retrieve a set of settings to test against
        $all = ini_get_all();

        foreach($all as $name => $valueArray) {
            if($valueArray['local_value'] == '0') {
                break;
            }
        }
        $check = new PhpFlag($name, false);
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $check->check());

        $check = new PhpFlag($name, true);
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $check->check());


        $allFalse = array();
        foreach($all as $name => $valueArray) {
            if($valueArray['local_value'] == '0') {
                $allFalse[] = $name;
            }

            if(count($allFalse) == 3) {
                break;
            }
        }

        $check = new PhpFlag($allFalse, false);
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $check->check());

        $check = new PhpFlag($allFalse, true);
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $result = $check->check());
        $this->assertStringMatchesFormat('%A' . join(', ', $allFalse) . '%Aenabled%A', $result->getMessage());

        $allFalse = new ArrayObject($allFalse);
        $check = new PhpFlag($allFalse, false);
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $check->check());

        $check = new PhpFlag($allFalse, true);
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $check->check());


        $notAllFalse = $allFalse;
        foreach($all as $name => $valueArray) {
            if($valueArray['local_value'] == '1') {
                $notAllFalse[] = $name;
                break;
            }
        }

        $check = new PhpFlag($notAllFalse, false);
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $result = $check->check());
        $this->assertStringMatchesFormat("%A$name%A", $result->getMessage());

        $check = new PhpFlag($notAllFalse, true);
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
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $result);
        $this->assertStringMatchesFormat('%simprobableName9999999999999999999999%s', $result->getMessage());

        $wrappers = array(
            'improbableName9999999999999999999999',
            'improbableName0000000000000000000000',
        );

        $check = new StreamWrapperExists($wrappers);
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $result);
        $this->assertStringMatchesFormat('%simprobableName9999999999999999999999%s', $result->getMessage());
        $this->assertStringMatchesFormat('%simprobableName0000000000000000000000', $result->getMessage());

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

    public function testProcessRunning()
    {
        if (!$phpPid = @getmypid()) {
            $this->markTestSkipped('Unable to retrieve PHP process\' PID');
        }

        $check = new ProcessRunning($phpPid);
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $result);

        $check = new ProcessRunning(32768);
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $result);
        $this->assertStringMatchesFormat('%sPID 32768%s', $result->getMessage());

        // try to retrieve full PHP process command string
        $phpCommand = shell_exec('ps -o command= -p ' . $phpPid);
        if (!$phpCommand || strlen($phpCommand) < 4) {
            $this->markTestSkipped('Unable to retrieve PHP process command name.');
        }

        $check = new ProcessRunning(substr($phpCommand, 0, ceil(strlen($phpPid) / 2)));
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Success', $result);

        $check = new ProcessRunning('improbable process name 9999999999999999');
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $result);
        $this->assertStringMatchesFormat('%simprobable process name 9999999999999999%s', $result->getMessage());
    }

    public function testSecurityAdvisory()
    {
        if (!class_exists('SensioLabs\Security\SecurityChecker')) {
            $this->markTestSkipped(
                'Unable to find SensioLabs\Security\SecurityChecker class - probably missing ' .
                'sensiolabs/security-checker package. Have you installed all dependencies, ' .
                'including those specified require-dev in composer.json?'
            );
        }

        $secureComposerLock = __DIR__ . '/TestAsset/secure-composer.lock';
        $check = new SecurityAdvisory($secureComposerLock);
        $result = $check->check();
        $this->assertNotInstanceOf('ZendDiagnostics\Result\Failure', $result);

        // check against non-existent lock file
        $check = new SecurityAdvisory(__DIR__ . '/improbable-lock-file-99999999999.lock');
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $result);

        // check against unreadable lock file
        $tmpDir = sys_get_temp_dir();
        if (!is_dir($tmpDir) || !is_writable($tmpDir)) {
            $this->markTestSkipped('Cannot access writable system temp dir to perform the test... ');
            return;
        }
        $unreadableFile = $tmpDir . '/composer.' . uniqid('', true) . '.lock';
        if (!file_put_contents($unreadableFile, 'foo') || !chmod($unreadableFile, 0000)) {
            $this->markTestSkipped('Cannot create temporary file in system temp dir to perform the test... ');
            return;
        }

        $check = new SecurityAdvisory($unreadableFile);
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $result);

        // cleanup
        chmod($unreadableFile, 0666);
        unlink($unreadableFile);
    }

    /**
     * @depends testSecurityAdvisory
     */
    public function testSecurityAdvisoryFailure()
    {
        $secureComposerLock = __DIR__ . '/TestAsset/secure-composer.lock';
        $checker = $this->getMock('SensioLabs\Security\SecurityChecker');
        $checker->expects($this->once())
            ->method('check')
            ->with($this->equalTo($secureComposerLock))
            ->will($this->returnValue('[{"a":1},{"b":2},{"c":3}]'));

        $check = new SecurityAdvisory($secureComposerLock);
        $check->setSecurityChecker($checker);
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Failure', $result);
    }

    /**
     * @depends testSecurityAdvisory
     */
    public function testSecurityAdvisoryInvalidServerResponse()
    {
        $secureComposerLock = __DIR__ . '/TestAsset/secure-composer.lock';
        $checker = $this->getMock('SensioLabs\Security\SecurityChecker');
        $checker->expects($this->once())
            ->method('check')
            ->with($this->equalTo($secureComposerLock))
            ->will($this->returnValue('404 error'));
        $check = new SecurityAdvisory($secureComposerLock);
        $check->setSecurityChecker($checker);
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Warning', $result);

    }
    /**
     * @depends testSecurityAdvisory
     */
    public function testSecurityAdvisoryCheckerException()
    {
        $secureComposerLock = __DIR__ . '/TestAsset/secure-composer.lock';
        $checker = $this->getMock('SensioLabs\Security\SecurityChecker');
        $checker->expects($this->once())
            ->method('check')
            ->with($this->equalTo($secureComposerLock))
            ->will($this->throwException(new Exception));
        $check = new SecurityAdvisory($secureComposerLock);
        $check->setSecurityChecker($checker);
        $result = $check->check();
        $this->assertInstanceOf('ZendDiagnostics\Result\Warning', $result);
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

    public function testProcessRunningInvalidArgument()
    {
        $this->setExpectedException('InvalidArgumentException');
        new ProcessRunning(new \stdClass());
    }

    public function testProcessRunningInvalidArgument2()
    {
        $this->setExpectedException('InvalidArgumentException');
        new ProcessRunning(-100);
    }

    public function testProcessRunningInvalidArgument3()
    {
        $this->setExpectedException('InvalidArgumentException');
        new ProcessRunning('');
    }

    /**
     * @depends testSecurityAdvisory
     */
    public function testSecurityAdvisoryInvalidArgument1()
    {
        $this->setExpectedException('InvalidArgumentException');
        new SecurityAdvisory($this->getMock('SensioLabs\Security\SecurityChecker'), new \stdClass());
    }

    public function testAbstractFileCheckArgument1()
    {
        $temp = tmpfile();
        fwrite($temp, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<foo>1</foo>");
        $meta = stream_get_meta_data($temp);
        $path = $meta['uri'];

        // single string
        $check = new XmlFile($path);
        $this->assertInstanceOf('ZendDiagnostics\Result\SuccessInterface', $check->check());

        // array
        $check = new XmlFile(array($path, $path, $path));
        $this->assertInstanceOf('ZendDiagnostics\Result\SuccessInterface', $check->check());

        // object inplementing \Traversable
        $check = new XmlFile(new ArrayObject(array($path, $path, $path)));
        $this->assertInstanceOf('ZendDiagnostics\Result\SuccessInterface', $check->check());

        fclose($temp);
    }

    public function testAbstractFileCheckInvalidArgument1()
    {
        // int
        try {
            $check = new XmlFile(2);
            $this->fail('InvalidArguementException should be thrown here!');
        } catch(Exception $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e);
        }

        // bool
        try {
            $check = new XmlFile(true);
            $this->fail('InvalidArguementException should be thrown here!');
        } catch(Exception $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e);
        }

        // object not implementing \Traversable
        try {
            $check = new XmlFile(new \stdClass());
            $this->fail('InvalidArguementException should be thrown here!');
        } catch(Exception $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e);
        }
    }

    public function testXmlFileValid()
    {
        $temp = tmpfile();
        fwrite($temp, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<foo>1</foo>");
        $meta = stream_get_meta_data($temp);
        $path = $meta['uri'];

        $check = new XmlFile($path);
        $this->assertInstanceOf('ZendDiagnostics\Result\SuccessInterface', $check->check());

        fclose($temp);
    }

    public function testXmlFileInvalid()
    {
        $temp = tmpfile();
        fwrite($temp, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<foo>1</bar>");
        $meta = stream_get_meta_data($temp);
        $path = $meta['uri'];

        $check = new XmlFile($path);
        $this->assertInstanceOf('ZendDiagnostics\Result\FailureInterface', $check->check());

        fclose($temp);
    }

    public function testXmlFileNotPresent()
    {
        $check = new XmlFile('/does/not/exist');
        $this->assertInstanceOf('ZendDiagnostics\Result\FailureInterface', $check->check());
    }

    public function testIniFileValid()
    {
        $temp = tmpfile();
        fwrite($temp, "[first_group]\nfoo = 1\nbar = 5");
        $meta = stream_get_meta_data($temp);
        $path = $meta['uri'];

        $check = new IniFile($path);
        $this->assertInstanceOf('ZendDiagnostics\Result\SuccessInterface', $check->check());

        fclose($temp);
    }

    public function testIniFileInvalid()
    {
        $temp = tmpfile();
        fwrite($temp, "]]]]]]");
        $meta = stream_get_meta_data($temp);
        $path = $meta['uri'];

        $check = new IniFile($path);
        $this->assertInstanceOf('ZendDiagnostics\Result\FailureInterface', $check->check());

        fclose($temp);
    }

    public function testIniFileNotPresent()
    {
        $check = new IniFile('/does/not/exist');
        $this->assertInstanceOf('ZendDiagnostics\Result\FailureInterface', $check->check());
    }

    public function testYamlFileValid()
    {
        $temp = tmpfile();
        fwrite($temp, "foo: 1\nbar: 1");
        $meta = stream_get_meta_data($temp);
        $path = $meta['uri'];

        $check = new YamlFile($path);
        $this->assertInstanceOf('ZendDiagnostics\Result\SuccessInterface', $check->check());

        fclose($temp);
    }

    public function testYamlFileInvalid()
    {
        $temp = tmpfile();
        fwrite($temp, "foo: 1\n\tbar: 3");
        $meta = stream_get_meta_data($temp);
        $path = $meta['uri'];

        $check = new YamlFile($path);
        $this->assertInstanceOf('ZendDiagnostics\Result\FailureInterface', $check->check());

        fclose($temp);
    }

    public function testYamlFileNotPresent()
    {
        $check = new YamlFile('/does/not/exist');
        $this->assertInstanceOf('ZendDiagnostics\Result\FailureInterface', $check->check());
    }

    public function testJsonFileValid()
    {
        $temp = tmpfile();
        fwrite($temp, '{ "foo": "bar"}');
        $meta = stream_get_meta_data($temp);
        $path = $meta['uri'];

        $check = new JsonFile($path);
        $this->assertInstanceOf('ZendDiagnostics\Result\SuccessInterface', $check->check());

        fclose($temp);
    }

    public function testJsonFileInvalid()
    {
        $temp = tmpfile();
        fwrite($temp, '{ foo: {"bar"');
        $meta = stream_get_meta_data($temp);
        $path = $meta['uri'];

        $check = new JsonFile($path);
        $this->assertInstanceOf('ZendDiagnostics\Result\FailureInterface', $check->check());

        fclose($temp);
    }

    public function testJsonFileNotPresent()
    {
        $check = new JsonFile('/does/not/exist');
        $this->assertInstanceOf('ZendDiagnostics\Result\FailureInterface', $check->check());
    }
}

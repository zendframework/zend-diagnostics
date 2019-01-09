<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2013-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

namespace ZendDiagnosticsTest;

use ArrayObject;
use ErrorException;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SensioLabs\Security\Result;
use SensioLabs\Security\SecurityChecker;
use stdClass;
use ZendDiagnostics\Check\Callback;
use ZendDiagnostics\Check\ClassExists;
use ZendDiagnostics\Check\CpuPerformance;
use ZendDiagnostics\Check\DirReadable;
use ZendDiagnostics\Check\DirWritable;
use ZendDiagnostics\Check\ExtensionLoaded;
use ZendDiagnostics\Check\IniFile;
use ZendDiagnostics\Check\JsonFile;
use ZendDiagnostics\Check\Memcache;
use ZendDiagnostics\Check\Memcached;
use ZendDiagnostics\Check\Mongo;
use ZendDiagnostics\Check\PhpFlag;
use ZendDiagnostics\Check\PhpVersion;
use ZendDiagnostics\Check\ProcessRunning;
use ZendDiagnostics\Check\RabbitMQ;
use ZendDiagnostics\Check\Redis;
use ZendDiagnostics\Check\StreamWrapperExists;
use ZendDiagnostics\Check\XmlFile;
use ZendDiagnostics\Check\YamlFile;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\FailureInterface;
use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Result\SuccessInterface;
use ZendDiagnostics\Result\Warning;
use ZendDiagnosticsTest\TestAsset\Check\AlwaysSuccess;
use ZendDiagnosticsTest\TestAsset\Check\SecurityAdvisory;

class ChecksTest extends TestCase
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
        $this->assertInstanceOf(Success::class, $result);

        $check = new CpuPerformance(999999999); // improbable to archive
        $result = $check->check();
        $this->assertInstanceOf(Failure::class, $result);
    }

    public function testRabbitMQ()
    {
        if (getenv('TESTS_ZEND_DIAGNOSTICS_RABBITMQ_ENABLED') !== 'true') {
            $this->markTestSkipped('RabbitMQ tests are not enabled; enable them in phpunit.xml');
        }

        $check = new RabbitMQ();
        $result = $check->check();
        $this->assertInstanceOf(Success::class, $result);

        $check = new RabbitMQ('127.0.0.250', 9999);
        // Exception type varies between different versions of php-amqplib;
        // sometimes it is a descendent of ErrorException, sometimes
        // RuntimeException. As such, catching any exception here.
        $this->expectException(Exception::class);
        $check->check();
    }

    public function testRedis()
    {
        if (getenv('TESTS_ZEND_DIAGNOSTICS_REDIS_ENABLED') !== 'true') {
            $this->markTestSkipped('Redis tests are not enabled; enable them in phpunit.xml');
        }

        $check = new Redis();
        $result = $check->check();
        $this->assertInstanceOf(Success::class, $result);

        $check = new Redis('127.0.0.250', 9999);
        $this->expectException(Exception::class);
        $check->check();
    }

    public function testMemcache()
    {
        if (getenv('TESTS_ZEND_DIAGNOSTICS_MEMCACHE_ENABLED') !== 'true') {
            $this->markTestSkipped('Memcache tests are not enabled; enable them in phpunit.xml');
        }

        $check = new Memcache();
        $result = $check->check();
        $this->assertInstanceOf(Success::class, $result);

        $check = new Memcache('127.0.0.250', 9999);
        $result = $check->check();
        $this->assertInstanceOf(Failure::class, $result);
    }

    public function testMemcached()
    {
        if (getenv('TESTS_ZEND_DIAGNOSTICS_MEMCACHED_ENABLED') !== 'true') {
            $this->markTestSkipped('Memcached tests are not enabled; enable them in phpunit.xml');
        }

        $check = new Memcached();
        $result = $check->check();
        $this->assertInstanceOf(Success::class, $result);

        $check = new Memcached('127.0.0.250', 9999);
        $result = $check->check();
        $this->assertInstanceOf(Failure::class, $result);
    }

    public function testMongo()
    {
        if (getenv('TESTS_ZEND_DIAGNOSTICS_MONGO_ENABLED') !== 'true') {
            $this->markTestSkipped('Mongo tests are not enabled; enable them in phpunit.xml');
        }

        $check = new Mongo();
        $result = $check->check();
        $this->assertInstanceOf(Success::class, $result);

        $check = new Memcached('127.0.0.250', 9999);
        $result = $check->check();
        $this->assertInstanceOf(Failure::class, $result);
    }

    public function testClassExists()
    {
        $check = new ClassExists(__CLASS__);
        $this->assertInstanceOf(Success::class, $check->check());

        $check = new ClassExists('improbableClassNameInGlobalNamespace999999999999999999');
        $this->assertInstanceOf(Failure::class, $check->check());

        $check = new ClassExists([
            __CLASS__,
            Success::class,
            Failure::class,
            Warning::class,
        ]);
        $this->assertInstanceOf(Success::class, $check->check());

        $check = new ClassExists([
            __CLASS__,
            Success::class,
            'improbableClassNameInGlobalNamespace999999999999999999',
            Failure::class,
            Warning::class,
        ]);
        $this->assertInstanceOf(Failure::class, $check->check());
    }

    public function testClassExistsExplanation()
    {
        $check = new ClassExists([
            __CLASS__,
            Success::class,
            'improbableClassNameInGlobalNamespace888',
            'improbableClassNameInGlobalNamespace999',
            Failure::class,
            Warning::class,
        ]);
        $result = $check->check();
        $this->assertInstanceOf(Failure::class, $result);
        $this->assertStringMatchesFormat('%simprobableClassNameInGlobalNamespace888%s', $result->getMessage());
        $this->assertStringMatchesFormat('%simprobableClassNameInGlobalNamespace999', $result->getMessage());
    }

    public function testPhpVersion()
    {
        $check = new PhpVersion(PHP_VERSION); // default operator
        $this->assertInstanceOf(Success::class, $check->check());

        $check = new PhpVersion(PHP_VERSION, '='); // explicit equal
        $this->assertInstanceOf(Success::class, $check->check());

        $check = new PhpVersion(PHP_VERSION, '<'); // explicit less than
        $this->assertInstanceOf(Failure::class, $check->check());
    }

    public function testPhpVersionArray()
    {
        $check = new PhpVersion([PHP_VERSION]); // default operator
        $this->assertInstanceOf(Success::class, $check->check());

        $check = new PhpVersion([
            '1.0.0',
            '1.1.0',
            '1.1.1',
        ], '<'); // explicit less than
        $this->assertInstanceOf(Failure::class, $check->check());

        $check = new PhpVersion(new ArrayObject([
            '40.0.0',
            '41.0.0',
            '42.0.0',
        ]), '<'); // explicit less than
        $this->assertInstanceOf(Success::class, $check->check());

        $check = new PhpVersion(new ArrayObject([
            '41.0.0',
            PHP_VERSION,
        ]), '!='); // explicit less than
        $this->assertInstanceOf(Failure::class, $check->check());
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
        $this->assertInstanceOf(Success::class, $check->check());

        $check = new ExtensionLoaded('improbableExtName999999999999999999');
        $this->assertInstanceOf(Failure::class, $check->check());

        $extensions = [];
        foreach (array_rand($allExtensions, 3) as $key) {
            $extensions[] = $allExtensions[$key];
        }

        $check = new ExtensionLoaded($extensions);
        $this->assertInstanceOf(Success::class, $check->check());

        $extensions[] = 'improbableExtName9999999999999999999999';

        $check = new ExtensionLoaded($extensions);
        $this->assertInstanceOf(Failure::class, $check->check());

        $extensions = [
            'improbableExtName9999999999999999999999',
            'improbableExtName0000000000000000000000',
        ];

        $check = new ExtensionLoaded($extensions);
        $this->assertInstanceOf(Failure::class, $check->check());
    }

    public function testPhpFlag()
    {
        // Retrieve a set of settings to test against
        $all = ini_get_all();

        foreach ($all as $name => $valueArray) {
            if ($valueArray['local_value'] == '0') {
                break;
            }
        }
        $check = new PhpFlag($name, false);
        $this->assertInstanceOf(Success::class, $check->check());

        $check = new PhpFlag($name, true);
        $this->assertInstanceOf(Failure::class, $check->check());


        $allFalse = [];
        foreach ($all as $name => $valueArray) {
            if ($valueArray['local_value'] == '0') {
                $allFalse[] = $name;
            }

            if (count($allFalse) == 3) {
                break;
            }
        }

        $check = new PhpFlag($allFalse, false);
        $this->assertInstanceOf(Success::class, $check->check());

        $check = new PhpFlag($allFalse, true);
        $this->assertInstanceOf(Failure::class, $result = $check->check());
        $this->assertStringMatchesFormat('%A' . join(', ', $allFalse) . '%Aenabled%A', $result->getMessage());

        $allFalse = new ArrayObject($allFalse);
        $check = new PhpFlag($allFalse, false);
        $this->assertInstanceOf(Success::class, $check->check());

        $check = new PhpFlag($allFalse, true);
        $this->assertInstanceOf(Failure::class, $check->check());

        $notAllFalse = $allFalse;
        foreach ($all as $name => $valueArray) {
            if ($valueArray['local_value'] == '1') {
                $notAllFalse[] = $name;
                break;
            }
        }

        $check = new PhpFlag($notAllFalse, false);
        $this->assertInstanceOf(Failure::class, $result = $check->check());
        $this->assertStringMatchesFormat("%A$name%A", $result->getMessage());

        $check = new PhpFlag($notAllFalse, true);
        $this->assertInstanceOf(Failure::class, $check->check());
    }

    public function testStreamWrapperExists()
    {
        $allWrappers = stream_get_wrappers();
        $wrapper = $allWrappers[array_rand($allWrappers)];

        $check = new StreamWrapperExists($wrapper);
        $this->assertInstanceOf(Success::class, $check->check());

        $check = new StreamWrapperExists('improbableName999999999999999999');
        $this->assertInstanceOf(Failure::class, $check->check());

        $wrappers = [];
        foreach (array_rand($allWrappers, 3) as $key) {
            $wrappers[] = $allWrappers[$key];
        }

        $check = new StreamWrapperExists($wrappers);
        $this->assertInstanceOf(Success::class, $check->check());

        $wrappers[] = 'improbableName9999999999999999999999';

        $check = new StreamWrapperExists($wrappers);
        $result = $check->check();
        $this->assertInstanceOf(Failure::class, $result);
        $this->assertStringMatchesFormat('%simprobableName9999999999999999999999%s', $result->getMessage());

        $wrappers = [
            'improbableName9999999999999999999999',
            'improbableName0000000000000000000000',
        ];

        $check = new StreamWrapperExists($wrappers);
        $result = $check->check();
        $this->assertInstanceOf(Failure::class, $result);
        $this->assertStringMatchesFormat('%simprobableName9999999999999999999999%s', $result->getMessage());
        $this->assertStringMatchesFormat('%simprobableName0000000000000000000000', $result->getMessage());
    }

    public function testDirReadable()
    {
        $check = new DirReadable(__DIR__);
        $result = $check->check();
        $this->assertInstanceOf(Success::class, $result);

        $check = new DirReadable([
            __DIR__,
            __DIR__ . '/../'
        ]);
        $result = $check->check();
        $this->assertInstanceOf(Success::class, $result, 'Array of valid dirs');

        $check = new DirReadable(__FILE__);
        $result = $check->check();
        $this->assertInstanceOf(Failure::class, $result, 'An existing file');

        $check = new DirReadable(__DIR__ . '/improbabledir99999999999999999999999999999999999999');
        $result = $check->check();
        $this->assertInstanceOf(Failure::class, $result, 'Single non-existent dir');

        $check = new DirReadable(__DIR__ . '/improbabledir999999999999');
        $result = $check->check();
        $this->assertInstanceOf(Failure::class, $result);
        $this->assertStringMatchesFormat('%simprobabledir999999999999%s', $result->getMessage());

        $check = new DirReadable([
            __DIR__ . '/improbabledir888888888888',
            __DIR__ . '/improbabledir999999999999',
        ]);
        $result = $check->check();
        $this->assertInstanceOf(Failure::class, $result);
        $this->assertStringMatchesFormat('%simprobabledir888888888888%s', $result->getMessage());
        $this->assertStringMatchesFormat('%simprobabledir999999999999', $result->getMessage());

        // create a barrage of unreadable directories
        $tmpDir = sys_get_temp_dir();
        if (! is_dir($tmpDir) || ! is_writable($tmpDir)) {
            $this->markTestSkipped('Cannot access writable system temp dir to perform the test... ');

            return;
        }

        // generate a random dir name
        while (($dir1 = $tmpDir . '/test' . mt_rand(1, PHP_INT_MAX)) && file_exists($dir1)) {
        }
        while (($dir2 = $tmpDir . '/test' . mt_rand(1, PHP_INT_MAX)) && file_exists($dir2)) {
        }

        // create temporary unreadable directories
        if (! mkdir($dir1) || ! chmod($dir1, 0000) ||
            ! mkdir($dir2) || ! chmod($dir2, 0000)
        ) {
            $this->markTestSkipped('Cannot create unreadable temporary directory to perform the test... ');

            return;
        }

        // run the check
        $check = new DirReadable([
            $dir1, // unreadable
            $dir2, // unreadable
            $tmpDir, // valid one
            __DIR__ . '/simprobabledir999999999999', // non existent
        ]);
        $result = $check->check();
        $this->assertInstanceOf(Failure::class, $result);
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
        $this->assertInstanceOf(Failure::class, $result, 'Non-existent path');
        $this->assertStringMatchesFormat($path . '%s', $result->getMessage());

        // non-dir path
        $path = __FILE__;
        $check = new DirWritable($path);
        $result = $check->check();
        $this->assertInstanceOf(Failure::class, $result, 'Non-dir path');
        $this->assertStringMatchesFormat($path . '%s', $result->getMessage());

        // multiple non-dir paths
        $path1 = __FILE__;
        $path2 = __DIR__ . '/BasicClassesTest.php';
        $check = new DirWritable([$path1, $path2]);
        $result = $check->check();
        $this->assertInstanceOf(Failure::class, $result, 'Non-dir path');
        $this->assertStringMatchesFormat('%s' . $path1 . '%s', $result->getMessage());
        $this->assertStringMatchesFormat('%s' . $path2 . '%s', $result->getMessage());

        // create a barrage of unwritable directories
        $tmpDir = sys_get_temp_dir();
        if (! is_dir($tmpDir) || ! is_writable($tmpDir)) {
            $this->markTestSkipped('Cannot access writable system temp dir to perform the test... ');

            return;
        }

        // this should succeed
        $check = new DirWritable($tmpDir);
        $result = $check->check();
        $this->assertInstanceOf(Success::class, $result, 'Single writable dir');

        // generate a random dir name
        while (($dir1 = $tmpDir . '/test' . mt_rand(1, PHP_INT_MAX)) && file_exists($dir1)) {
        }
        while (($dir2 = $tmpDir . '/test' . mt_rand(1, PHP_INT_MAX)) && file_exists($dir2)) {
        }

        // create temporary writable directories
        if (! mkdir($dir1) || ! mkdir($dir2)) {
            $this->markTestSkipped('Cannot create unreadable temporary directory to perform the test... ');

            return;
        }

        // we should now have 3 writable directories
        $check = new DirWritable([
            $tmpDir,
            $dir1,
            $dir2,
        ]);
        $result = $check->check();
        $this->assertInstanceOf(Success::class, $result, 'Multiple writable dirs');

        // make temporary dirs unwritable
        if (! chmod($dir1, 0000) || ! chmod($dir2, 0000)) {
            $this->markTestSkipped('Cannot create unreadable temporary directory to perform the test... ');

            return;
        }

        // single unwritable dir
        $check = new DirWritable($dir1);
        $result = $check->check();
        $this->assertInstanceOf(Failure::class, $result);
        $this->assertStringMatchesFormat($dir1 . '%s', $result->getMessage());

        // this should now fail
        $check = new DirWritable([
            $dir1, // unwritable
            $dir2, // unwritable
            $tmpDir, // valid one
            __DIR__ . '/simprobabledir999999999999', // non existent
        ]);
        $result = $check->check();
        $this->assertInstanceOf(Failure::class, $result);
        $this->assertStringMatchesFormat('%s' . $dir1 . '%s', $result->getMessage());
        $this->assertStringMatchesFormat('%s' . $dir2 . '%s', $result->getMessage());
        $this->assertStringMatchesFormat('%simprobabledir999999999999%s', $result->getMessage());

        chmod($dir1, 0777);
        chmod($dir2, 0777);
        rmdir($dir1);
        rmdir($dir2);
    }

    public function testProcessRunning()
    {
        if (! $phpPid = @getmypid()) {
            $this->markTestSkipped('Unable to retrieve PHP process\' PID');
        }

        $check = new ProcessRunning($phpPid);
        $result = $check->check();
        $this->assertInstanceOf(Success::class, $result);

        $check = new ProcessRunning(32768);
        $result = $check->check();
        $this->assertInstanceOf(Failure::class, $result);
        $this->assertStringMatchesFormat('%sPID 32768%s', $result->getMessage());

        // try to retrieve full PHP process command string
        $phpCommand = shell_exec('ps -o command= -p ' . $phpPid);
        if (! $phpCommand || strlen($phpCommand) < 4) {
            $this->markTestSkipped('Unable to retrieve PHP process command name.');
        }

        $check = new ProcessRunning(substr($phpCommand, 0, ceil(strlen($phpPid) / 2)));
        $result = $check->check();
        $this->assertInstanceOf(Success::class, $result);

        $check = new ProcessRunning('improbable process name 9999999999999999');
        $result = $check->check();
        $this->assertInstanceOf(Failure::class, $result);
        $this->assertStringMatchesFormat('%simprobable process name 9999999999999999%s', $result->getMessage());
    }

    public function testSecurityAdvisory()
    {
        if (! class_exists(SecurityChecker::class)) {
            $this->markTestSkipped(
                'Unable to find SensioLabs\Security\SecurityChecker class - probably missing ' .
                'sensiolabs/security-checker package. Have you installed all dependencies, ' .
                'including those specified require-dev in composer.json?'
            );
        }

        $secureComposerLock = __DIR__ . '/TestAsset/secure-composer.lock';
        $check = new SecurityAdvisory($secureComposerLock);
        $result = $check->check();
        $this->assertNotInstanceOf(Failure::class, $result);

        // check against non-existent lock file
        $check = new SecurityAdvisory(__DIR__ . '/improbable-lock-file-99999999999.lock');
        $result = $check->check();
        $this->assertInstanceOf(Failure::class, $result);

        // check against unreadable lock file
        $tmpDir = sys_get_temp_dir();
        if (! is_dir($tmpDir) || ! is_writable($tmpDir)) {
            $this->markTestSkipped('Cannot access writable system temp dir to perform the test... ');
            return;
        }
        $unreadableFile = $tmpDir . '/composer.' . uniqid('', true) . '.lock';
        if (! file_put_contents($unreadableFile, 'foo') || ! chmod($unreadableFile, 0000)) {
            $this->markTestSkipped('Cannot create temporary file in system temp dir to perform the test... ');
            return;
        }

        $check = new SecurityAdvisory($unreadableFile);
        $result = $check->check();
        $this->assertInstanceOf(Failure::class, $result);

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
        $checker = $this->createMock(SecurityChecker::class);
        $checker->expects($this->once())
            ->method('check')
            ->with($this->equalTo($secureComposerLock))
            ->will($this->returnValue(new Result(3, '[{"a":1},{"b":2},{"c":3}]', 'json')));

        $check = new SecurityAdvisory($secureComposerLock);
        $check->setSecurityChecker($checker);
        $result = $check->check();
        $this->assertInstanceOf(Failure::class, $result);
        $this->assertSame('Found security advisories for 3 composer package(s)', $result->getMessage());
    }

    /**
     * @depends testSecurityAdvisory
     */
    public function testSecurityAdvisoryInvalidServerResponse()
    {
        $secureComposerLock = __DIR__ . '/TestAsset/secure-composer.lock';
        $checker = $this->createMock(SecurityChecker::class);
        $checker->expects($this->once())
            ->method('check')
            ->with($this->equalTo($secureComposerLock))
            ->will($this->returnValue('404 error'));
        $check = new SecurityAdvisory($secureComposerLock);
        $check->setSecurityChecker($checker);
        $result = $check->check();
        $this->assertInstanceOf(Warning::class, $result);
    }
    /**
     * @depends testSecurityAdvisory
     */
    public function testSecurityAdvisoryCheckerException()
    {
        $secureComposerLock = __DIR__ . '/TestAsset/secure-composer.lock';
        $checker = $this->createMock(SecurityChecker::class);
        $checker->expects($this->once())
            ->method('check')
            ->with($this->equalTo($secureComposerLock))
            ->will($this->throwException(new Exception));
        $check = new SecurityAdvisory($secureComposerLock);
        $check->setSecurityChecker($checker);
        $result = $check->check();
        $this->assertInstanceOf(Warning::class, $result);
    }

    /**
     * @depends testSecurityAdvisory
     */
    public function testSecurityAdvisoryCheckerSuccess()
    {
        $secureComposerLock = __DIR__ . '/TestAsset/secure-composer.lock';
        $checker = $this->createMock(SecurityChecker::class);
        $checker->expects($this->once())
            ->method('check')
            ->with($this->equalTo($secureComposerLock))
            ->will($this->returnValue(new Result(0, '[]', 'json')));
        $check = new SecurityAdvisory($secureComposerLock);
        $check->setSecurityChecker($checker);
        $result = $check->check();
        $this->assertInstanceOf(Success::class, $result);
    }

    public function testPhpVersionInvalidVersion()
    {
        $this->expectException(InvalidArgumentException::class);
        new PhpVersion(new stdClass());
    }

    public function testPhpVersionInvalidVersion2()
    {
        $this->expectException(InvalidArgumentException::class);
        new PhpVersion(fopen('php://memory', 'r'));
    }

    public function testPhpVersionInvalidOperator()
    {
        $this->expectException(InvalidArgumentException::class);
        new PhpVersion('1.0.0', []);
    }

    public function testPhpVersionInvalidOperator2()
    {
        $this->expectException(InvalidArgumentException::class);
        new PhpVersion('1.0.0', 'like');
    }

    public function testClassExistsInvalidArgument()
    {
        $this->expectException(InvalidArgumentException::class);
        new ClassExists(new stdClass);
    }

    public function testClassExistsInvalidArgument2()
    {
        $this->expectException(InvalidArgumentException::class);
        new ClassExists(15);
    }

    public function testExtensionLoadedInvalidArgument()
    {
        $this->expectException(InvalidArgumentException::class);
        new ExtensionLoaded(new stdClass);
    }

    public function testExtensionLoadedInvalidArgument2()
    {
        $this->expectException(InvalidArgumentException::class);
        new ExtensionLoaded(15);
    }

    public function testDirReadableInvalidArgument()
    {
        $this->expectException(InvalidArgumentException::class);
        new DirReadable(new stdClass);
    }

    public function testDirReadableInvalidArgument2()
    {
        $this->expectException(InvalidArgumentException::class);
        new DirReadable(15);
    }

    public function testDirWritableInvalidArgument()
    {
        $this->expectException(InvalidArgumentException::class);
        new DirWritable(new stdClass);
    }

    public function testDirWritableInvalidArgument2()
    {
        $this->expectException(InvalidArgumentException::class);
        new DirWritable(15);
    }

    public function testStreamWrapperInvalidArgument()
    {
        $this->expectException(InvalidArgumentException::class);
        new StreamWrapperExists(new stdClass);
    }

    public function testStreamWrapperInvalidInvalidArgument2()
    {
        $this->expectException(InvalidArgumentException::class);
        new StreamWrapperExists(15);
    }

    public function testCallbackInvalidArgument()
    {
        $this->expectException(InvalidArgumentException::class);
        new Callback(15);
    }

    public function testCallbackInvalidArgument2()
    {
        $this->expectException(InvalidArgumentException::class);
        new Callback([$this, 'foobarbar']);
    }

    public function testCpuPerformanceInvalidArgument()
    {
        $this->expectException(InvalidArgumentException::class);
        new CpuPerformance(-1);
    }

    public function testProcessRunningInvalidArgument()
    {
        $this->expectException(InvalidArgumentException::class);
        new ProcessRunning(new stdClass());
    }

    public function testProcessRunningInvalidArgument2()
    {
        $this->expectException(InvalidArgumentException::class);
        new ProcessRunning(-100);
    }

    public function testProcessRunningInvalidArgument3()
    {
        $this->expectException(InvalidArgumentException::class);
        new ProcessRunning('');
    }

    /**
     * @depends testSecurityAdvisory
     */
    public function testSecurityAdvisoryInvalidArgument1()
    {
        $this->expectException(InvalidArgumentException::class);
        new SecurityAdvisory($this->createMock(SecurityChecker::class), new stdClass());
    }

    public function testAbstractFileCheckArgument1()
    {
        $temp = tmpfile();
        fwrite($temp, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<foo>1</foo>");
        $meta = stream_get_meta_data($temp);
        $path = $meta['uri'];

        // single string
        $check = new XmlFile($path);
        $this->assertInstanceOf(SuccessInterface::class, $check->check());

        // array
        $check = new XmlFile([$path, $path, $path]);
        $this->assertInstanceOf(SuccessInterface::class, $check->check());

        // object inplementing \Traversable
        $check = new XmlFile(new ArrayObject([$path, $path, $path]));
        $this->assertInstanceOf(SuccessInterface::class, $check->check());

        fclose($temp);
    }

    public function testAbstractFileCheckInvalidArgument1()
    {
        // int
        try {
            $check = new XmlFile(2);
            $this->fail('InvalidArguementException should be thrown here!');
        } catch (Exception $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e);
        }

        // bool
        try {
            $check = new XmlFile(true);
            $this->fail('InvalidArguementException should be thrown here!');
        } catch (Exception $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e);
        }

        // object not implementing \Traversable
        try {
            $check = new XmlFile(new stdClass());
            $this->fail('InvalidArguementException should be thrown here!');
        } catch (Exception $e) {
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
        $this->assertInstanceOf(SuccessInterface::class, $check->check());

        fclose($temp);
    }

    public function testXmlFileInvalid()
    {
        $temp = tmpfile();
        fwrite($temp, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<foo>1</bar>");
        $meta = stream_get_meta_data($temp);
        $path = $meta['uri'];

        $check = new XmlFile($path);
        $this->assertInstanceOf(FailureInterface::class, $check->check());

        fclose($temp);
    }

    public function testXmlFileNotPresent()
    {
        $check = new XmlFile('/does/not/exist');
        $this->assertInstanceOf(FailureInterface::class, $check->check());
    }

    public function testIniFileValid()
    {
        $temp = tmpfile();
        fwrite($temp, "[first_group]\nfoo = 1\nbar = 5");
        $meta = stream_get_meta_data($temp);
        $path = $meta['uri'];

        $check = new IniFile($path);
        $this->assertInstanceOf(SuccessInterface::class, $check->check());

        fclose($temp);
    }

    public function testIniFileInvalid()
    {
        $temp = tmpfile();
        fwrite($temp, "]]]]]]");
        $meta = stream_get_meta_data($temp);
        $path = $meta['uri'];

        $check = new IniFile($path);
        $this->assertInstanceOf(FailureInterface::class, $check->check());

        fclose($temp);
    }

    public function testIniFileNotPresent()
    {
        $check = new IniFile('/does/not/exist');
        $this->assertInstanceOf(FailureInterface::class, $check->check());
    }

    public function testYamlFileValid()
    {
        $temp = tmpfile();
        fwrite($temp, "foo: 1\nbar: 1");
        $meta = stream_get_meta_data($temp);
        $path = $meta['uri'];

        $check = new YamlFile($path);
        $this->assertInstanceOf(SuccessInterface::class, $check->check());

        fclose($temp);
    }

    public function testYamlFileInvalid()
    {
        $temp = tmpfile();
        fwrite($temp, "foo: 1\n\tbar: 3");
        $meta = stream_get_meta_data($temp);
        $path = $meta['uri'];

        $check = new YamlFile($path);
        $this->assertInstanceOf(FailureInterface::class, $check->check());

        fclose($temp);
    }

    public function testYamlFileNotPresent()
    {
        $check = new YamlFile('/does/not/exist');
        $this->assertInstanceOf(FailureInterface::class, $check->check());
    }

    public function testJsonFileValid()
    {
        $temp = tmpfile();
        fwrite($temp, '{ "foo": "bar"}');
        $meta = stream_get_meta_data($temp);
        $path = $meta['uri'];

        $check = new JsonFile($path);
        $this->assertInstanceOf(SuccessInterface::class, $check->check());

        fclose($temp);
    }

    public function testJsonFileInvalid()
    {
        $temp = tmpfile();
        fwrite($temp, '{ foo: {"bar"');
        $meta = stream_get_meta_data($temp);
        $path = $meta['uri'];

        $check = new JsonFile($path);
        $this->assertInstanceOf(FailureInterface::class, $check->check());

        fclose($temp);
    }

    public function testJsonFileNotPresent()
    {
        $check = new JsonFile('/does/not/exist');
        $this->assertInstanceOf(FailureInterface::class, $check->check());
    }
}

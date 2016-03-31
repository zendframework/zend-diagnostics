ZendDiagnostics
===============

Simple component for performing diagnostic tests in real-world PHP applications.

![ZendDiagnostics](http://i.imgur.com/xd2Na8y.png)

It currently ships with the following Diagnostic Checks:

 * [ApcFragmentation](#apcfragmentation) - check if APC memory fragmentation is below given threshold,
 * [ApcMemory](#apcmemory) - check available APC memory,
 * [Callback](#callback) - call a user-defined diagnostic function,
 * [ClassExists](#classexists) - make sure class exists in current environment,
 * [CouchDBCheck](#couchdbcheck) - check if connection is possible,
 * [CpuPerformance](#cpuperformance) - check server CPU performance is above baseline,
 * [DirReadable](#dirreadable) - make sure given path is readable,
 * [DirWritable](#dirwritable) - make sure given path is writable,
 * [DiskFree](#diskfree) - check there's enough free space on given path,
 * [DiskUsage](#diskusage) - check if the disk usage is below warning/critical percent thresholds,
 * [DoctrineMigration](#doctrinemigration) - make sure all migrations are applied.
 * [ExtensionLoaded](#extensionloaded) - make sure extension is loaded,
 * [GuzzleHttpService](#guzzlehttpservice) - check if given http host is responding using Guzzle,
 * [HttpService](#httpservice) - check if given http host is responding,
 * [Memcache](#memcache) - check if memcache extension is loaded and given server is reachable,
 * [OpCacheMemory](#opcachememory) - check if the OpCache memory usage is below warning/critical thresholds,
 * [PDOCheck](#pdocheck) - check if connection is possible,
 * [PhpVersion](#phpversion) - make sure that PHP version matches constraint,
 * [PhpFlag](#phpflag) - make sure that given PHP flag (feature) is turned on or off.
 * [ProcessRunning](#processrunning) - check if a process with given name or ID is currently running,
 * [RabbitMQ](#rabbitmq) - Validate that a RabbitMQ service is running,
 * [Redis](#redis) - Validate that a Redis service is running,
 * [SecurityAdvisory](#securityadvisory) - check installed composer dependencies against SensioLabs SA database,
 * [StreamWrapperExists](#streamwrapperexists) - make sure given stream wrapper is available.

File validation checks:

 * [IniFile](#inifile) - check if given INI file is available and valid,
 * [JsonFile](#jsonfile) - check if given JSON file is available and valid,
 * [XmlFile](#xmlfile) - check if given XML file is available and valid,
 * [YamlFile](#yamlfile) - check if given YAML file is available and valid

## Using diagnostics with Zend Framework 2

1. Install the [ZFTool module](https://github.com/zendframework/ZFTool).
2. Enable diagnostic tests in [your application config.php](https://github.com/zendframework/ZFTool/blob/master/docs/DIAGNOSTICS.md).
3. In your console type `php public/index.php diag` to run diagnostics.

## Using diagnostics with Symfony 2

1. Install the [LiipMonitorBundle](https://github.com/liip/LiipMonitorBundle).
2. Enable diagnostic tests in [your application configuration](https://github.com/liip/LiipMonitorBundle/blob/master/README.md).
3. In your console type `./app/console monitor:health` to run diagnostics.

## Using diagnostics with PSR-7 middleware

Install the [rstgroup/diagnostics-middleware](https://github.com/rstgroup/diagnostics-middleware).

## Using diagnostics in plain PHP

1. Add ZendDiagnostics component to your application
    * via composer - run `composer require zendframework/zenddiagnostics:dev-master`
    * via git - clone [https://github.com/zendframework/ZendDiagnostics.git](https://github.com/zendframework/ZendDiagnostics.git)
    * manually - download and extract [zip package](https://github.com/zendframework/ZendDiagnostics/archive/master.zip)
2. If you are not using Composer, use `include "autoload_register.php";`
3. Create an instance of `ZendDiagnostics\Runner`
4. Add tests using `Runner::addTest()`
5. Optionally add a reporter to display progress using `Runner::addReporter()`
6. Run diagnostics `Runner::run()`

For example:

````php
// run_diagnostics.php

use ZendDiagnostics\Check;
use ZendDiagnostics\Runner\Runner;
use ZendDiagnostics\Runner\Reporter\BasicConsole;
use ZendDiagnostics\Check\DiskFree;

include 'autoload_register.php';

// Create Runner instance
$runner = new Runner();

// Add checks
$runner->addCheck(new Check\DirWritable('/tmp'));
$runner->addCheck(new Check\DiskFree('/tmp', 100000000));

// Add console reporter
$runner->addReporter(new BasicConsole(80, true));

// Run all checks
$runner->run();

````

You can now run the file in your console (command line):

````bash
> php run_diagnostics.php
Starting diagnostics:

..

OK (2 diagnostic tests)
````


## Using Result Collection

The Runner will always return a [Result\Collection](src/ZendDiagnostics/Result/Collection.php) (even without any
attached Reporter). This collection contains results for all tests and failure counters.

Simple example:

````php
$runner = new Runner();
$checkSpace = new Check\DiskFree('/tmp', 100000000);
$checkTemp  = new Check\DirWritable('/tmp');
$runner->addCheck($checkSpace);
$runner->addCheck($checkTemp);

// Run all checks
$results = $runner->run();

echo "Number of successful tests: " . $results->getSuccessCount() . "\n";
echo "Number of failed tests:     " . $results->getFailureCount() . "\n";

if ($results[$checkSpace] instanceof \ZendDiagnostics\Result\FailureInterface) {
    echo "Oooops! We're running out of space on temp.\n";
}

if ($results[$checkTemp] instanceof \ZendDiagnostics\Result\FailureInterface) {
    echo "It seems that /tmp is not writable - this is a serious problem!\n";
}

````


## Architecture

A single diagnostic [Check](src/ZendDiagnostics/Check/CheckInterface.php) performs one particular
test on the application or environment.

It must return a [Result](src/ZendDiagnostics/Result/ResultInterface.php)
which implements one of the following result interfaces:

 * [Success](src/ZendDiagnostics/Result/SuccessInterface.php) - in case the check ran through without any issue.
 * [Warning](src/ZendDiagnostics/Result/WarningInterface.php) - in case there might be something wrong.
 * [Failure](src/ZendDiagnostics/Result/FailureInterface.php) - when the test failed and an intervention is required.

Each test [Result](src/ZendDiagnostics/Result/ResultInterface.php) can additionally return:

 * **result message** via `getMessage()`. It can be used to describe the context of the result.
 * **result data** via `getData()`. This can be used for providing detailed information on the cause of particular
 result, which might be useful for debugging problems.


One can define additional [result interfaces](src/ZendDiagnostics/Result/ResultInterface.php), i.e. denoting
severity levels (i.e. critical, alert, notice) or appropriate actions (i.e. missing, incomplete). However, it
is recommended to extend the primary set of Success, Warning, Failure interfaces for compatibility with other
applications and libraries.


## Writing custom Checks

A Check class has to implement [Check](src/ZendDiagnostics/Check/CheckInterface.php) and provide the following methods:

````php
interface CheckInterface
{
    /**
     * @return ResultInterface
     */
    public function check();

    /**
     * Return a label describing this test instance.
     *
     * @return string
     */
    public function getLabel();
}
````

The main `check()` method is responsible for doing the actual check and is expected to return a
[Result](src/ZendDiagnostics/Result/ResultInterface.php). It is recommended to use the built-in result classes for
compatibility with Runner and other checks.

Here is an example trivial class, that will check if PHP default timezone is set to UTC.

````php
namespace MyApp\Diagnostics\Check;

use ZendDiagnostics\Check\CheckInterface;
use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Result\Failure;

class TimezoneSetToUTC implements CheckInterface
{
    public function check()
    {
        $tz = date_default_timezone_get();

        if ($tz == 'UTC') {
            return new Success('Default timezone is UTC');
        } else {
            return new Failure('Default timezone is not UTC! It is actually ' . $tz);
        }
    }

    public function getLabel()
    {
        return 'Check if PHP default timezone is set to UTC';
    }
}
````

## Writing custom Reporters

A Reporter is a class implementing [ReporterInterface](src/ZendDiagnostics/Runner/Reporter/ReporterInterface.php).

````php
interface ReporterInterface
{
    public function onStart(ArrayObject $checks, $runnerConfig);
    public function onBeforeRun(Check $check);
    public function onAfterRun(Check $check, Result $result);
    public function onStop(ResultsCollection $results);
    public function onFinish(ResultsCollection $results);
}
````

A Runner invokes above methods while running diagnostics in the following order:

 * `onStart` - right after calling `Runner::run()`
 * `onBeforeRun` - before each individual Check.
 * `onAfterRun` - after each individual check has finished running.
 * `onFinish` - after Runner has finished its job.
 * `onStop` - in case Runner has been interrupted:
     * when the Reporter has returned `false` from `onAfterRun` method
     * or when runner is configured with `setBreakOnFailure(true)` and one of the Checks fails.


Some Reporter methods can be used to interrupt the operation of a Runner:

 * `onBeforeRun(Check $check)` - in case this method returns `false`, that particular Check will be omitted.
 * `onAfterRun(Check $check, Result($result))` - in case this method returns `false`, the Runner will abort checking.

All other return values are ignored.

ZendDiagnostics ships with a [simple Console reporter](src/ZendDiagnostics/Runner/Reporter/BasicConsole.php) - it
can serve as a good example on how to write your own Reporters.


## Built-in diagnostics checks

ZendDiagnostics provides several "just add water" checks you can use straight away.

The following built-in tests are currently available:

### ApcFragmentation

Make sure that [APC memory fragmentation level](www.php.net/apc/‎) is below given threshold:

````php
<?php
use ZendDiagnostics\Check\ApcFragmentation;

// Display a warning with fragmentation > 50% and failure when above 90%
$fragmentation = new ApcFragmentation(50, 90);
````

### ApcMemory

Check [APC memory usage percent](www.php.net/apc/‎) and make sure it's below given threshold.

````php
<?php
use ZendDiagnostics\Check\ApcMemory;

// Display a warning with memory usage is above 70% and a failure above 90%
$checkFreeMemory = new ApcMemory(70, 90);
````

### Callback

Run a function (callback) and use return value as the result:

````php
<?php
use ZendDiagnostics\Check\Callback;
use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Result\Failure;

$checkDbFile = new Callback(function(){
    $path = __DIR__ . '/data/db.sqlite';
    if(is_file($path) && is_readable($path) && filesize($path)) {
        return new Success('Db file is ok');
    } else {
        return new Failure('There is something wrong with the db file');
    }
});
````

**Note:** The callback must return either a `boolean` (true for success, false for failure) or a valid instance of
[ResultInterface](src/ZendDiagnostics/Result/ResultInterface.php). All other objects will result in an exception
and scalars (i.e. a string) will be interpreted as warnings.

### ClassExists

Check if a class (or an array of classes) exist. For example:

````php
<?php
use ZendDiagnostics\Check\ClassExists;

$checkLuaClass    = new ClassExists('Lua');
$checkRbacClasses = new ClassExists(array(
    'ZfcRbac\Module',
    'ZfcRbac\Controller\Plugin\IsGranted'
));
````

### CpuPerformance

Benchmark CPU performance and return failure if it is below the given ratio. The baseline for performance calculation
is the speed of Amazon EC2 Micro Instance (Q1 2013). You can specify the expected performance for the test, where a
ratio of `1.0` (one) means at least the speed of EC2 Micro Instance. A ratio of `2` would mean "at least double the
performance of EC2 Micro Instance" and a fraction of `0.5` means "at least half the performance of Micro Instance".

The following check will test if current server has at least half the CPU power of EC2 Micro Instance:

````php
<?php
use ZendDiagnostics\Check\CpuPerformance;

$checkMinCPUSpeed = new CpuPerformance(0.5); // at least 50% of EC2 micro instance
````

### DirReadable

Check if a given path (or array of paths) points to a directory and it is readable.

````php
<?php
use ZendDiagnostics\Check\DirReadable;

$checkPublic = new DirReadable('public/');
$checkAssets = new DirReadable(array(
    __DIR__ . '/assets/img',
    __DIR__ . '/assets/js'
));
````

### DirWritable

Check if a given path (or array of paths) points to a directory and if it can be written to.

````php
<?php
use ZendDiagnostics\Check\DirWritable;

$checkTemporary = new DirWritable('/tmp');
$checkAssets    = new DirWritable(array(
    __DIR__ . '/assets/customImages',
    __DIR__ . '/assets/customJs',
    __DIR__ . '/assets/uploads',
));
````

### DiskFree

Check if there is enough remaining free disk space.

The first parameter is the minimum disk space, which can be supplied as integer (in bytes, i.e. `1024`)
or as a string with a multiplier (IEC, SI or Jedec, i.e. `"150MB"`). The second parameter is the path to check -
on *NIX systems it is an ordinary path (i.e. `/home`), on Windows systems it is a drive letter (i.e. `"C:"`).

````php
<?php
use ZendDiagnostics\Check\DiskFree;

$tempHasAtLeast100Megs  = new DiskFree('100MB', '/tmp');
$homeHasAtLeast1TB      = new DiskFree('1TiB',  '/home');
$dataHasAtLeast900Bytes = new DiskFree(900, __DIR__ . '/data/');
````

### ExtensionLoaded

Check if a PHP extension (or an array of extensions) is currently loaded.

````php
<?php
use ZendDiagnostics\Check\ExtensionLoaded;

$checkMbstring    = new ExtensionLoaded('mbstring');
$checkCompression = new ExtensionLoaded(array(
    'rar',
    'bzip2',
    'zip'
));
````

### HttpService

Attempt connection to given HTTP host or IP address and try to load a web page. The check also supports
checking response codes and page contents.

````php
<?php
use ZendDiagnostics\Check\HttpService;

// Try to connect to google.com
$checkGoogle = new HttpService('www.google.com');

// Check port 8080 on localhost
$checkLocal = new HttpService('127.0.0.1', 8080);

// Check that the page exists (response code must equal 200)
$checkPage = new HttpService('www.example.com', 80, '/some/page.html', 200);

// Check page content
$checkPageContent = new HttpService(
    'www.example.com',
    80,
    '/some/page.html',
    200,
    '<title>Hello World</title>'
);
````

### GuzzleHttpService

Attempt connection to given HTTP host or IP address and try to load a web page using
[Guzzle](http://guzzle3.readthedocs.org/en/latest/). The check also supports checking response
codes and page contents.

````php
<?php
use ZendDiagnostics\Check\GuzzleHttpService;

// Try to connect to google.com
$checkGoogle = new GuzzleHttpService('www.google.com');

// Check port 8080 on localhost
$checkLocal = new GuzzleHttpService('127.0.0.1:8080');

// Check that the page exists (response code must equal 200)
$checkPage = new GuzzleHttpService('www.example.com/some/page.html');

// Check page content
$checkPageContent = new GuzzleHttpService(
    'www.example.com/some/page.html',
    array(),
    array(),
    200,
    '<title>Hello World</title>'
);

// Check that the post request returns the content
$checkPageContent = new GuzzleHttpService(
    'www.example.com/user/update',
    array(),
    array(),
    200,
    '{"status":"success"}',
    'POST',
    array("post_field" => "post_value")
);
````

### Memcache

Attempt to connect to given Memcache server.

````php
<?php
use ZendDiagnostics\Check\Memcache;

$checkLocal  = new Memcache('127.0.0.1'); // default port
$checkBackup = new Memcache('10.0.30.40', 11212);
````

### PhpVersion

Check if current PHP version matches the given requirement. The test accepts 2 parameters - baseline version and
optional [comparison operator](http://www.php.net/manual/en/function.version-compare.php).


````php
<?php
use ZendDiagnostics\Check\PhpVersion;

$require545orNewer  = new PhpVersion('5.4.5');
$rejectBetaVersions = new PhpVersion('5.5.0', '<');
````

### PhpFlag

Make sure that given PHP flag(s) is enabled or disabled (i.e. as defined in php.ini). You can use this test to
alert the user about unsafe or behavior-changing PHP settings.

````php
<?php
use ZendDiagnostics\Check\PhpFlag;

// This check will fail if use_only_cookies is not enabled
$sessionOnlyUsesCookies = new PhpFlag('session.use_only_cookies', true);

// This check will fail if safe_mode has been enabled
$noSafeMode = new PhpFlag('safe_mode', false);

// The following will fail if any of the flags is enabled
$check = new PhpFlag(array(
    'expose_php',
    'ignore_user_abort',
    'html_errors'
), false);
````

### ProcessRunning

Check if a given unix process is running. This check supports PIDs and process names.

````php
<?php
use ZendDiagnostics\Check\ProcessRunning;

$checkApache = new ProcessRunning('httpd');

$checkProcess1000 = new ProcessRunning(1000);
````

### RabbitMQ

Validate that a RabbitMQ service is running.

```php
<?php
use ZendDiagnostics\Check\RabbitMQ;

$rabbitMQCheck = new RabbitMQ('localhost', 5672, 'guest', 'guest', '/');
```

### Redis

Validate that a Redis service is running.

```php
<?php
use ZendDiagnostics\Check\Redis;

$redisCheck = new Redis('localhost', 6379, 'secret');
```

### SecurityAdvisory

Run a security check of libraries locally installed by [Composer](http://getcomposer.org) against
[SensioLabs Security Advisory database](https://security.sensiolabs.org/database) and warn about potential
security vulnerabilities.

````php
<?php
use ZendDiagnostics\Check\SecurityAdvisory;

// Warn about any packages that might have security vulnerabilities and require updating
$security = new SecurityAdvisory();

// Check another composer.lock
$security = new SecurityAdvisory('/var/www/project/composer.lock');
````

### StreamWrapperExists

Check if a given stream wrapper (or an array of wrappers) is available. For example:

````php
<?php
use ZendDiagnostics\Check\StreamWrapperExists;

$checkOGGStream   = new StreamWrapperExists('ogg');
$checkCompression = new StreamWrapperExists(array(
    'zlib',
    'bzip2',
    'zip'
));
````

### DoctrineMigration

Make sure all migrations are applied:

````php
<?php
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\ORM\EntityManager;
use ZendDiagnostics\Check\DoctrineMigration;

$em = EntityManager::create(/** config */);
$migrationConfig = new Configuration($em);
$check = new DoctrineMigration($migrationConfig);
````

### IniFile

Read an INI-file from the given path and try to parse it.

````php
<?php
use ZendDiagnostics\Check\IniFile;

$checkIniFile = new IniFile('/my/path/to/file.ini');
$checkIniFile = new IniFile(['file1.ini', 'file2.ini', '...']);
````

### JsonFile

Read a JSON-file from the given path and try to decode it.

````php
<?php
use ZendDiagnostics\Check\JsonFile;

$checkJsonFile = new JsonFile('/my/path/to/file.json');
$checkJsonFile = new JsonFile(['file1.json', 'file2.json', '...']);
````


### XmlFile

Read an XML-file from the given path, try to parse it and validate it agaist its DTD schema if possible.

````php
<?php
use ZendDiagnostics\Check\XmlFile;

$checkXmlFile = new XmlFile('/my/path/to/file.xml');
$checkXmlFile = new XmlFile(['file1.xml', 'file2.xml', '...']);
````

### YamlFile

Read a YAML-file from the given path and try to parse it.

````php
<?php
use ZendDiagnostics\Check\YamlFile;

$checkYamlFile = new YamlFile('/my/path/to/file.yml');
$checkYamlFile = new YamlFile(['file1.yml', 'file2.yml', '...']);
````

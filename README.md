ZendDiagnostics
===============

Interfaces for performing diagnostic tests in PHP applications.

It currently ships with the following Checks: [ClassExists](#classexists), [CpuPerformance](#cpuperformance),
[DirReadable](#dirreadable), [DirWritable](#dirwritable), [ExtensionLoaded](#extensionloaded),
[PhpVersion](#phpversion), [SteamWrapperExists](#streamwrapperexists)

## Using diagnostics with Symfony 2

> TODO

## Using diagnostics with Zend Framework 2

1. Install the [ZFTool module](https://github.com/zendframework/ZFTool/pulls).
2. Enable diagnostic tests in [your application config.php](https://github.com/zendframework/ZFTool/blob/master/docs/DIAGNOSTICS.md).
3. In your console type `php public/index.php diag` to run diagnostics.

## Using diagnostics with another PHP application.

> WIP

1. Add ZendDiagnostics component to your application
    * via composer - run `composer require zendframework/zenddiagnostics:dev-master`
    * via git - clone [https://github.com/zendframework/ZendDiagnostics.git](https://github.com/zendframework/ZendDiagnostics.git) and add `ZendDiagnostics` to your autoloader.
    * manually - download and extract [package](https://github.com/zendframework/ZendDiagnostics/archive/master.zip) and add `ZendDiagnostics` to your autoloader.
2. Create an instance of `ZendDiagnostics\Runner`
3. Add tests using `Runner::addTest()`
4. Run diagnostics `Runner::start()`;


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

## Built-in diagnostics checks

ZendDiagnostics provides several "just add water" checks you can use straight away.

The following built-in tests are currently available:

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


### PhpVersion

Check if current PHP version matches the given requirement. The test accepts 2 parameters - baseline version and
optional [comparison operator](http://www.php.net/manual/en/function.version-compare.php).


````php
<?php
use ZendDiagnostics\Check\PhpVersion;

$require545orNewer  = new PhpVersion('5.4.5');
$rejectBetaVersions = new PhpVersion('5.5.0', '<');
````

### SteamWrapperExists

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

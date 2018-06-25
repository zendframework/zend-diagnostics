# Introduction and Usage

zendframework/zenddiagnostics provides diagnostic tests for real-world PHP
applications.

It currently ships with the following diagnostic checks:

- [ApcFragmentation](diagnostics.md#apcfragmentation) - check if APC memory fragmentation is below given threshold,
- [ApcMemory](diagnostics.md#apcmemory) - check available APC memory,
- [Callback](diagnostics.md#callback) - call a user-defined diagnostic function,
- [ClassExists](diagnostics.md#classexists) - make sure class exists in current environment,
- [CouchDBCheck](diagnostics.md#couchdbcheck) - check if connection is possible,
- [CpuPerformance](diagnostics.md#cpuperformance) - check server CPU performance is above baseline,
- [DirReadable](diagnostics.md#dirreadable) - make sure given path is readable,
- [DirWritable](diagnostics.md#dirwritable) - make sure given path is writable,
- [DiskFree](diagnostics.md#diskfree) - check there's enough free space on given path,
- [DiskUsage](diagnostics.md#diskusage) - check if the disk usage is below warning/critical percent thresholds,
- [DoctrineMigration](diagnostics.md#doctrinemigration) - make sure all migrations are applied.
- [ExtensionLoaded](diagnostics.md#extensionloaded) - make sure extension is loaded,
- [GuzzleHttpService](diagnostics.md#guzzlehttpservice) - check if given http host is responding using Guzzle,
- [HttpService](diagnostics.md#httpservice) - check if given http host is responding,
- [Memcache](diagnostics.md#memcache) - check if memcache extension is loaded and given server is reachable,
- [Mongo](diagnostics.md#mongodb) - check if connection to MongoDb is possible,
- [OpCacheMemory](diagnostics.md#opcachememory) - check if the OpCache memory usage is below warning/critical thresholds,
- [PDOCheck](diagnostics.md#pdocheck) - check if connection is possible,
- [PhpVersion](diagnostics.md#phpversion) - make sure that PHP version matches constraint,
- [PhpFlag](diagnostics.md#phpflag) - make sure that given PHP flag (feature) is turned on or off.
- [ProcessRunning](diagnostics.md#processrunning) - check if a process with given name or ID is currently running,
- [RabbitMQ](diagnostics.md#rabbitmq) - Validate that a RabbitMQ service is running,
- [Redis](diagnostics.md#redis) - Validate that a Redis service is running,
- [SecurityAdvisory](diagnostics.md#securityadvisory) - check installed composer dependencies against SensioLabs SA database,
- [StreamWrapperExists](diagnostics.md#streamwrapperexists) - make sure given stream wrapper is available.

It also provides the following file validation checks:

- [IniFile](file-validation.md#inifile) - check if given INI file is available and valid,
- [JsonFile](file-validation.md#jsonfile) - check if given JSON file is available and valid,
- [XmlFile](file-validation.md#xmlfile) - check if given XML file is available and valid,
- [YamlFile](file-validation.md#yamlfile) - check if given YAML file is available and valid

## Performing diagnostics with Zend Framework 2

1. Install the [ZFTool module](https://github.com/zendframework/ZFTool).
2. Enable diagnostic tests in [your application config.php](https://github.com/zendframework/ZFTool/blob/master/docs/DIAGNOSTICS.md).
3. In your console type `php public/index.php diag` to run diagnostics.

Note: this does not work with Zend Framework 3; use the [plain PHP
diagnostics](#using-diagnostics-in-plain-php) instructions below when using that
framework version.

## Using diagnostics with Symfony 2

1. Install the [LiipMonitorBundle](https://github.com/liip/LiipMonitorBundle).
2. Enable diagnostic tests in [your application configuration](https://github.com/liip/LiipMonitorBundle/blob/master/README.md).
3. In your console type `./app/console monitor:health` to run diagnostics.

## Using diagnostics with PSR-7 middleware

Install the [rstgroup/diagnostics-middleware](https://github.com/rstgroup/diagnostics-middleware).

## Using diagnostics in plain PHP

1. Create an instance of `ZendDiagnostics\Runner`.
2. Add tests using `Runner::addTest()`.
3. Optionally add a reporter to display progress using `Runner::addReporter()`.
4. Run diagnostics `Runner::run()`.

For example:

```php
<?php
// run_diagnostics.php

use ZendDiagnostics\Check;
use ZendDiagnostics\Runner\Runner;
use ZendDiagnostics\Runner\Reporter\BasicConsole;

include 'vendor/autoload.php';

// Create Runner instance
$runner = new Runner();

// Add checks
$runner->addCheck(new Check\DirWritable('/tmp'));
$runner->addCheck(new Check\DiskFree(100000000, '/tmp'));

// Add console reporter
$runner->addReporter(new BasicConsole(80, true));

// Run all checks
$results = $runner->run();

// Emit an appropriate exit code
$status = ($results->getFailureCount() + $results->getWarningCount()) > 0 ? 1 : 0;
exit($status);
```

You can now run the file in your console (command line):

```bash
$ php run_diagnostics.php
Starting diagnostics:

..

OK (2 diagnostic tests)
```

## Using a result collection

The diagnostics runner will always return a
[ZendDiagnostics\Result\Collection](https://github.com/zendframework/zend-diagnostics/src/Result/Collection.php),
even when no reporter is attached. This collection contains results for all
tests and failure counters.

As an example:

```php
<?php
use ZendDiagnostics\Check;
use ZendDiagnostics\Result;
use ZendDiagnostics\Runner\Runner;

$runner = new Runner();
$checkSpace = new Check\DiskFree(100000000, '/tmp');
$checkTemp  = new Check\DirWritable('/tmp');
$runner->addCheck($checkSpace);
$runner->addCheck($checkTemp);

// Run all checks
$results = $runner->run();

echo "Number of successful tests: " . $results->getSuccessCount() . "\n";
echo "Number of failed tests:     " . $results->getFailureCount() . "\n";

if ($results[$checkSpace] instanceof Result\FailureInterface) {
    echo "Oooops! We're running out of space on temp.\n";
}

if ($results[$checkTemp] instanceof Result\FailureInterface) {
    echo "It seems that /tmp is not writable - this is a serious problem!\n";
}
```

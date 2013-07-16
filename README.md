ZendDiagnostics
===============

Simple component for performing diagnostic tests in real-world PHP applications.


## Using diagnostics with Symfony 2

> TODO

## Using diagnostics with Zend Framework 2

1. Install the [ZFTool module](https://github.com/zendframework/ZFTool/pulls).
2. Enable diagnostic tests in [your application config.php](https://github.com/zendframework/ZFTool/blob/master/docs/DIAGNOSTICS.md).
3. In your console type `php public/index.php diag` to run diagnostics.

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
use ZendDiagnostics\Check\Failure;

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

 * `onStart` - right after calling `Reporter::run()`
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



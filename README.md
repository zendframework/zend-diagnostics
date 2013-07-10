ZendDiagnostics
===============

Interfaces for performing diagnostic tests in PHP applications.


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


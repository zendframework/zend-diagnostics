PHP Diagnostics
==============

Universal set of diagnostic tests for PHP applications.

## Using diagnostics with Symfony 2

> TODO

## Using diagnostics with Zend Framework 2

1. Install the [ZFTool module](https://github.com/zendframework/ZFTool/pulls).
2. Enable diagnostic tests in [your application config.php](https://github.com/zendframework/ZFTool/blob/master/docs/DIAGNOSTICS.md).
3. In your console type `php public/index.php diag` to run diagnostics.

## Using diagnostics with another PHP application.

> WIP

1. Add PHPDiagnostics component to your application
    * via composer - run `composer require thinkscape/phpdiagnostics:dev-master`
    * via git - clone [https://github.com/Thinkscape/PHPDiagnostics.git](https://github.com/Thinkscape/PHPDiagnostics.git) and add `PHPDiagnostics` to your autoloader.
    * manually - download and extract [package](https://github.com/Thinkscape/PHPDiagnostics/archive/master.zip) and add `PHPDiagnostics` to your autoloader.
2. Create an instance of `PHPDiagnostics\Runner`
3. Add tests using `Runner::addTest()`
4. Run diagnostics `Runner::start()`;

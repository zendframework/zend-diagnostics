# Writing Custom Checks

A Check class MUST implement [Check](https://github.com/zendframework/zend-diagnostics/tree/master/src/Check/CheckInterface.php)
and provide the following methods:

```php
<?php
namespace ZendDiagnostics\Check;

use ZendDiagnostics\Result\ResultInterface;

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
```

The main `check()` method is responsible for performing the actual check, and is
expected to return a [Result](https://github.com/zendframework/zend-diagnostics/tree/master/src/Result/ResultInterface.php).
It is recommended to use the built-in result classes for compatibility with the
diagnostics Runner and other checks.

Below is an example class that checks if the PHP default timezone is set to UTC.

```php
<?php
namespace MyApp\Diagnostics\Check;

use ZendDiagnostics\Check\CheckInterface;
use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Result\Failure;

class TimezoneSetToUTC implements CheckInterface
{
    public function check()
    {
        $tz = date_default_timezone_get();

        if ($tz === 'UTC') {
            return new Success('Default timezone is UTC');
        }

        return new Failure('Default timezone is not UTC! It is actually ' . $tz);
    }

    public function getLabel()
    {
        return 'Check if PHP default timezone is set to UTC';
    }
}
```

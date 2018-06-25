# Writing Custom Reporters

A reporter is a class implementing
  [ReporterInterface](https://github.com/zendframework/zend-diagnostics/tree/master/src/Runner/Reporter/ReporterInterface.php).

```php
<?php
namespace ZendDiagnostics\Runner\Reporter;

use ArrayObject;
use ZendDiagnostics\Check\CheckInterface as Check;
use ZendDiagnostics\Result\Collection as ResultCollection;
use ZendDiagnostics\Result\ResultInterface as Result;

interface ReporterInterface
{
    public function onStart(ArrayObject $checks, $runnerConfig);
    public function onBeforeRun(Check $check);
    public function onAfterRun(Check $check, Result $result);
    public function onStop(ResultCollection $results);
    public function onFinish(ResultCollection $results);
}
```

A Runner invokes the above methods while running diagnostics in the following order:

- `onStart` - right after calling `Runner::run()`
- `onBeforeRun` - before each individual Check.
- `onAfterRun` - after each individual check has finished running.
- `onFinish` - after Runner has finished its job.
- `onStop` - in case Runner has been interrupted:
    - when the reporter has returned `false` from the `onAfterRun` method
    - or when the runner is configured with `setBreakOnFailure(true)` and one of
      the checks fails.

Some reporter methods can be used to interrupt the operation of a diagnostics
runner:

- `onBeforeRun(Check $check)` - in case this method returns `false`, that
  particular check will be omitted.
- `onAfterRun(Check $check, Result($result))` - in case this method returns
  `false`, the runner will abort checking.

All other return values are ignored.

zendframework/zenddiagnostics ships with a [simple Console reporter](https://github.com/zendframework/zend-diagnostics/tree/master/src/Runner/Reporter/BasicConsole.php)
that can serve as an example of how to write your own reporters.

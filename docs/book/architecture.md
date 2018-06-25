# Architecture

A single diagnostic [Check](https://github.com/zendframework/tree/master/src/Check/CheckInterface.php)
performs one particular test on the application or environment.

It MUST return a [Result](src/zend-diagnostics/Result/ResultInterface.php)
which implements one of the following result interfaces:

- [Success](https://github.com/zendframework/tree/master/src/Result/SuccessInterface.php) - in case the check ran through without any issue.
- [Warning](https://github.com/zendframework/tree/master/src/Result/WarningInterface.php) - in case there might be something wrong.
- [Failure](https://github.com/zendframework/tree/master/src/Result/FailureInterface.php) - when the test failed and an intervention is required.

Each test [Result](https://github.com/zendframework/tree/master/src/Result/ResultInterface.php) can additionally return:

- **result message** via `getMessage()`. It can be used to describe the context of the result.
- **result data** via `getData()`. This can be used for providing detailed information on the cause of particular
  result, which might be useful for debugging problems.

One can define additional [result interfaces](https://github.com/zendframework/tree/master/src/Result/ResultInterface.php),
to denote additional severity levels (e.g. critical, alert, notice) or
appropriate actions (i.e. missing, incomplete). However, it is recommended to
extend the primary set of success, warning, and failure interfaces for
compatibility with other applications and libraries.

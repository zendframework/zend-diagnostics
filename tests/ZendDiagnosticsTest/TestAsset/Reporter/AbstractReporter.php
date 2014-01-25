<?php
namespace ZendDiagnosticsTest\TestAsset\Reporter;

use ZendDiagnostics\Check\CheckInterface as Check;
use ZendDiagnostics\Result\ResultInterface as Result;
use ZendDiagnostics\Result\Collection as ResultsResult;
use ZendDiagnostics\Runner\Reporter\ReporterInterface;
use \ArrayObject;

abstract class AbstractReporter implements ReporterInterface
{
    public function onStart(ArrayObject $checks, $runnerConfig) {}
    public function onBeforeRun(Check $check, $checkAlias = null) {}
    public function onAfterRun(Check $check, Result $result, $checkAlias = null) {}
    public function onStop(ResultsResult $results) {}
    public function onFinish(ResultsResult $results) {}
}

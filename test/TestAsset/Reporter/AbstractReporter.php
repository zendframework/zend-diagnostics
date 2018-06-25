<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2013-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

namespace ZendDiagnosticsTest\TestAsset\Reporter;

use ArrayObject;
use ZendDiagnostics\Check\CheckInterface as Check;
use ZendDiagnostics\Result\Collection as ResultsResult;
use ZendDiagnostics\Result\ResultInterface as Result;
use ZendDiagnostics\Runner\Reporter\ReporterInterface;

abstract class AbstractReporter implements ReporterInterface
{
    public function onStart(ArrayObject $checks, $runnerConfig)
    {
    }

    public function onBeforeRun(Check $check, $checkAlias = null)
    {
    }

    public function onAfterRun(Check $check, Result $result, $checkAlias = null)
    {
    }

    public function onStop(ResultsResult $results)
    {
    }

    public function onFinish(ResultsResult $results)
    {
    }
}

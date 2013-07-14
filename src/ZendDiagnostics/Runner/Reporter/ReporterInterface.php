<?php
namespace ZendDiagnostics\Runner\Reporter;

use ZendDiagnostics\Check\CheckInterface as Check;
use ZendDiagnostics\Result\ResultInterface as Result;
use ZendDiagnostics\Result\Collection as ResultsCollection;
use \ArrayObject;

/**
 * Interface for a Reporter that can be attached to Runner.
 *
 * A Reporter is responsible for gathering information on Checks and their results
 * as they are being invoked by the Runner. Some methods can be also be used to
 * stop the Runner in the middle of testing.
 *
 * @package ZendDiagnostics\Runner\Reporter
 */
interface ReporterInterface
{
    public function onStart(ArrayObject $checks, $runnerConfig);
    public function onBeforeRun(Check $check);
    public function onAfterRun(Check $check, Result $result);
    public function onStop(ResultsCollection $results);
    public function onFinish(ResultsCollection $results);
}
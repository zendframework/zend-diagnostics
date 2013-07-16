<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Runner\Reporter;

use ZendDiagnostics\Check\CheckInterface;
use ZendDiagnostics\Result\ResultInterface;
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
    public function onBeforeRun(CheckInterface $check);
    public function onAfterRun(CheckInterface $check, ResultInterface $result);
    public function onStop(ResultsCollection $results);
    public function onFinish(ResultsCollection $results);
    public function setVerbose($verbose);
}
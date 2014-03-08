<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use InvalidArgumentException;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;

/**
 * Check if a process with given name or ID is currently running.
 */
class ProcessRunning extends AbstractCheck
{
    /**
     * @var string
     */
    private $processName;
    private $pid;

    /**
     * @param string|int $processNameOrPid   Name or ID of the process to find.
     * @throws \InvalidArgumentException
     */
    public function __construct($processNameOrPid)
    {
        if (empty($processNameOrPid)) {
            throw new InvalidArgumentException(sprintf(
                'Wrong argument provided for ProcessRunning check - ' .
                'expected a process name (string) or pid (positive number).',
                gettype($processNameOrPid)
            ));
        }

        if (!is_numeric($processNameOrPid) && !is_scalar($processNameOrPid)) {
            throw new InvalidArgumentException(sprintf(
                'Wrong argument provided for ProcessRunning check - ' .
                'expected a process name (string) or pid (positive number) but got %s',
                gettype($processNameOrPid)
            ));
        }

        if (is_numeric($processNameOrPid)) {
            if ((int)$processNameOrPid < 0) {
                throw new InvalidArgumentException(sprintf(
                    'Wrong argument provided for ProcessRunning check - ' .
                    'expected pid to be a positive number but got %s',
                    (int)$processNameOrPid
                ));
            }
            $this->pid = (int)$processNameOrPid;
        } else {
            $this->processName = $processNameOrPid;
        }
    }

    /**
     * @see ZendDiagnostics\CheckInterface::check()
     */
    public function check()
    {
        // TODO: make more OS agnostic
        if ($this->pid) {
            exec('ps -p ' . (int)$this->pid, $output, $return);

            if ($return == 1) {
                return new Failure(sprintf('Process with PID %s is not currently running.', $this->pid));
            }
        } else {
            exec('ps -ef | grep ' . escapeshellarg($this->processName) . ' | grep -v grep', $output, $return);

            if ($return == 1) {
                return new Failure(sprintf('Could not find any running process containing "%s"', $this->processName));
            }
        }

        return new Success();
    }
}

<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;

class ProcessActive extends AbstractCheck
{
    /**
     * @var string
     */
    private $command;

    public function __construct($command)
    {
        $this->command = $command;
    }

    /**
     * @see ZendDiagnostics\CheckInterface::check()
     */
    public function check()
    {
        // TODO make more OS agnostic
        exec('ps -ef | grep ' . escapeshellarg($this->command) . ' | grep -v grep', $output, $return);
        if ($return == 1) {
            return new Failure(sprintf('There is no process running containing "%s"', $this->command));
        }

        return new Success();
    }
}

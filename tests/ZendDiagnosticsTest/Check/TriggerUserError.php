<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace ZendDiagnosticsTest\Check;

use ZendDiagnostics\Check\AbstractCheck;
use ZendDiagnostics\Result\Success;

class TriggerUserError extends AbstractCheck
{
    protected $label = '';

    protected $message;
    protected $severity;

    protected $result = true;

    public function __construct($message, $severity, $result = true)
    {
        $this->message  = $message;
        $this->severity = $severity;
        $this->result   = $result;
    }

    public function check()
    {
        trigger_error($this->message, $this->severity);
        return $this->result;
    }
}
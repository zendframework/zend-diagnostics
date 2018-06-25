<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2013-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

namespace ZendDiagnosticsTest\TestAsset\Check;

use ZendDiagnostics\Check\AbstractCheck;

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

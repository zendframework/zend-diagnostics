<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace ZendDiagnosticsTest\TestAsset\Check;

use ZendDiagnostics\Check\AbstractCheck;

class ReturnThis extends AbstractCheck
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function check()
    {
        return $this->value;
    }
}

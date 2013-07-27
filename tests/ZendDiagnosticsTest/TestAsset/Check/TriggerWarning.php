<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace ZendDiagnosticsTest\TestAsset\Check;

use ZendDiagnostics\Check\AbstractCheck;
use ZendDiagnostics\Result\Success;

class TriggerWarning extends AbstractCheck
{
    public function check()
    {
        strpos(); // <-- this will throw a real warning

        return new Success(); // this should be ignored
    }
}

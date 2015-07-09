<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace ZendDiagnosticsTest\TestAsset\Check;

use ZendDiagnostics\Check\AbstractCheck;

class ThrowException extends AbstractCheck
{
    public function check()
    {
        throw new \Exception('This check always throws a generic \Exception');
    }
}

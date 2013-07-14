<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace ZendDiagnosticsTest\Check;

use ZendDiagnostics\Check\AbstractCheck;
use ZendDiagnostics\Result\Failure;

class AlwaysFailure extends AbstractCheck
{
    public function check()
    {
        return new Failure('This check always results in failure!');
    }
}
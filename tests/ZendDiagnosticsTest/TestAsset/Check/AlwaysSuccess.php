<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace ZendDiagnosticsTest\TestAsset\Check;

use ZendDiagnostics\Check\AbstractCheck;
use ZendDiagnostics\Result\Success;

class AlwaysSuccess extends AbstractCheck
{
    public function check()
    {
        return new Success('This check always results in success!');
    }
}

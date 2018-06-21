<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2013-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

namespace ZendDiagnosticsTest\TestAsset\Check;

use Exception;
use ZendDiagnostics\Check\AbstractCheck;

class ThrowException extends AbstractCheck
{
    public function check()
    {
        throw new Exception('This check always throws a generic \Exception');
    }
}

<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use ZendDiagnostics\Result\ResultInterface;

interface CheckInterface
{
    /**
     * @return ResultInterface
     */
    public function check();
}

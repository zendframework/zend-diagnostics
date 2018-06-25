<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2013-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

namespace ZendDiagnostics\Check;

use ZendDiagnostics\Result\ResultInterface;

interface CheckInterface
{
    /**
     * Perform the actual check and return a ResultInterface
     *
     * @return ResultInterface
     */
    public function check();

    /**
     * Return a label describing this test instance.
     *
     * @return string
     */
    public function getLabel();
}

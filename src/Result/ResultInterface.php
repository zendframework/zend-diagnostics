<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2013-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

namespace ZendDiagnostics\Result;

interface ResultInterface
{
    /**
     * Get message related to the result.
     *
     * @return string
     */
    public function getMessage();

    /**
     * Get detailed info on the test result (if available).
     *
     * @return mixed|null
     */
    public function getData();
}

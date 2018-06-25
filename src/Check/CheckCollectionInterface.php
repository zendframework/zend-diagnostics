<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2013-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

namespace ZendDiagnostics\Check;

use Traversable;

interface CheckCollectionInterface
{
    /**
     * Return a list of CheckInterface's.
     *
     * @return array|Traversable
     */
    public function getChecks();
}

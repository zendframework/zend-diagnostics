<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2013-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

namespace ZendDiagnosticsTest;

use ZendDiagnostics\Check\OpCacheMemory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class OpCacheMemoryTest extends AbstractMemoryTest
{
    protected function createCheck($warningThreshold, $criticalThreshold)
    {
        return new OpCacheMemory($warningThreshold, $criticalThreshold);
    }
}

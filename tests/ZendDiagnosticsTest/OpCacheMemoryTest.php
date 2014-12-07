<?php

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

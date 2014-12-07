<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Skip;
use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Result\Warning;

/**
 * Checks to see if the OpCache memory usage is below warning/critical thresholds
 */
class OpCacheMemory extends AbstractMemoryCheck
{
    /**
     * OpCache information
     *
     * @var array
     */
    private $opCacheInfo;

    /**
     * Perform the check
     *
     * @see \ZendDiagnostics\Check\CheckInterface::check()     *
     * @return Failure|Skip|Success|Warning
     */
    public function check()
    {
        if (!function_exists('opcache_get_status')) {
            return new Warning('Zend OPcache extension is not available');
        }

        $this->opCacheInfo = opcache_get_status(false);

        if (!is_array($this->opCacheInfo) || !array_key_exists('memory_usage', $this->opCacheInfo)) {
            return new Warning('Zend OPcache extension is not enabled in this environment');
        }

        return parent::check();
    }

    /**
     * Return a label describing this test instance.
     *
     * @return string
     */
    public function getLabel()
    {
        return 'OPcache Memory';
    }

    /**
     * Returns the total memory in bytes
     *
     * @return int
     */
    protected function getTotalMemory()
    {
        return $this->opCacheInfo['memory_usage']['used_memory'] + $this->opCacheInfo['memory_usage']['free_memory'] + $this->opCacheInfo['memory_usage']['wasted_memory'];
    }

    /**
     * Returns the used memory in bytes
     *
     * @return int
     */
    protected function getUsedMemory()
    {
        return $this->opCacheInfo['memory_usage']['used_memory'];
    }
}

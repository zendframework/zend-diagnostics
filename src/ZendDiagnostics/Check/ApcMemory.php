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
 * Checks to see if the APC memory usage is below warning/critical thresholds
 *
 * APC memory logic borrowed from APC project:
 *      https://github.com/php/pecl-caching-apc/blob/master/apc.php
 *      authors:   Ralf Becker <beckerr@php.net>, Rasmus Lerdorf <rasmus@php.net>, Ilia Alshanetsky <ilia@prohost.org>
 *      license:   The PHP License, version 3.01
 *      copyright: Copyright (c) 2006-2011 The PHP Group
 */
class ApcMemory implements CheckInterface
{
    protected $warningThreshold;
    protected $criticalThreshold;

    /**
     * @param int $warningThreshold
     * @param int $criticalThreshold
     */
    public function __construct($warningThreshold, $criticalThreshold)
    {
        $this->warningThreshold = (int) $warningThreshold;
        $this->criticalThreshold = (int) $criticalThreshold;
    }

    /**
     * Perform the check
     *
     * @see \ZendDiagnostics\Check\CheckInterface::check()     *
     * @return Failure|Skip|Success|Warning
     */
    public function check()
    {
        if ('cli' === php_sapi_name()) {
            return new Skip('APC not available in CLI');
        }

        $info = apc_sma_info();
        $size = $info['num_seg'] * $info['seg_size'];
        $available = $info['avail_mem'];
        $used = $size - $available;
        $percentUsed = ($used / $size) * 100;
        $message = sprintf('%.0f%% of available %s memory used.', $percentUsed, $this->formatBytes($size));

        if ($percentUsed > $this->criticalThreshold) {
            return new Failure($message);
        }

        if ($percentUsed > $this->warningThreshold) {
            return new Warning($message);
        }

        return new Success();
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return 'APC Memory';
    }

    /**
     * @param int $bytes
     * @return string
     */
    private function formatBytes($bytes)
    {
        $size = 'B';

        foreach (array('B','KB','MB','GB') as $size) {
            if ($bytes < 1024) {
                break;
            }

            $bytes /= 1024;
        }

        return sprintf("%.0f %s", $bytes, $size);
    }
}

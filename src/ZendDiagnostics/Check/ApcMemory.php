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
class ApcMemory extends AbstractMemoryCheck
{
    /**
     * APC information
     *
     * @var array
     */
    private $apcInfo;

    /**
     * Perform the check
     *
     * @see \ZendDiagnostics\Check\CheckInterface::check()     *
     * @return Failure|Skip|Success|Warning
     */
    public function check()
    {
        if (!ini_get('apc.enabled')) {
            return new Skip('APC has not been enabled or installed.');
        }

        if (php_sapi_name() == 'cli' && !ini_get('apc.enabled_cli')) {
            return new Skip('APC has not been enabled in CLI.');
        }

        if (!function_exists('apc_sma_info')) {
            return new Warning('APC extension is not available');
        }

        if (!$this->apcInfo = apc_sma_info()) {
            return new Warning('Unable to retrieve APC memory status information.');
        }

        return parent::check();
    }

    /**
     * Returns the total memory in bytes
     *
     * @return int
     */
    protected function getTotalMemory()
    {
        return $this->apcInfo['num_seg'] * $this->apcInfo['seg_size'];
    }

    /**
     * Returns the used memory in bytes
     *
     * @return int
     */
    protected function getUsedMemory()
    {
        return $this->getTotalMemory() - $this->apcInfo['avail_mem'];
    }
}

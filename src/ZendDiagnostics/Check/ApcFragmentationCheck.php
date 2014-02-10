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
 * Checks to see if the APC fragmentation is below warning/critical thresholds
 *
 * APC memory logic borrowed from APC project:
 *      https://github.com/php/pecl-caching-apc/blob/master/apc.php
 *      authors:   Ralf Becker <beckerr@php.net>, Rasmus Lerdorf <rasmus@php.net>, Ilia Alshanetsky <ilia@prohost.org>
 *      license:   The PHP License, version 3.01
 *      copyright: Copyright (c) 2006-2011 The PHP Group
 */
class ApcFragmentationCheck implements CheckInterface
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
        $nseg = $freeseg = $fragsize = $freetotal = 0;

        for ($i = 0; $i < $info['num_seg']; $i++) {
            $ptr = 0;
            foreach ($info['block_lists'][$i] as $block) {
                if ($block['offset'] != $ptr) {
                    ++$nseg;
                }

                $ptr = $block['offset'] + $block['size'];

                /* Only consider blocks <5M for the fragmentation % */
                if ($block['size'] < (5 * 1024 * 1024)) {
                    $fragsize += $block['size'];
                }

                $freetotal += $block['size'];
            }

            $freeseg += count($info['block_lists'][$i]);
        }

        $fragPercent = 0;

        if ($freeseg > 1) {
            $fragPercent = ($fragsize / $freetotal) * 100;
        }

        $message = sprintf('%.0f%% memory fragmentation.', $fragPercent);

        if ($fragPercent > $this->criticalThreshold) {
            return new Failure($message);
        }

        if ($fragPercent > $this->warningThreshold) {
            return new Warning($message);
        }

        return new Success();
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return 'APC Fragmentation';
    }

}

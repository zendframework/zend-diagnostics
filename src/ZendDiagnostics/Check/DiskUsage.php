<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Result\Warning;

/**
 * Checks to see if the disk usage is below warning/critical percent thresholds
 */
class DiskUsage implements CheckInterface
{
    protected $warningThreshold;
    protected $criticalThreshold;
    protected $path;

    /**
     * @param int $warningThreshold
     * @param int $criticalThreshold
     * @param int $path
     */
    public function __construct($warningThreshold, $criticalThreshold, $path)
    {
        $this->warningThreshold = (int) $warningThreshold;
        $this->criticalThreshold = (int) $criticalThreshold;
        $this->path = $path;
    }

    /**
     * Perform the check
     *
     * @return Failure|Success|Warning
     */
    public function check()
    {
        $df = disk_free_space($this->path);
        $dt = disk_total_space($this->path);
        $du = $dt - $df;
        $dp = ($du / $dt) * 100;

        if ($dp >= $this->criticalThreshold) {
            return new Failure(sprintf('Disc usage too high: %2d percentage.', $dp));
        }

        if ($dp >= $this->warningThreshold) {
            return new Warning(sprintf('Disc usage high: %2d percentage.', $dp));
        }

        return new Success();
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Disc Usage';
    }
}

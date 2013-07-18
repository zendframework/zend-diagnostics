<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;

class DiskUsage extends AbstractCheck
{
    /**
     * Maximum disk usage in percentage
     *
     * @var int
     */
    protected $maxDiskUsage;

    /**
     * Path that should be checked
     *
     * @var string
     */
    protected $path;

    public function __construct($maxDiskUsage, $path)
    {
        $this->maxDiskUsage = (int) $maxDiskUsage;
        $this->path = $path;
    }

    public function check()
    {
        $df = disk_free_space($this->path);
        $dt = disk_total_space($this->path);
        $du = $dt - $df;
        $dp = ($du / $dt) * 100;

        if ($dp >= $this->maxDiskUsage) {
            return new Failure(sprintf('Disk usage too high: %2d percentage.', $dp));
        }

        return new Success();
    }
}
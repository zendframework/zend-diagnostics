<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;

class DiscUsageCheck extends AbstractCheck
{
    /**
     * Maximum disc usage in percentage
     *
     * @var int
     */
    protected $maxDiscUsage;

    /**
     * Path that should be checked
     *
     * @var string
     */
    protected $path;

    public function __construct($maxDiscUsage, $path)
    {
        $this->maxDiscUsage = (int) $maxDiscUsage;
        $this->path = $path;
    }

    public function check()
    {
        $df = disk_free_space($this->path);
        $dt = disk_total_space($this->path);
        $du = $dt - $df;
        $dp = ($du / $dt) * 100;

        if ($dp >= $this->maxDiscUsage) {
            return new Failure(sprintf('Disc usage too high: %2d percentage.', $dp));
        }

        return new Success();
    }

    public function getName()
    {
        return "Disc Usage Health";
    }
}
<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2013-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

namespace ZendDiagnosticsTest;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ZendDiagnostics\Check\DiskUsage;
use ZendDiagnostics\Result\FailureInterface;
use ZendDiagnostics\Result\SuccessInterface;
use ZendDiagnostics\Result\WarningInterface;

class DiskUsageTest extends TestCase
{
    /**
     * @dataProvider invalidArgumentProvider
     */
    public function testInvalidArguments($warningThreshold, $criticalThreshold, $path)
    {
        $this->expectException(InvalidArgumentException::class);
        new DiskUsage($warningThreshold, $criticalThreshold, $path);
    }

    public function testCheck()
    {
        $df = disk_free_space($this->getTempDir());
        $dt = disk_total_space($this->getTempDir());
        $du = $dt - $df;
        $dp = ($du / $dt) * 100;

        $check = new DiskUsage($dp + 1, $dp + 2, $this->getTempDir());
        $result = $check->check();

        $this->assertInstanceof(SuccessInterface::class, $result);

        $check = new DiskUsage($dp - 1, 100, $this->getTempDir());
        $result = $check->check();

        $this->assertInstanceof(WarningInterface::class, $result);

        $check = new DiskUsage(0, $dp - 1, $this->getTempDir());
        $result = $check->check();

        $this->assertInstanceof(FailureInterface::class, $result);
    }

    public function invalidArgumentProvider()
    {
        return [
            ['Not an integer.', 'Not an integer.', $this->getTempDir()],
            [5, 'Not an integer.', $this->getTempDir()],
            ['Not an integer.', 100, $this->getTempDir()],
            [5, 100, []],
            [-10, 100, $this->getTempDir()],
            [105, 100, $this->getTempDir()],
            [10, -10, $this->getTempDir()],
            [10, 105, $this->getTempDir()]
        ];
    }

    protected function getTempDir()
    {
        // try to retrieve tmp dir
        $tmp = sys_get_temp_dir();

        // make sure there is any space there
        if (! $tmp || ! is_writable($tmp) || ! disk_free_space($tmp)) {
            $this->markTestSkipped(
                'Cannot find a writable temporary directory with free disk space for Check\DiskUsage tests'
            );
        }

        return $tmp;
    }
}

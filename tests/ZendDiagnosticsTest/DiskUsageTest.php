<?php

namespace ZendDiagnosticsTest;

use ZendDiagnostics\Check\DiskUsage;

class DiskUsageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider InvalidArgumentProvider
     */
    public function testInvalidArguments($warningThreshold, $criticalThreshold, $path)
    {
        $this->setExpectedException('InvalidArgumentException');
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

        $this->assertInstanceof('ZendDiagnostics\Result\SuccessInterface', $result);

        $check = new DiskUsage($dp - 1, 100, $this->getTempDir());
        $result = $check->check();

        $this->assertInstanceof('ZendDiagnostics\Result\WarningInterface', $result);

        $check = new DiskUsage(0, $dp - 1, $this->getTempDir());
        $result = $check->check();

        $this->assertInstanceof('ZendDiagnostics\Result\FailureInterface', $result);
    }

    public function InvalidArgumentProvider()
    {
        return array(
            array('Not an integer.', 'Not an integer.', $this->getTempDir()),
            array(5, 'Not an integer.', $this->getTempDir()),
            array('Not an integer.', 100, $this->getTempDir()),
            array(5, 100, array()),
            array(-10, 100, $this->getTempDir()),
            array(105, 100, $this->getTempDir()),
            array(10, -10, $this->getTempDir()),
            array(10, 105, $this->getTempDir())
        );
    }

    protected function getTempDir()
    {
        // try to retrieve tmp dir
        $tmp = sys_get_temp_dir();

        // make sure there is any space there
        if (!$tmp || !is_writable($tmp) || !disk_free_space($tmp)) {
            $this->markTestSkipped(
                'Cannot find a writable temporary directory with free disk space for Check\DiskUsage tests'
            );
        }

        return $tmp;
    }
}

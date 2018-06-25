<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2013-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

namespace ZendDiagnosticsTest;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ZendDiagnostics\Check\DiskFree;
use ZendDiagnostics\Result\FailureInterface;
use ZendDiagnostics\Result\SuccessInterface;
use ZendDiagnostics\Result\WarningInterface;

/**
 * Bytes conversion tests borrowed from Jerity project:
 *     https://github.com/jerity/jerity/blob/master/tests/Util/NumberTest.php
 *     authors:   Dave Ingram <dave@dmi.me.uk>, Nick Pope <nick@nickpope.me.uk>
 *     license:   http://creativecommons.org/licenses/BSD/ CC-BSD
 *     copyright: Copyright (c) 2010, Dave Ingram, Nick Pope

 */
class DiskFreeTest extends TestCase
{
    public static function stringToBytesProvider()
    {
        $values = [1, 10, 12.34];
        $prefix_symbol = ['', 'k', 'M', 'G', 'T', 'P', 'E', 'Z', 'Ki', 'Mi', 'Gi', 'Ti', 'Pi', 'Ei', 'K', 'M', 'G'];
        $prefix_name = [
            '', 'kilo', 'mega', 'giga', 'tera', 'peta',
            'exa', 'zetta', 'kibi', 'mebi', 'gibi', 'tebi',
            'pebi', 'exbi', 'kilo', 'mega', 'giga',
        ];
        $multiplier_base = [10, 10, 10, 10, 10, 10, 10, 10, 2, 2, 2, 2, 2, 2, 2, 2, 2];
        $multiplier_exp = [0, 3, 6, 9, 12, 15, 18, 21, 10, 20, 30, 40, 50, 60, 10, 20, 30];
        $data = [];
        foreach ($values as $value) {
            for ($i = 0; $i < count($prefix_symbol); $i++) {
                $v = $value * pow($multiplier_base[$i], $multiplier_exp[$i]);
                $jedec = ($i >= count($prefix_symbol) - 4);
                $data[] = ["{$value}{$prefix_symbol[$i]}B", $jedec, $v];
                $data[] = ["{$value}{$prefix_symbol[$i]}Bps", $jedec, $v];
                $data[] = ["{$value}{$prefix_symbol[$i]}b", $jedec, $v / 8];
                $data[] = ["{$value}{$prefix_symbol[$i]}bps", $jedec, $v / 8];
                $data[] = ["{$value} {$prefix_symbol[$i]}B", $jedec, $v];
                $data[] = ["{$value} {$prefix_symbol[$i]}Bps", $jedec, $v];
                $data[] = ["{$value} {$prefix_symbol[$i]}b", $jedec, $v / 8];
                $data[] = ["{$value} {$prefix_symbol[$i]}bps", $jedec, $v / 8];
                $postfix = ($value == 1 ? '' : 's');
                $data[] = ["{$value}{$prefix_name[$i]}byte{$postfix}", $jedec, $v];
                $data[] = ["{$value}{$prefix_name[$i]}bit{$postfix}", $jedec, $v / 8];
                $data[] = ["{$value} {$prefix_name[$i]}byte{$postfix}", $jedec, $v];
                $data[] = ["{$value} {$prefix_name[$i]}bit{$postfix}", $jedec, $v / 8];
            }
        }

        return $data;
    }

    public static function stringToBytesExceptionProvider()
    {
        return [
            ['Not a size.', false, InvalidArgumentException::class],
            ['Not a size.', true, InvalidArgumentException::class],
            ['1 KB', false, InvalidArgumentException::class],
            ['1 TB', true, InvalidArgumentException::class],
        ];
    }

    public static function bytesToStringProvider()
    {
        return [
            [1125899906842624, 5, '1 PiB'],
            [1099511627776,    5, '1 TiB'],
            [1073741824,       5, '1 GiB'],
            [1048576,          5, '1 MiB'],
            [1024,             5, '1 KiB'],
            [999,              5, '999 B'],

            [1351079888211148, 0, '1 PiB'],
            [1319413953331,    0, '1 TiB'],
            [1288490190,       0, '1 GiB'],
            [1258291,          0, '1 MiB'],
            [1228,             0, '1 KiB'],
            [999,              0, '999 B'],

            [1351079888211148, 1, '1.2 PiB'],
            [1319413953331,    1, '1.2 TiB'],
            [1288490190,       1, '1.2 GiB'],
            [1258291,          1, '1.2 MiB'],
            [1228,             1, '1.2 KiB'],
            [999,              1, '999 B'],
        ];
    }

    /**
     * @dataProvider  stringToBytesProvider
     */
    public function testStringToBytes($a, $b, $c)
    {
        $this->assertEquals(DiskFree::stringToBytes($a, $b), $c);
    }

    /**
     * @dataProvider  stringToBytesExceptionProvider
     */
    public function testStringToBytesException($a, $b, $c)
    {
        $this->expectException($c);
        DiskFree::stringToBytes($a, $b);
    }

    /**
     * @dataProvider  bytesToStringProvider
     */
    public function testBytesToString($bytes, $precision, $string)
    {
        $this->assertEquals($string, DiskFree::bytesToString($bytes, $precision));
    }

    public function testJitFreeSpace()
    {
        $tmp = $this->getTempDir();
        $freeRightNow = disk_free_space($tmp);
        $check = new DiskFree($freeRightNow * 0.5, $tmp);
        $result = $check->check();

        $this->assertInstanceof(SuccessInterface::class, $result);

        $freeRightNow = disk_free_space($tmp);
        $check = new DiskFree($freeRightNow + 1073741824, $tmp);
        $result = $check->check();

        $this->assertInstanceof(FailureInterface::class, $result);
    }

    public function testSpaceWithStringConversion()
    {
        $tmp = $this->getTempDir();
        $freeRightNow = disk_free_space($tmp);
        if ($freeRightNow < 1024) {
            $this->markTestSkipped('There is less that 1024 bytes free in temp dir');
        }

        // give some margin of error
        $freeRightNow *= 0.9;
        $freeRightNowString = DiskFree::bytesToString($freeRightNow);
        $check = new DiskFree($freeRightNowString, $tmp);
        $result = $check->check();

        $this->assertInstanceof(SuccessInterface::class, $result);
    }

    public function testInvalidPathShouldReturnWarning()
    {
        $check = new DiskFree(1024, __DIR__ . '/someImprobablePath99999999999999999');
        $result = $check->check();
        $this->assertInstanceof(WarningInterface::class, $result);
    }

    public function testInvalidSizeParamThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        new DiskFree(-1, $this->getTempDir());
    }

    public function testInvalidSizeParamThrowsException2()
    {
        $this->expectException(InvalidArgumentException::class);
        new DiskFree(-1, $this->getTempDir());
    }

    public function testInvalidSizeParamThrowsException3()
    {
        $this->expectException(InvalidArgumentException::class);
        new DiskFree([], $this->getTempDir());
    }

    public function testInvalidPathParamThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        new DiskFree(1024, 100);
    }

    protected function getTempDir()
    {
        // try to retrieve tmp dir
        $tmp = sys_get_temp_dir();

        // make sure there is any space there
        if (! $tmp || ! is_writable($tmp) || ! disk_free_space($tmp)) {
            $this->markTestSkipped(
                'Cannot find a writable temporary directory with free disk space for Check\DiskFree tests'
            );
        }

        return $tmp;
    }
}

<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2013-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

namespace ZendDiagnosticsTest;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ZendDiagnostics\Check\ApcFragmentation;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class ApcFragmentationTest extends TestCase
{
    /**
     * @dataProvider invalidArgumentProvider
     */
    public function testInvalidArguments($warningThreshold, $criticalThreshold)
    {
        $this->expectException(InvalidArgumentException::class);
        new ApcFragmentation($warningThreshold, $criticalThreshold);
    }

    public function invalidArgumentProvider()
    {
        return [
            ['Not an integer.', 'Not an integer.'],
            [5, 'Not an integer.'],
            ['Not an integer.', 100],
            [-10, 100],
            [105, 100],
            [10, -10],
            [10, 105]
        ];
    }
}

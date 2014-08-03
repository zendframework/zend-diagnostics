<?php

namespace ZendDiagnosticsTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class AbstractMemoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider InvalidArgumentProvider
     */
    public function testInvalidArguments($warningThreshold, $criticalThreshold)
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->createCheck($warningThreshold, $criticalThreshold);
    }

    public function InvalidArgumentProvider()
    {
        return array(
            array('Not an integer.', 'Not an integer.'),
            array(5, 'Not an integer.'),
            array('Not an integer.', 100),
            array(-10, 100),
            array(105, 100),
            array(10, -10),
            array(10, 105)
        );
    }

    abstract protected function createCheck($warningThreshold, $criticalThreshold);
}

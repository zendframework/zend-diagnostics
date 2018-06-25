<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2013-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

namespace ZendDiagnosticsTest;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use PHPUnit\Framework\TestCase;
use ZendDiagnostics\Check\DoctrineMigration;
use ZendDiagnostics\Result\FailureInterface;
use ZendDiagnostics\Result\SuccessInterface;

class DoctrineMigrationTest extends TestCase
{
    public function testEverythingMigrated()
    {
        $configuration = $this->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configuration
            ->expects($this->once())
            ->method('getAvailableVersions')
            ->will($this->returnValue(['Version1', 'Version2']));

        $configuration
            ->expects($this->once())
            ->method('getMigratedVersions')
            ->will($this->returnValue(['Version1', 'Version2']));

        $check = new DoctrineMigration($configuration);
        $result = $check->check();

        $this->assertInstanceof(SuccessInterface::class, $result);
    }

    public function testNotAllMigrationsMigrated()
    {
        $configuration = $this->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configuration
            ->expects($this->once())
            ->method('getAvailableVersions')
            ->will($this->returnValue(['Version1', 'Version2']));

        $configuration
            ->expects($this->once())
            ->method('getMigratedVersions')
            ->will($this->returnValue(['Version1']));

        $check = new DoctrineMigration($configuration);
        $result = $check->check();

        $this->assertInstanceof(FailureInterface::class, $result);
    }

    public function testNoExistingMigrationMigrated()
    {
        $configuration = $this->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configuration
            ->expects($this->once())
            ->method('getAvailableVersions')
            ->will($this->returnValue(['Version1']));

        $configuration
            ->expects($this->once())
            ->method('getMigratedVersions')
            ->will($this->returnValue(['Version1', 'Version2']));

        $check = new DoctrineMigration($configuration);
        $result = $check->check();

        $this->assertInstanceof(FailureInterface::class, $result);
    }
}

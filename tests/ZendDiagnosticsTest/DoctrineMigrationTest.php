<?php

namespace ZendDiagnosticsTest;

use ZendDiagnostics\Check\DoctrineMigration;

class DoctrineMigrationTest extends \PHPUnit_Framework_TestCase
{
    public function testEverythingMigrated()
    {
        $configuration = $this->getMockBuilder('Doctrine\DBAL\Migrations\Configuration\Configuration')
            ->disableOriginalConstructor()
            ->getMock();

        $configuration
            ->expects($this->once())
            ->method('getAvailableVersions')
            ->will($this->returnValue(array('Version1', 'Version2')));

        $configuration
            ->expects($this->once())
            ->method('getMigratedVersions')
            ->will($this->returnValue(array('Version1', 'Version2')));

        $check = new DoctrineMigration($configuration);
        $result = $check->check();

        $this->assertInstanceof('ZendDiagnostics\Result\SuccessInterface', $result);
    }

    public function testNotAllMigrationsMigrated()
    {
        $configuration = $this->getMockBuilder('Doctrine\DBAL\Migrations\Configuration\Configuration')
            ->disableOriginalConstructor()
            ->getMock();

        $configuration
            ->expects($this->once())
            ->method('getAvailableVersions')
            ->will($this->returnValue(array('Version1', 'Version2')));

        $configuration
            ->expects($this->once())
            ->method('getMigratedVersions')
            ->will($this->returnValue(array('Version1')));

        $check = new DoctrineMigration($configuration);
        $result = $check->check();

        $this->assertInstanceof('ZendDiagnostics\Result\FailureInterface', $result);
    }

    public function testNoExistingMigrationMigrated()
    {
        $configuration = $this->getMockBuilder('Doctrine\DBAL\Migrations\Configuration\Configuration')
            ->disableOriginalConstructor()
            ->getMock();

        $configuration
            ->expects($this->once())
            ->method('getAvailableVersions')
            ->will($this->returnValue(array('Version1')));

        $configuration
            ->expects($this->once())
            ->method('getMigratedVersions')
            ->will($this->returnValue(array('Version1', 'Version2')));

        $check = new DoctrineMigration($configuration);
        $result = $check->check();

        $this->assertInstanceof('ZendDiagnostics\Result\FailureInterface', $result);
    }
}

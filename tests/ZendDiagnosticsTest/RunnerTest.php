<?php
namespace ZendDiagnosticsTest;

use ZendDiagnosticsTest\Check\AlwaysSuccess;

class RunnerTest extends \PHPUnit_Framework_TestCase
{
    protected $runner;

    public function setUp()
    {

    }

    public function testRunner(){
        $alwaysSuccess = new AlwaysSuccess();
        $this->assertInstanceOf('\ZendDiagnostics\Result\Success', $alwaysSuccess->check());
    }
}
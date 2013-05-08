<?php
namespace PHPDiagnostics\Check;

use PHPDiagnostics\Result\ResultInterface;

interface CheckInterface
{
    /**
     * @return ResultInterface
     */
    public function check();
}
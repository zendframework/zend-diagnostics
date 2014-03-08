<?php

namespace ZendDiagnosticsTest\TestAsset\Check;

use SensioLabs\Security\SecurityChecker;
use ZendDiagnostics\Check\SecurityAdvisory as BaseCheck;

class SecurityAdvisory extends BaseCheck
{
    /**
     * @param SecurityChecker $securityChecker
     */
    public function setSecurityChecker(SecurityChecker $securityChecker)
    {
        $this->securityChecker = $securityChecker;
    }
}

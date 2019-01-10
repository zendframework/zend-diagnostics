<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2013-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

namespace ZendDiagnostics\Check;

use Doctrine\Migrations\Configuration\Configuration;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\ResultInterface;
use ZendDiagnostics\Result\Success;

class DoctrineMigration extends AbstractCheck
{
    /**
     * @var Configuration
     */
    private $migrationConfiguration;

    /**
     * @param Configuration $migrationConfiguration
     */
    public function __construct(Configuration $migrationConfiguration)
    {
        $this->migrationConfiguration = $migrationConfiguration;
    }

    /**
     * Perform the actual check and return a ResultInterface
     *
     * @return ResultInterface
     */
    public function check()
    {
        $availableVersions = $this->migrationConfiguration->getAvailableVersions();
        $migratedVersions = $this->migrationConfiguration->getMigratedVersions();

        $notMigratedVersions = array_diff($availableVersions, $migratedVersions);
        if (! empty($notMigratedVersions)) {
            return new Failure('Not all migrations applied', $notMigratedVersions);
        }

        $notAvailableVersion = array_diff($migratedVersions, $availableVersions);
        if (! empty($notAvailableVersion)) {
            return new Failure('Migrations applied which are not available', $notMigratedVersions);
        }

        return new Success();
    }
}

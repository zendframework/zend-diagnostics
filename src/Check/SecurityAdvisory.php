<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2013-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

namespace ZendDiagnostics\Check;

use InvalidArgumentException;
use SensioLabs\Security\Result;
use SensioLabs\Security\SecurityChecker;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Result\Warning;

/**
 * Checks installed composer dependencies against the SensioLabs Security Advisory database.
 */
class SecurityAdvisory extends AbstractCheck
{
    /**
     * @var string
     */
    protected $lockFilePath;

    /**
     * @var SecurityChecker
     */
    protected $securityChecker;

    /**
     * @param  string $lockFilePath Path to composer.lock
     * @throws InvalidArgumentException
     */
    public function __construct($lockFilePath = null)
    {
        if (! class_exists('SensioLabs\Security\SecurityChecker')) {
            throw new InvalidArgumentException(sprintf(
                'Unable to find "%s" class. Please install "%s" library to use this Check.',
                'SensioLabs\Security\SecurityChecker',
                'sensiolabs/security-checker'
            ));
        }

        if (! class_exists('SensioLabs\Security\Result')) {
            throw new InvalidArgumentException(
                'You must have sensiolabs/security-checker version 5+ to use this check.'
            );
        }

        if (! $lockFilePath) {
            if (! file_exists('composer.lock')) {
                throw new InvalidArgumentException(
                    'You have not provided lock file path and there is no "composer.lock" file in current directory.'
                );
            }

            $lockFilePath = getcwd() . DIRECTORY_SEPARATOR . 'composer.lock';
        } elseif (! is_scalar($lockFilePath)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid argument 2 provided for SecurityAdvisory check - expected file name (string) , got %s',
                gettype($lockFilePath)
            ));
        }

        $this->lockFilePath    = $lockFilePath;
        $this->securityChecker = new SecurityChecker();
    }

    public function check()
    {
        try {
            if (! file_exists($this->lockFilePath) || ! is_file($this->lockFilePath)) {
                return new Failure(sprintf(
                    'Cannot find composer lock file at %s',
                    $this->lockFilePath
                ), $this->lockFilePath);
            } elseif (! is_readable($this->lockFilePath)) {
                return new Failure(sprintf(
                    'Cannot open composer lock file at %s',
                    $this->lockFilePath
                ), $this->lockFilePath);
            }

            $advisories = $this->securityChecker->check($this->lockFilePath, 'json');

            $advisories = @json_decode((string) $advisories, true);

            if (! is_array($advisories)) {
                return new Warning('Could not parse response from security advisory service.');
            }

            if (! empty($advisories)) {
                return new Failure(sprintf(
                    'Found security advisories for %u composer package(s)',
                    count($advisories)
                ), $advisories);
            }
        } catch (\Exception $e) {
            return new Warning($e->getMessage());
        }

        return new Success(sprintf(
            'There are currently no security advisories for packages specified in %s',
            $this->lockFilePath
        ));
    }
}

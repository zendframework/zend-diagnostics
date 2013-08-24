<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use InvalidArgumentException;
use SensioLabs\Security\SecurityChecker;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Result\Warning;

/**
 * Checks installed dependencies against the SensioLabs Security Advisory database.
 *
 * @author Baldur Rensch <brensch@gmail.com>
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
     * @param  SecurityChecker           $securityChecker An instance of SecurityChecker
     * @param  string                    $lockFilePath    Path to composer.lock
     * @throws \InvalidArgumentException
     */
    public function __construct(SecurityChecker $securityChecker, $lockFilePath)
    {
        if (empty($lockFilePath) || !is_scalar($lockFilePath)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid argument 2 provided for SecurityAdvisory check - expected file name (string) , got %s',
                gettype($lockFilePath)
            ));
        }

        $this->lockFilePath = $lockFilePath;
        $this->securityChecker = $securityChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function check()
    {
        try {
            if (!file_exists($this->lockFilePath) || !is_file($this->lockFilePath)) {
                return new Failure(sprintf(
                    'Cannot find composer lock file at %s',
                    $this->lockFilePath
                ), $this->lockFilePath);
            } elseif (!is_readable($this->lockFilePath)) {
                return new Failure(sprintf(
                    'Cannot open composer lock file at %s',
                    $this->lockFilePath
                ), $this->lockFilePath);
            }

            $advisories = $this->securityChecker->check($this->lockFilePath, 'json');
            $advisories = @json_decode($advisories);

            if (null === $advisories) {
                return new Warning('Could not parse response from security advisory service.');
            }

            if (!empty($advisories)) {
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

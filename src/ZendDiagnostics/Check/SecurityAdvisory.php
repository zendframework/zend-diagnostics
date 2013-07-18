<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

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
     * @param SecurityChecker $securityChecker
     * @param string $lockFilePath
     */
    public function __construct(SecurityChecker $securityChecker, $lockFilePath)
    {
        $this->securityChecker = $securityChecker;
        $this->lockFilePath = $lockFilePath;
    }

    /**
     * {@inheritdoc}
     */
    public function check()
    {
        try {
            if (!file_exists($this->lockFilePath)) {
                return new Failure("No composer lock file found");
            }

            $advisories = $this->securityChecker->check($this->lockFilePath, 'json');
            $advisories = @json_decode($advisories);
            if (false  === $advisories) {
                return new Warning('Could not parse response from security advisory service');
            }
            if (!empty($advisories)) {
                return new Warning('Advisories for ' . count($advisories) . ' packages');
            }
        } catch (\Exception $e) {
            return new Warning(''. $e->getMessage());
        }

        return new Success();
    }
}
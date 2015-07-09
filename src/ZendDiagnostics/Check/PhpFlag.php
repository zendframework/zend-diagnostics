<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use InvalidArgumentException;
use Traversable;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;

/**
 * Make sure given PHP flag is turned on or off in php.ini
 *
 * This test accepts a string or array of strings for php flags
 */
class PhpFlag extends AbstractCheck implements CheckInterface
{
    /**
     * @var array
     */
    protected $settings;

    /**
     * @var bool
     */
    protected $expectedValue;

    /**
     * @param string|array|traversable $settingName   PHP setting names to check.
     * @param bool                     $expectedValue true or false
     * @throws InvalidArgumentException
     */
    public function __construct($settingName, $expectedValue)
    {
        if (is_object($settingName)) {
            if (!$settingName instanceof Traversable) {
                throw new InvalidArgumentException(
                    'Expected setting name as string, array or traversable, got ' . get_class($settingName)
                );
            }
            $this->settings = iterator_to_array($settingName);
        } elseif (!is_scalar($settingName)) {
            if (!is_array($settingName)) {
                throw new InvalidArgumentException(
                    'Expected setting name as string, array or traversable, got ' . gettype($settingName)
                );
            }
            $this->settings = $settingName;
        } else {
            $this->settings = array($settingName);
        }

        if (!is_scalar($expectedValue)) {
            throw new InvalidArgumentException(
                'Expected expected value, expected boolean, got ' . gettype($expectedValue)
            );
        }

        $this->expectedValue = (bool)$expectedValue;
    }

    /**
     * Perform the check
     *
     * @see \ZendDiagnostics\Check\CheckInterface::check()
     * @return Success|Failure
     */
    public function check()
    {
        $failures = array();

        foreach ($this->settings as $name) {
            if (ini_get($name) != $this->expectedValue) {
                $failures[] = $name;
            }
        }

        if (count($failures) > 1) {
            return new Failure(
                join(', ', $failures) .
                ' are expected to be ' .
                ($this->expectedValue ? 'enabled' : 'disabled')
            );
        } elseif (count($failures)) {
            return new Failure(
                $failures[0] .
                ' is expected to be ' .
                ($this->expectedValue ? 'enabled' : 'disabled')
            );
        }

        if (count($this->settings) > 1) {
            return new Success(
                join(', ', $this->settings) .
                ' are all ' .
                ($this->expectedValue ? 'enabled' : 'disabled')
            );
        } else {
            return new Success(
                $this->settings[0] .
                ' is ' .
                ($this->expectedValue ? 'enabled' : 'disabled')
            );
        }
    }
}

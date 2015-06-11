<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Runner;

use ArrayObject;
use ErrorException;
use InvalidArgumentException;
use RuntimeException;
use BadMethodCallException;
use Traversable;
use ZendDiagnostics\Check\CheckCollectionInterface;
use ZendDiagnostics\Check\CheckInterface;
use ZendDiagnostics\Result\Collection as ResultsCollection;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\FailureInterface;
use ZendDiagnostics\Result\ResultInterface;
use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Result\Warning;
use ZendDiagnostics\Runner\Reporter\ReporterInterface as Reporter;

/**
 * Diagnostics Checks runner.
 *
 * A Runner takes one or more Checks and runs them in sequence. One or more Reporters can be attached to
 * display the progress and results of checks.
 */
class Runner
{
    /**
     * An array of Checks to run.
     *
     * @var ArrayObject
     */
    protected $checks;

    /**
     * An array of reporters.
     *
     * @var array|Traversable
     */
    protected $reporters = array();

    /**
     * The results from last run()
     *
     * @var ResultsCollection
     */
    protected $lastResults;

    /**
     * Should the run stop on first failure.
     *
     * @var bool
     */
    protected $breakOnFailure = false;

    /**
     * Severity of error that will result in a test failing. Defaults to:
     *  E_WARNING|E_PARSE|E_USER_ERROR|E_USER_WARNING|E_RECOVERABLE_ERROR
     *
     * @var int
     */
    protected $catchErrorSeverity = 4870;

    /**
     * Create new instance of Runner, optionally providing configuration and initial collection of Checks.
     *
     * @param null|array|Traversable $config   Config settings.
     * @param null|array|Traversable $checks   A collection of Checks to run.
     * @param null|Reporter          $reporter Reporter instance to use
     */
    public function __construct($config = null, $checks = null, Reporter $reporter = null)
    {
        if ($config !== null) {
            $this->setConfig($config);
        }

        $this->checks = new ArrayObject();

        if ($checks !== null) {
            $this->addChecks($checks);
        }

        if ($reporter !== null) {
            $this->addReporter($reporter);
        }
    }

    /**
     * Run all Checks and return a Result\Collection for every check.
     *
     * @param  string|null       $checkAlias An alias of Check instance to run, or null to run all checks.
     * @return ResultsCollection The result of running Checks
     */
    public function run($checkAlias = null)
    {
        $results = new ResultsCollection();

        $checks = $checkAlias ? new ArrayObject(array($checkAlias => $this->getCheck($checkAlias))) : $this->getChecks();

        // Trigger START event
        $this->triggerReporters('onStart', $checks, $this->getConfig());

        // Iterate over all Checks
        foreach ($checks as $alias => $check) {
            /* @var $check CheckInterface */

            // Skip Checking if BEFORE_RUN returned false or has been stopped
            if (!$this->triggerReporters('onBeforeRun', $check, $alias)) {
                continue;
            }

            // Run the Check!
            try {
                $this->startErrorHandler();
                $result = $check->check();
                $this->stopErrorHandler();
            } catch (ErrorException $e) {
                $result = new Failure(
                    'PHP ' . static::getSeverityDescription($e->getSeverity()) . ': ' . $e->getMessage(),
                    $e
                );
            } catch (\Exception $e) {
                $this->stopErrorHandler();
                $result = new Failure(
                    'Uncaught ' . get_class($e) . ': ' . $e->getMessage(),
                    $e
                );
            }

            // Check if we've received a Result object
            if (is_object($result)) {
                if (!$result instanceof ResultInterface) {
                    $result = new Failure(
                        'Test returned unknown object ' . get_class($result),
                        $result
                    );
                }
            } elseif (is_bool($result)) {
                // Interpret boolean as a failure or success
                $result = $result ? new Success() : new Failure();
            } elseif (is_scalar($result)) {
                // Convert scalars to a warning
                $result = new Warning('Test returned unexpected '.gettype($result), $result);
            } else {
                // Otherwise interpret as failure
                $result = new Failure(
                    'Test returned unknown result of type ' . gettype($result),
                    $result
                );
            }

            // Save Check result
            $results[$check] = $result;

            // Stop Checking if AFTER_RUN returned false
            if (!$this->triggerReporters('onAfterRun', $check, $result, $alias)) {
                $this->triggerReporters('onStop', $results);
                break;
            }

            // Stop Checking on first failure
            if ($this->breakOnFailure && $result instanceof FailureInterface) {
                $this->triggerReporters('onStop', $results);
                break;
            }
        }

        // trigger FINISH event
        $this->triggerReporters('onFinish', $results);

        $this->lastResults = $results;

        return $results;
    }

    /**
     * Set config values from an array.
     *
     * @param  array|Traversable        $config
     * @throws InvalidArgumentException
     * @throws BadMethodCallException
     * @return $this
     */
    public function setConfig($config)
    {
        if (!is_array($config) && !$config instanceof Traversable) {
            throw new InvalidArgumentException('Expected an array or Traversable as config for Runner.');
        }

        foreach ($config as $key => $val) {
            $methodName = 'set' . implode(array_map(function ($value) {
                return ucfirst($value);
            }, explode('_', $key)));

            if (!is_callable(array($this, $methodName))) {
                throw new BadMethodCallException('Unknown config parameter ' . $key);
            }

            $this->$methodName($val);
        }

        return $this;
    }

    /**
     * Get current config.
     *
     * @return array
     */
    public function getConfig()
    {
        return array(
            'break_on_failure'     => $this->getBreakOnFailure(),
            'catch_error_severity' => $this->getCatchErrorSeverity(),
        );
    }

    /**
     * Add diagnostic Check to run.
     *
     * @param CheckInterface $check
     * @param string|null    $alias
     */
    public function addCheck(CheckInterface $check, $alias = null)
    {
        $alias = is_string($alias) ? $alias : count($this->checks);
        $this->checks[$alias] = $check;
    }

    /**
     * Add multiple Checks from an array, Traversable or CheckCollectionInterface.
     *
     * @param  array|Traversable|CheckCollectionInterface $checks
     * @throws InvalidArgumentException
     */
    public function addChecks($checks)
    {
        if ($checks instanceof CheckCollectionInterface) {
            $checks = $checks->getChecks();
        }

        if (!is_array($checks) && !$checks instanceof Traversable) {
            $what = is_object($checks) ? 'object of class ' . get_class($checks) : gettype($checks);
            throw new InvalidArgumentException('Cannot add Checks from ' . $what . ' - expected array or Traversable');
        }

        foreach ($checks as $key => $check) {
            if (!$check instanceof CheckInterface) {
                $what = is_object($check) ? 'object of class ' . get_class($check) : gettype($check);
                throw new InvalidArgumentException(
                    'Cannot use ' . $what . ' as Check - expected ZendDiagnostics\Check\CheckInterface'
                );
            }
            $alias = is_string($key) ? $key : null;
            $this->addCheck($check, $alias);
        }
    }

    /**
     * Add new reporter.
     *
     * @param Reporter $reporter
     */
    public function addReporter(Reporter $reporter)
    {
        $this->reporters[] = $reporter;
    }

    /**
     * Remove previously attached reporter.
     *
     * @param Reporter $reporter
     */
    public function removeReporter(Reporter $reporter)
    {
        $this->reporters = array_filter($this->reporters, function (Reporter $r) use (&$reporter) {
            return $r !== $reporter;
        });
    }

    /**
     * Get a single Check instance by its alias name
     *
     * @param  string            $alias Alias name of the Check instance to retrieve
     * @throws \RuntimeException
     * @return CheckInterface
     */
    public function getCheck($alias)
    {
        if (empty($this->checks[$alias])) {
            throw new RuntimeException(sprintf(
                'There is no Check instance with an alias of "%s"',
                $alias
            ));
        }

        return $this->checks[$alias];
    }

    /**
     * @return ArrayObject
     */
    public function getChecks()
    {
        return $this->checks;
    }

    /**
     * @return ResultsCollection
     */
    public function getLastResults()
    {
        return $this->lastResults;
    }

    /**
     * Set if checking should abort on first failure.
     *
     * @param boolean $breakOnFailure
     */
    public function setBreakOnFailure($breakOnFailure)
    {
        $this->breakOnFailure = (bool) $breakOnFailure;
    }

    /**
     * @return boolean
     */
    public function getBreakOnFailure()
    {
        return $this->breakOnFailure;
    }

    /**
     * @return array
     */
    public function getReporters()
    {
        return $this->reporters;
    }

    /**
     * Set severity of error that will result in a check failing. Defaults to:
     *  E_WARNING|E_PARSE|E_USER_ERROR|E_USER_WARNING|E_RECOVERABLE_ERROR
     *
     * @param int $catchErrorSeverity
     */
    public function setCatchErrorSeverity($catchErrorSeverity)
    {
        $this->catchErrorSeverity = $catchErrorSeverity;
    }

    /**
     * Get current severity of error that will result in a check failing.
     *
     * @return int
     */
    public function getCatchErrorSeverity()
    {
        return $this->catchErrorSeverity;
    }

    /**
     * Trigger an event on reporters.
     *
     * @param $eventType
     * @return bool
     */
    protected function triggerReporters($eventType)
    {
        $args = func_get_args();
        array_shift($args);
        foreach ($this->reporters as $reporter) {
            if (call_user_func_array(array($reporter, $eventType), $args) === false) {
                return false;
            }
        }

        return true;
    }

    protected function startErrorHandler()
    {
        set_error_handler(array($this, 'errorHandler'), $this->catchErrorSeverity);
    }

    public function errorHandler($errno, $errstr = '', $errfile = '', $errline = 0)
    {
        if (error_reporting() !== 0) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        }
    }

    protected function stopErrorHandler()
    {
        restore_error_handler();
    }

    /**
     * Convert PHP error severity INT to name.
     *
     * @param  integer $severity
     * @return string
     */
    public static function getSeverityDescription($severity)
    {
        switch ($severity) {
            case E_WARNING: // 2 //

                return 'WARNING';
            // @codeCoverageIgnoreStart
            case E_ERROR: // 1 //

                return 'ERROR';
            case E_PARSE: // 4 //

                return 'PARSE';
            case E_NOTICE: // 8 //

                return 'NOTICE';
            case E_CORE_ERROR: // 16 //

                return 'CORE_ERROR';
            case E_CORE_WARNING: // 32 //

                return 'CORE_WARNING';
            case E_COMPILE_ERROR: // 64 //

                return 'COMPILE_ERROR';
            case E_COMPILE_WARNING: // 128 //

                return 'COMPILE_WARNING';
            case E_USER_ERROR: // 256 //

                return 'USER_ERROR';
            case E_USER_WARNING: // 512 //

                return 'USER_WARNING';
            case E_USER_NOTICE: // 1024 //

                return 'USER_NOTICE';
            case E_STRICT: // 2048 //

                return 'STRICT';
            case E_RECOVERABLE_ERROR: // 4096 //

                return 'RECOVERABLE_ERROR';
            case E_DEPRECATED: // 8192 //

                return 'DEPRECATED';
            case E_USER_DEPRECATED: // 16384 //

                return 'USER_DEPRECATED';
            default:
                return 'error severity ' . $severity;
        }
        // @codeCoverageIgnoreEnd
    }
}

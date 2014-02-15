<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Runner\Reporter;

use ArrayObject;
use ZendDiagnostics\Result\Collection as ResultsCollection;
use ZendDiagnostics\Result\FailureInterface;
use ZendDiagnostics\Result\SkipInterface;
use ZendDiagnostics\Result\SuccessInterface;
use ZendDiagnostics\Result\WarningInterface;
use ZendDiagnostics\Check\CheckInterface;
use ZendDiagnostics\Result\ResultInterface;

/**
 * A simple reporter for displaying Runner results in console window.
 */
class BasicConsole implements ReporterInterface
{
    /**
     * Console window max width
     *
     * @var int
     */
    protected $width = 80;

    /**
     * Total number of Checks
     *
     * @var int
     */
    protected $total = 0;

    /**
     * Current Runner iteration
     *
     * @var int
     */
    protected $iteration = 1;

    /**
     * Current vertical screen position
     *
     * @var int
     */
    protected $pos = 1;

    /**
     * Width of the string representing total count of Checks
     *
     * @var int
     */
    protected $countLength;

    /**
     * Right-hand side gutter char width
     *
     * @var int
     */
    protected $gutter;

    /**
     * Has the Runner operation been aborted (stopped) ?
     *
     * @var bool
     */
    protected $stopped = false;

    /**
     * Create new BasicConsole reporter.
     *
     * @param int $width Max console window width (defaults to 80 chars)
     */
    public function __construct($width = 80)
    {
        $this->width = (int) $width;
    }

    /**
     * @see \ZendDiagnostics\Runner\Reporter\ReporterInterface
     * @param ArrayObject $checks
     * @param array       $runnerConfig
     */
    public function onStart(ArrayObject $checks, $runnerConfig)
    {
        $this->stopped = false;
        $this->iteration = 1;
        $this->pos = 1;
        $this->total = count($checks);

        // Calculate gutter width to accommodate number of tests passed
        if ($this->total <= $this->width) {
            $this->gutter = 0; // everything fits well
        } else {
            $this->countLength = floor(log10($this->total)) + 1;
            $this->gutter = ($this->countLength * 2) + 11;
        }

        $this->consoleWriteLn('Starting diagnostics:');
        $this->consoleWriteLn('');
    }

    /**
     * @see \ZendDiagnostics\Runner\Reporter\ReporterInterface
     * @param  CheckInterface $check
     * @param  string|null    $checkAlias
     * @return bool|void
     */
    public function onBeforeRun(CheckInterface $check, $checkAlias = null)
    {
    }

    /**
     * @see \ZendDiagnostics\Runner\Reporter\ReporterInterface
     * @param  CheckInterface  $check
     * @param  ResultInterface $result
     * @param  string|null     $checkAlias
     * @return bool|void
     */
    public function onAfterRun(CheckInterface $check, ResultInterface $result, $checkAlias = null)
    {
        // Draw a symbol for each result
        if ($result instanceof SuccessInterface) {
            $this->consoleWrite('.');
        } elseif ($result instanceof FailureInterface) {
            $this->consoleWrite('F');
        } elseif ($result instanceof WarningInterface) {
            $this->consoleWrite('!');
        } elseif ($result instanceof SkipInterface) {
            $this->consoleWrite('S');
        } else {
            $this->consoleWrite('?');
        }

        $this->pos++;

        // CheckInterface if we need to move to the next line
        if ($this->gutter > 0 && $this->pos > $this->width - $this->gutter) {
            $this->consoleWrite(
                str_pad(
                    sprintf('%s / %u (%s%%)',
                        str_pad($this->iteration, $this->countLength, ' ', STR_PAD_LEFT),
                        $this->total,
                        str_pad(round($this->iteration / $this->total * 100), 3, ' ', STR_PAD_LEFT)
                    ),
                    $this->gutter,
                    ' ',
                    STR_PAD_LEFT
                )
            );

            $this->pos = 1;
        }

        $this->iteration++;
    }

    /**
     * @see \ZendDiagnostics\Runner\Reporter\ReporterInterface
     * @param ResultsCollection $results
     */
    public function onFinish(ResultsCollection $results)
    {
        $this->consoleWriteLn();
        $this->consoleWriteLn();

        // Display a summary line
        if ($results->getFailureCount() == 0 && $results->getWarningCount() == 0 && $results->getUnknownCount() == 0 && $results->getSkipCount() == 0) {
            $line = 'OK (' . $this->total . ' diagnostic tests)';
            $this->consoleWrite(str_pad($line, $this->width - 1, ' ', STR_PAD_RIGHT));

        } elseif ($results->getFailureCount() == 0) {
            $line = $results->getWarningCount() . ' warnings, ';
            $line .= $results->getSuccessCount() . ' successful tests';

            if ($results->getSkipCount() > 0) {
                $line .= ', ' . $results->getSkipCount() . ' skipped tests';
            }

            if ($results->getUnknownCount() > 0) {
                $line .= ', ' . $results->getUnknownCount() . ' unknown test results';
            }

            $line .= '.';

            $this->consoleWrite(str_pad($line, $this->width - 1, ' ', STR_PAD_RIGHT));

        } else {
            $line = $results->getFailureCount() . ' failures, ';
            $line .= $results->getWarningCount() . ' warnings, ';
            $line .= $results->getSuccessCount() . ' successful tests';

            if ($results->getSkipCount() > 0) {
                $line .= ', ' . $results->getSkipCount() . ' skipped tests';
            }

            if ($results->getUnknownCount() > 0) {
                $line .= ', ' . $results->getUnknownCount() . ' unknown test results';
            }

            $line .= '.';

            $this->consoleWrite(str_pad($line, $this->width, ' ', STR_PAD_RIGHT));
        }

        $this->consoleWriteLn();
        $this->consoleWriteLn();
        // Display a list of failures and warnings
        foreach ($results as $check) {
            /* @var $check  \ZendDiagnostics\Check\CheckInterface */
            /* @var $result \ZendDiagnostics\Result\ResultInterface */
            $result = $results[$check];

            if ($result instanceof FailureInterface) {
                $this->consoleWriteLn('Failure: ' . $check->getLabel());
                $message = $result->getMessage();
                if ($message) {
                    $this->consoleWriteLn($message);
                }
                $this->consoleWriteLn();
            } elseif ($result instanceof WarningInterface) {
                $this->consoleWriteLn('Warning: ' . $check->getLabel());
                $message = $result->getMessage();
                if ($message) {
                    $this->consoleWriteLn($message);
                }
                $this->consoleWriteLn();
            } elseif (!$result instanceof SuccessInterface) {
                $this->consoleWriteLn('Unknown result ' . get_class($result) . ': ' . $check->getLabel());
                $message = $result->getMessage();
                if ($message) {
                    $this->consoleWriteLn($message);
                }
                $this->consoleWriteLn();
            }
        }

        // Display information that the test has been aborted.
        if ($this->stopped) {
            $this->consoleWriteLn('Diagnostics aborted because of a failure.');
        }
    }

    /**
     * @see \ZendDiagnostics\Runner\Reporter\ReporterInterface
     * @param ResultsCollection $results
     */
    public function onStop(ResultsCollection $results)
    {
        $this->stopped = true;
    }

    /**
     * Write text to the console.
     *
     * Feel free to extend this method and add better console handling.
     *
     * @param string $text
     */
    protected function consoleWrite($text)
    {
        echo $text;
    }

    /**
     * Write text followed by a newline character to the console.
     *
     * Feel free to extend this method and add better console handling.
     *
     * @param string $text
     */
    protected function consoleWriteLn($text = '')
    {
        echo $text . PHP_EOL;
    }

}

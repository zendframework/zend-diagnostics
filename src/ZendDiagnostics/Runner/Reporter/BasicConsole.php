<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Runner\Reporter;

use ZendDiagnostics\Result\Collection as ResultsCollection;
use ZendDiagnostics\Result\FailureInterface;
use ZendDiagnostics\Result\SuccessInterface;
use ZendDiagnostics\Result\WarningInterface;
use ZendDiagnostics\Check\CheckInterface;
use ZendDiagnostics\Result\ResultInterface;
use \ArrayObject;

/**
 * A simple reporter for displaying Runner results in console window.
 *
 * @package ZendDiagnostics\Reporter
 */
class BasicConsole implements ReporterInterface
{
    protected $width = 80;
    protected $total = 0;
    protected $iteration = 1;
    protected $pos = 1;
    protected $countLength;
    protected $gutter;
    protected $stopped = false;

    /**
     * Create new BasicConsole reporter.
     *
     * @param int $width Max console window width (defaults to 80 chars)
     */
    public function __construct($width = 80)
    {
        $this->width = (int)$width;
    }

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

    public function onBeforeRun(CheckInterface $check)
    {
    }

    public function onAfterRun(CheckInterface $check, ResultInterface $result)
    {
        // Draw a symbol for each result
        if ($result instanceof SuccessInterface) {
            $this->consoleWrite('.');
        } elseif ($result instanceof FailureInterface) {
            $this->consoleWrite('F');
        } elseif ($result instanceof WarningInterface) {
            $this->consoleWrite('!');
        } else {
            $this->consoleWrite('?');
        }

        $this->pos++;

        // CheckInterface if we need to move to the next line
        if ($this->gutter > 0 && $this->pos > $this->width - $this->gutter) {
            $this->consoleWrite(
                str_pad(
                    str_pad($this->iteration, $this->countLength, ' ', STR_PAD_LEFT) . ' / ' . $this->total .
                        ' (' . str_pad(round($this->iteration / $this->total * 100), 3, ' ', STR_PAD_LEFT) . '%)'
                    , $this->gutter, ' ', STR_PAD_LEFT
                )
            );
            $this->pos = 1;
        }

        $this->iteration++;
    }

    public function onFinish(ResultsCollection $results)
    {
        $this->consoleWriteLn();
        $this->consoleWriteLn();

        // Display a summary line
        if ($results->getFailureCount() == 0 && $results->getWarningCount() == 0 && $results->getUnknownCount() == 0) {
            $line = 'OK (' . $this->total . ' diagnostic tests)';
            $this->consoleWrite(str_pad($line, $this->width - 1, ' ', STR_PAD_RIGHT));

        } elseif ($results->getFailureCount() == 0) {
            $line = $results->getWarningCount() . ' warnings, ';
            $line .= $results->getSuccessCount() . ' successful tests';

            if ($results->getUnknownCount() > 0) {
                $line .= ', ' . $results->getUnknownCount() . ' unknown test results';
            }

            $line .= '.';

            $this->consoleWrite(str_pad($line, $this->width - 1, ' ', STR_PAD_RIGHT));

        } else {
            $line = $results->getFailureCount() . ' failures, ';
            $line .= $results->getWarningCount() . ' warnings, ';
            $line .= $results->getSuccessCount() . ' successful tests';

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

    public function onStop(ResultsCollection $results)
    {
        $this->stopped = true;
    }

    protected function consoleWrite($text)
    {
        echo $text;
    }

    protected function consoleWriteLn($text = '')
    {
        echo $text . PHP_EOL;
    }

}

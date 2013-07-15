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
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Result\Warning;
use ZendDiagnostics\Runner\Reporter\ConsoleColor as Color;
use ZendDiagnostics\Check\CheckInterface as Check;
use ZendDiagnostics\Result\ResultInterface as Result;
use \ArrayObject;

/**
 * A simple reporter for displaying Runner results in console window.
 *
 * @package ZendDiagnostics\Reporter
 */
class BasicConsole implements ReporterInterface
{
    protected $width = 80;
    protected $consoleColor = false;
    protected $total = 0;
    protected $iteration = 1;
    protected $pos = 1;
    protected $countLength;
    protected $gutter;
    protected $stopped = false;

    /**
     * Create new BasicConsole reporter.
     *
     * @param int  $width       Max console window width (defaults to 80 chars)
     * @param bool $useColor    Use ANSI colors ? (defaults to false)
     */
    public function __construct($width = 80, $useColor = false)
    {
        $this->width = (int)$width;
        $this->consoleColor = (bool)$useColor;
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

    public function onBeforeRun(Check $check){}

    public function onAfterRun(Check $check, Result $result)
    {
        // Draw a symbol for each result
        if ($result instanceof Success) {
            $this->consoleWrite('.', Color::GREEN);
        } elseif ($result instanceof Failure) {
            $this->consoleWrite('F', Color::WHITE, Color::RED);
        } elseif ($result instanceof Warning) {
            $this->consoleWrite('!', Color::YELLOW);
        } else {
            $this->consoleWrite('?', Color::YELLOW);
        }

        $this->pos++;

        // Check if we need to move to the next line
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
            $this->consoleWrite(
                str_pad($line, $this->width - 1, ' ', STR_PAD_RIGHT),
                Color::NORMAL, Color::GREEN
            );
        } elseif ($results->getFailureCount() == 0) {
            $line = $results->getWarningCount() . ' warnings, ';
            $line .= $results->getSuccessCount() . ' successful tests';

            if ($results->getUnknownCount() > 0) {
                $line .= ', ' . $results->getUnknownCount() . ' unknown test results';
            }

            $line .= '.';

            $this->consoleWrite(
                str_pad($line, $this->width - 1, ' ', STR_PAD_RIGHT),
                Color::NORMAL, Color::YELLOW
            );
        } else {
            $line = $results->getFailureCount() . ' failures, ';
            $line .= $results->getWarningCount() . ' warnings, ';
            $line .= $results->getSuccessCount() . ' successful tests';

            if ($results->getUnknownCount() > 0) {
                $line .= ', ' . $results->getUnknownCount() . ' unknown test results';
            }

            $line .= '.';

            $this->consoleWrite(
                str_pad($line, $this->width, ' ', STR_PAD_RIGHT),
                Color::NORMAL, Color::RED
            );
        }

        $this->consoleWriteLn();
        $this->consoleWriteLn();
        // Display a list of failures and warnings
        foreach ($results as $check) {
            /* @var $check  \ZendDiagnostics\Check\CheckInterface */
            /* @var $result \ZendDiagnostics\Result\ResultInterface */
            $result = $results[$check];

            if ($result instanceof Failure) {
                $this->consoleWriteLn('Failure: ' . $check->getLabel(), Color::RED);
                $message = $result->getMessage();
                if ($message) {
                    $this->consoleWriteLn($message, Color::RED);
                }
                $this->consoleWriteLn();
            } elseif ($result instanceof Warning) {
                $this->consoleWriteLn('Warning: ' . $check->getLabel(), Color::YELLOW);
                $message = $result->getMessage();
                if ($message) {
                    $this->consoleWriteLn($message, Color::YELLOW);
                }
                $this->consoleWriteLn();
            } elseif (!$result instanceof Success) {
                $this->consoleWriteLn('Unknown result ' . get_class($result) . ': ' . $check->getLabel(), Color::YELLOW);
                $message = $result->getMessage();
                if ($message) {
                    $this->consoleWriteLn($message, Color::YELLOW);
                }
                $this->consoleWriteLn();
            }
        }

        // Display information that the test has been aborted.
        if ($this->stopped) {
            $this->consoleWriteLn('Diagnostics aborted because of a failure.', Color::RED);
        }
    }

    public function onStop(ResultsCollection $results)
    {
        $this->stopped = true;
    }


    protected function consoleWrite($text, $color = null, $bgColor = null)
    {
        if (!$this->consoleColor || ($color === null && $bgColor === null)) {
            // raw output
            echo $text;

        } else {
            // use ANSI colorization
            // @codeCoverageIgnoreStart
            if ($color !== null) {
                if (!isset(Color::$ansiColorMap['fg'][$color])) {
                    throw new \BadMethodCallException(sprintf(
                        'Unknown color "%s". Please use one of the ColorInterface constants.',
                        $color
                    ));
                }
                $color = Color::$ansiColorMap['fg'][$color];
            }
            if ($bgColor !== null) {
                if (!isset(Color::$ansiColorMap['bg'][$bgColor])) {
                    throw new \BadMethodCallException(sprintf(
                        'Unknown color "%s". Please use one of the ColorInterface constants.',
                        $bgColor
                    ));
                }
                $bgColor = Color::$ansiColorMap['bg'][$bgColor];
            }

            echo ($color !== null ? "\x1b[" . $color . 'm' : '')
                . ($bgColor !== null ? "\x1b[" . $bgColor . 'm' : '')
                . $text
                . "\x1b[22;39m\x1b[0;49m";
            // @codeCoverageIgnoreEnd
        }
    }

    protected function consoleWriteLn($text = '', $color = null, $bgColor = null)
    {
        $this->consoleWrite($text . PHP_EOL, $color, $bgColor);
    }

}

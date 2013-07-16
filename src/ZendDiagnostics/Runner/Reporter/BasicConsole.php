<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Runner\Reporter;

use ZendDiagnostics\Check\CheckInterface;
use ZendDiagnostics\Result\SuccessInterface;
use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Result\WarningInterface;
use ZendDiagnostics\Result\Warning;
use ZendDiagnostics\Result\FailureInterface;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\ResultInterface;
use ZendDiagnostics\Result\Collection as ResultsCollection;
use ZendDiagnostics\Runner\Reporter\ConsoleColor as Color;

use \ArrayObject;

/**
 * A simple reporter for displaying Runner results in console window.
 *
 * @package ZendDiagnostics\Reporter
 */
class BasicConsole implements ReporterInterface
{
    protected $width = 80;
    protected $consoleColor;
    protected $total = 0;
    protected $iteration = 1;
    protected $pos = 1;
    protected $countLength;
    protected $gutter;
    protected $stopped = false;
    protected $verbose = false;

    /**
     * Create new BasicConsole reporter.
     *
     * @param int  $width       Max console window width (defaults to 80 chars)
     * @param bool $useColor    Use ANSI colors ? (defaults to false)
     */
    public function __construct($width = 80, $useColor = false)
    {
        $this->width = (int) $width;
        $this->consoleColor = (bool) $useColor;
    }

    public function setVerbose($verbose)
    {
        $this->verbose = (bool) $verbose;
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

    public function onBeforeRun(CheckInterface $check){}

    public function onAfterRun(CheckInterface $check, ResultInterface $result)
    {
        // Draw a symbol for each result
        if ($result instanceof SuccessInterface) {
            $this->consoleWrite('.', $result);
        } elseif ($result instanceof FailureInterface) {
            $this->consoleWrite('F', $result);
        } elseif ($result instanceof WarningInterface) {
            $this->consoleWrite('!', $result);
        } else {
            $this->consoleWrite('?', $result);
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
            $this->consoleWrite(str_pad($line, $this->width - 1, ' ', STR_PAD_RIGHT), new Success());
        } elseif ($results->getFailureCount() == 0) {
            $line = $results->getWarningCount() . ' warning(s), ';
            $line .= $results->getSuccessCount() . ' successful test(s)';

            if ($results->getUnknownCount() > 0) {
                $line .= ', ' . $results->getUnknownCount() . ' unknown test results';
            }

            $line .= '.';

            $this->consoleWrite(
                str_pad($line, $this->width - 1, ' ', STR_PAD_RIGHT), new Warning());
        } else {
            $line = $results->getFailureCount() . ' failure(s), ';
            $line .= $results->getWarningCount() . ' warning(s), ';
            $line .= $results->getSuccessCount() . ' successful test(s)';

            if ($results->getUnknownCount() > 0) {
                $line .= ', ' . $results->getUnknownCount() . ' unknown test result(s)';
            }

            $line .= '.';

            $this->consoleWrite(
                str_pad($line, $this->width, ' ', STR_PAD_RIGHT), new Failure());
        }

        $this->consoleWriteLn();

        if ($this->verbose) {
            $this->consoleWriteLn();
            // Display a list of failures and warnings
            foreach ($results as $check) {
                /* @var $check  \ZendDiagnostics\Check\CheckInterface */
                /* @var $result \ZendDiagnostics\Result\ResultInterface */
                $result = $results[$check];

                $msg = '';
                if ($result instanceof FailureInterface) {
                    $msg = 'Failure';
                } elseif ($result instanceof WarningInterface) {
                    $msg = 'Warning';
                } elseif ($result instanceof Success && $this->verbose) {
                    $msg = 'Success';
                } elseif (!$result instanceof SuccessInterface) {
                    $msg = 'Unknown result ' . get_class($result);
                }

                if ($msg) {
                    $this->consoleWriteLn($msg . ': ' . $check->getLabel(), $result);
                    $message = $result->getMessage();
                    if ($message) {
                        $this->consoleWriteLn($message, $result);
                    }
                    $this->consoleWriteLn();
                }
            }
        }

        // Display information that the test has been aborted.
        if ($this->stopped) {
            $this->consoleWriteLn('Diagnostics aborted because of a failure.', new Failure());
        }
    }

    public function onStop(ResultsCollection $results)
    {
        $this->stopped = true;
    }

    protected function consoleWrite($text, ResultInterface $result = null)
    {
        if ($result instanceof FailureInterface) {
            $color = Color::RED;
        } elseif ($result instanceof WarningInterface) {
            $color = Color::YELLOW;
        } elseif ($result instanceof SuccessInterface) {
            $color = Color::GREEN;
        } else {
            $color = Color::YELLOW;
        }

        $bgColor = null;

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

    protected function consoleWriteLn($text = '', ResultInterface $result = null)
    {
        $this->consoleWrite($text . PHP_EOL, $result);
    }
}

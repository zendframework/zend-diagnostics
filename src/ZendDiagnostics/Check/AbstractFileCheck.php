<?php

namespace ZendDiagnostics\Check;

use InvalidArgumentException;
use Traversable;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\FailureInterface;
use ZendDiagnostics\Result\ResultInterface;
use ZendDiagnostics\Result\Success;

/**
 * Abstract class for handling different file checks
 */
abstract class AbstractFileCheck extends AbstractCheck
{
    /**
     * @var array|Traversable
     */
    protected $files;

    /**
     * @param  string|array|Traversable $files Path name or an array / Traversable of paths
     * @throws InvalidArgumentException
     */
    public function __construct($files)
    {
        if (is_object($files) && !$files instanceof Traversable) {
            throw new InvalidArgumentException(
                'Expected a file name (string) , an array or Traversable of strings, got ' . get_class($files)
            );
        }

        if (!is_object($files) && !is_array($files) && !is_string($files)) {
            throw new InvalidArgumentException('Expected a file name (string) or an array of strings');
        }

        if (is_string($files)) {
            $this->files = array($files);
        } else {
            $this->files = $files;
        }
    }

    /**
     * @return ResultInterface
     */
    public function check()
    {
        foreach ($this->files as $file) {
            if (!is_file($file) or !is_readable($file)) {
                return new Failure(sprintf('File "%s" does not exist or is not readable!', $file));
            }

            if (($validationResult = $this->validateFile($file)) instanceof FailureInterface) {
                return $validationResult;
            }
        }

        return new Success('All files are available and valid');
    }

    /**
     * Validates a specific file type and returns a check result
     *
     * @param string $file
     * @return ResultInterface
     */
    abstract protected function validateFile($file);
}

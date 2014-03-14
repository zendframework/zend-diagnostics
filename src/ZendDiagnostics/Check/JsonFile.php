<?php

namespace ZendDiagnostics\Check;

use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Result\ResultInterface;

/**
 * Checks if a JSON file is available and valid
 */
class JsonFile extends AbstractFileCheck
{
    /**
     * @param string $file
     * @return ResultInterface
     */
    protected function validateFile($file)
    {
        if (is_null(json_decode(file_get_contents($file)))) {
            return new Failure(sprintf('Could no decode JSON file "%s"', $file));
        }

        return new Success();
    }
}

<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2013-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

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

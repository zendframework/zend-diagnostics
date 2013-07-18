<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;

class PhpExtensions extends AbstractCheck
{
    protected $extensions;

    /**
     * @param array $extensions List of extensions names you want to test availability
     */
    public function __construct($extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * @see ZendDiagnostics\CheckInterface::check()
     */
    public function check()
    {
        $missingExtensions = array();
        foreach ($this->extensions as $extension) {
            if (!extension_loaded($extension)) {
                $missingExtensions[] = $extension;
            }
        }

        if (count($missingExtensions) > 0) {
            return new Failure(sprintf('The following extensions are not missing: "%s"', implode('", "', $missingExtensions)));
        }

        return new Success();
    }
}

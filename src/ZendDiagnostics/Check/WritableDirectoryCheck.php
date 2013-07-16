<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;

/**
 * Check if the given directories are writable.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class WritableDirectoryCheck extends AbstractCheck
{
    /**
     * @var array
     */
    protected $directories;

    /**
     * Construct.
     *
     * @param array $directories
     */
    public function __construct($directories)
    {
        $this->directories = $directories;
    }

    /**
     * @see ZendDiagnostics\CheckInterface::check()
     */
    public function check()
    {
        $notWritable = array();
        foreach ($this->directories as $dir) {
            if (!is_writable($dir)) {
                $notWritable[] = $dir;
            }
        }

        if (count($notWritable) > 0) {
            return new Failure(sprintf('The following directories are not writable: "%s"', implode('", "', $notWritable)));
        }

        return new Success();
    }

    /**
     * @see Liip\MonitorBundle\Check\CheckInterface::getName()
     */
    public function getName()
    {
        return 'Writable directory';
    }
}

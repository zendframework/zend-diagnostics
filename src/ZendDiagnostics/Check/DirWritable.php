<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use InvalidArgumentException;
use Traversable;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;

/**
 * Validate that a given path (or a collection of paths) is a dir and is writable
 */
class DirWritable extends AbstractCheck implements CheckInterface
{
    /**
     * @var array|Traversable
     */
    protected $dir;

    /**
     * @param  string|array|Traversable $path Path name or an array of paths
     * @throws InvalidArgumentException
     */
    public function __construct($path)
    {
        if (is_object($path) && !$path instanceof Traversable) {
            throw new InvalidArgumentException(
                'Expected a dir name (string) , an array or Traversable of strings, got ' . get_class($path)
            );
        }

        if (!is_object($path) && !is_array($path) && !is_string($path)) {
            throw new InvalidArgumentException('Expected a dir name (string) or an array of strings');
        }

        if (is_string($path)) {
            $this->dir = array($path);
        } else {
            $this->dir = $path;
        }
    }

    /**
     * Perform the check
     *
     * @see \ZendDiagnostics\Check\CheckInterface::check()
     * @return Failure|Success
     */
    public function check()
    {
        $nonDirs = $unwritable = array();

        // Check each path if it's a dir and is writable
        foreach ($this->dir as $dir) {
            if (!is_dir($dir)) {
                $nonDirs[] = $dir;
            }

            if (!is_writable($dir)) {
                $unwritable[] = $dir;
            }
        }

        // Construct failure message
        $failureString = '';
        if (count($nonDirs) > 1) {
            $failureString .= 'The following paths are not valid directories: ' . join(', ', $nonDirs).' ';
        } elseif (count($nonDirs) == 1) {
            $failureString .= current($nonDirs) . ' is not a valid directory. ';
        }

        if (count($unwritable) > 1) {
            $failureString .= 'The following directories are not writable: ' . join(', ', $unwritable);
        } elseif (count($unwritable) == 1) {
            $failureString .= current($unwritable) . ' directory is not writable.';
        }

        // Return success or failure
        if ($failureString) {
            return new Failure(trim($failureString), array('nonDirs' => $nonDirs, 'unwritable' => $unwritable));
        } else {
            return new Success(
                count($this->dir) > 1 ? 'All paths are writable directories.' : 'The path is a a writable directory.',
                $this->dir
            );
        }
    }
}

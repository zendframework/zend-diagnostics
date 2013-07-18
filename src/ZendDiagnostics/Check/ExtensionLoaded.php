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
 * Validate that a named extension or a collection of extensions is available.
 */
class ExtensionLoaded extends AbstractCheck implements CheckInterface
{
    /**
     * @var array|Traversable
     */
    protected $extensions;

    protected $autoload = true;

    /**
     * @param  string|array|Traversable  $extensionName PHP extension name or an array of names
     * @throws \InvalidArgumentException
     */
    public function __construct($extensionName)
    {
        if (is_object($extensionName) && !$extensionName instanceof Traversable) {
            throw new InvalidArgumentException(
                'Expected a module name (string) , an array or Traversable of strings, got ' . get_class($extensionName)
            );
        }

        if (!is_object($extensionName) && !is_array($extensionName) && !is_string($extensionName)) {
            throw new InvalidArgumentException('Expected a module name (string) or an array of strings');
        }

        if (is_string($extensionName)) {
            $this->extensions = array($extensionName);
        } else {
            $this->extensions = $extensionName;
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
        $missing = array();
        foreach ($this->extensions as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }
        if (count($missing)) {
            if (count($missing) > 1) {
                return new Failure('Extensions ' . join(', ', $missing) . ' are not available.');
            } else {
                return new Failure('Extension ' . join('', $missing) . ' is not available.');
            }
        } else {
            if (count($this->extensions) > 1) {
                $versions = array();
                foreach ($this->extensions as $ext) {
                    $versions[$ext] = phpversion($ext) ? : 'loaded';
                }

                return new Success(
                    join(',', $this->extensions) . ' extensions are loaded.',
                    $versions
                );
            } else {
                $ext = $this->extensions[0];

                return new Success(
                    $ext . ' extension is loaded.',
                    $ext . ' ' . (phpversion($ext) ? phpversion($ext) : 'loaded')
                );
            }
        }
    }
}

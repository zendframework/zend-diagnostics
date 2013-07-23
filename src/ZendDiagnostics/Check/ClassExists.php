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
 * Validate that a class or a collection of classes is available.
 */
class ClassExists extends AbstractCheck implements CheckInterface
{
    /**
     * An array of classes to check
     *
     * @var array|Traversable
     */
    protected $classes;

    /**
     * Use autoloader when looking for classes? (defaults to true)
     *
     * @var bool
     */
    protected $autoload = true;

    /**
     * @param  string|array|Traversable $classNames Class name or an array of classes
     * @param  bool                     $autoload   Use autoloader when looking for classes? (defaults to true)
     * @throws InvalidArgumentException
     */
    public function __construct($classNames, $autoload = true)
    {
        if (is_object($classNames) && !$classNames instanceof Traversable) {
            throw new InvalidArgumentException(
                'Expected a class name (string) , an array or Traversable of strings, got ' . get_class($classNames)
            );
        }

        if (!is_object($classNames) && !is_array($classNames) && !is_string($classNames)) {
            throw new InvalidArgumentException('Expected a class name (string) or an array of strings');
        }

        if (is_string($classNames)) {
            $this->classes = array($classNames);
        } else {
            $this->classes = $classNames;
        }

        $this->autoload = $autoload;
    }

    /**
     * Perform the check
     *
     * @see \ZendDiagnostics\Check\CheckInterface::check()
     * @return Success|Failure
     */
    public function check()
    {
        $missing = array();
        foreach ($this->classes as $class) {
            if (!class_exists($class, $this->autoload)) {
                $missing[] = $class;
            }
        }

        if (count($missing) > 1) {
            return new Failure('The following classes are missing: ' . join(', ', $missing), $missing);
        } elseif (count($missing) == 1) {
            return new Failure('Class ' . current($missing) . ' does not exist', $missing);
        } else {
            return new Success('All classes are present.', $this->classes);
        }
    }
}

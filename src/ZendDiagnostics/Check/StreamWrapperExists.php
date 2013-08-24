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
 * Validate that a stream wrapper exists.
 */
class StreamWrapperExists extends AbstractCheck implements CheckInterface
{
    /**
     * @var array|Traversable
     */
    protected $wrappers;

    /**
     * @param  string|array|Traversable $wrappers Stream wrapper name or an array of names
     * @throws InvalidArgumentException
     */
    public function __construct($wrappers)
    {
        if (is_object($wrappers) && !$wrappers instanceof Traversable) {
            throw new InvalidArgumentException(
                'Expected a stream wrapper name (string) , an array or Traversable of strings, got ' . get_class($wrappers)
            );
        }

        if (!is_object($wrappers) && !is_array($wrappers) && !is_string($wrappers)) {
            throw new InvalidArgumentException('Expected a stream wrapper name (string) or an array of strings');
        }

        if (is_string($wrappers)) {
            $this->wrappers = array($wrappers);
        } else {
            $this->wrappers = $wrappers;
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
        $missingWrappers = array();
        $availableWrappers = stream_get_wrappers();
        array_walk($availableWrappers, function ($v) {
            return strtolower($v);
        });

        foreach ($this->wrappers as $class) {
            if (!in_array($class, $availableWrappers)) {
                $missingWrappers[] = $class;
            }
        }

        if (count($missingWrappers) == 1) {
            return new Failure('Stream wrapper ' . current($missingWrappers) . ' is not available', $availableWrappers);
        } elseif (count($missingWrappers)) {
            return new Failure(
                sprintf(
                    'The following stream wrappers are missing: %s',
                    join(', ', $missingWrappers)
                ),
                $availableWrappers
            );
        }

        return new Success(join(', ', $this->wrappers) . ' stream wrapper(s) are available', $availableWrappers);
    }
}

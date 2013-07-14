<?php
namespace ZendDiagnostics\Result;

use \InvalidArgumentException;
use ZendDiagnostics\Check\CheckInterface;

/**
 * Utility class to store Results entities for corresponding Checks
 *
 * @package ZendDiagnostics\Result
 */
class Collection extends \SplObjectStorage
{
    protected $countSuccess = 0;
    protected $countWarning = 0;
    protected $countFailure = 0;
    protected $countUnknown = 0;
    protected $objMap = array();

    /**
     * Get number of successful Check results.
     *
     * @return int
     */
    public function getSuccessCount()
    {
        return $this->countSuccess;
    }

    /**
     * Get number of failed Check results.
     *
     * @return int
     */
    public function getFailureCount()
    {
        return $this->countFailure;
    }

    /**
     * Get number of warnings.
     *
     * @return int
     */
    public function getWarningCount()
    {
        return $this->countWarning;
    }

    /**
     * Get number of unknown results.
     *
     * @return int
     */
    public function getUnknownCount()
    {
        return $this->countUnknown;
    }

    public function offsetGet($index)
    {
        $this->validateIndex($index);

        return parent::offsetGet($index);
    }

    public function offsetExists($index)
    {
        $this->validateIndex($index);

        return parent::offsetExists($index);
    }

    public function offsetSet($index, $CheckResult)
    {
        $indexObj = $index;
        $this->validateIndex($index);
        $this->validateValue($CheckResult);

        // Decrement counters when replacing existing item
        if (parent::offsetExists($index)) {
            $oldResult = parent::offsetGet($index);
            if ($oldResult instanceof Success) {
                $this->countSuccess--;
            } elseif ($oldResult instanceof Failure) {
                $this->countFailure--;
            } elseif ($oldResult instanceof Warning) {
                $this->countWarning--;
            } else {
                $this->countUnknown--;
            }
        }

        parent::offsetSet($index, $CheckResult);

        // Increment counters
        if ($CheckResult instanceof Success) {
            $this->countSuccess++;
        } elseif ($CheckResult instanceof Failure) {
            $this->countFailure++;
        } elseif ($CheckResult  instanceof Warning) {
            $this->countWarning++;
        } else {
            $this->countUnknown++;
        }
    }

    public function offsetUnset($index)
    {
        $this->validateIndex($index);

        // Decrement counters when replacing existing item
        if (parent::offsetExists($index)) {
            $oldResult = parent::offsetGet($index);
            if ($oldResult instanceof Success) {
                $this->countSuccess--;
            } elseif ($oldResult instanceof Failure) {
                $this->countFailure--;
            } elseif ($oldResult instanceof Warning) {
                $this->countWarning--;
            } else {
                $this->countUnknown--;
            }
        }

        parent::offsetUnset($index);
    }

    /**
     * Validate index object.
     *
     * @param mixed $index
     * @return string
     * @throws InvalidArgumentException
     */
    protected function validateIndex($index)
    {
        if (!is_object($index) || !$index instanceof CheckInterface) {
            $what = is_object($index) ? 'object of type ' . get_class($index) : gettype($index);
            throw new InvalidArgumentException(
                'Cannot use ' . $what . ' as index for this collection. Expected instance of CheckInterface.'
            );
        }

        return $index;
    }

    /**
     * Validate if the value can be stored in this collection.
     *
     * @param mixed $checkResult
     * @return mixed
     * @throws InvalidArgumentException
     */
    protected function validateValue($checkResult)
    {
        if (!is_object($checkResult) || !$checkResult instanceof ResultInterface) {
            $what = is_object($checkResult) ? 'object of type ' . get_class($checkResult) : gettype($checkResult);
            throw new InvalidArgumentException(
                'This collection cannot hold ' . $what . ' Expected instance of ' . __NAMESPACE__ . '\ResultInterface'
            );
        }

        return $checkResult;
    }
}

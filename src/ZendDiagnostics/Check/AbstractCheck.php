<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use ZendDiagnostics\Result\ResultInterface;

abstract class AbstractCheck implements CheckInterface
{
    /**
     * @return ResultInterface
     */
    abstract public function check();

    /**
     * Return a label describing this test instance.
     *
     * @return string
     */
    public function getLabel()
    {
        $class = get_class($this);
        return preg_replace('/([A-Z])/',' $1', substr($class, strrpos($class,"\\")+1));
    }

    /**
     * Alias for getLabel()
     *
     * @see CheckInterface::getLabel()
     * @return string
     */
    public function getName()
    {
        return $this->getLabel();
    }
}

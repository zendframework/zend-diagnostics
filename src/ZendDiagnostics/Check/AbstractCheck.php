<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

abstract class AbstractCheck implements CheckInterface
{
    /**
     * Explicitly set label.
     *
     * @var string
     */
    protected $label;

    /**
     * Return a label describing this test instance.
     *
     * @return string
     */
    public function getLabel()
    {
        if ($this->label !== null) {
            return $this->label;
        }

        $class = get_class($this);
        $class = substr($class, strrpos($class, '\\') + 1);
        $class = preg_replace('/([A-Z])/', ' $1', $class);

        return trim($class);
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

    /**
     * Set a custom label for this test instance.
     *
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }
}

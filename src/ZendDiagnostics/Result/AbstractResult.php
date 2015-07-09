<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace ZendDiagnostics\Result;

/**
 * Abstract, simple implementation of ResultInterface
 */
abstract class AbstractResult implements ResultInterface
{
    /**
     * @var string|null
     */
    protected $message;

    /**
     * @var mixed|null
     */
    protected $data;

    /**
     * Create new result
     *
     * @param string|null $message
     * @param mixed|null  $data
     */
    public function __construct($message = null, $data = null)
    {
        if ($message !== null) {
            $this->setMessage($message);
        }

        if ($data !== null) {
            $this->setData($data);
        }
    }

    /**
     * Get message related to the result.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Get detailed info on the test result (if available).
     *
     * @return mixed|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed|null $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @param null|string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }
}

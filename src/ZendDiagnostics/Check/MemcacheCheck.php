<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;

class MemcacheCheck extends AbstractCheck
{
    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @param string $host
     * @param int $port
     */
    public function __construct($host, $port = 11211)
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * @see ZendDiagnostics\CheckInterface::check()
     */
    public function check()
    {
        try {
            $memcache = new \Memcache();
            $memcache->addServer($this->host, $this->port);
            $stats = @$memcache->getExtendedStats();
            $available = $stats[$this->host . ':' . $this->port] !== false;
            if (!$available && !@$memcache->connect($this->host, $this->port)) {
                return new Failure(sprintf('No memcache server running at host %s on port %s', $this->host, $this->port));
            }
        } catch (\Exception $e) {
            return new Failure(' '. $e->getMessage());
        }

        new Success();
    }

    /**
     * @see ZendDiagnostics\CheckInterface::getName()()
     */
    public function getName()
    {
        return 'Memcache';
    }
}

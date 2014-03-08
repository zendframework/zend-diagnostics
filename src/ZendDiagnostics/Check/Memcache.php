<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use InvalidArgumentException;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;

/**
 * Check if MemCache extension is loaded and given server is reachable.
 */
class Memcache extends AbstractCheck
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
     * @param int    $port
     * @throws \InvalidArgumentException
     */
    public function __construct($host, $port = 11211)
    {
        if (!is_string($host)) {
            throw new InvalidArgumentException(sprintf('Cannot use %s as host - expecting a string', gettype($host)));
        }

        $port = (int)$port;
        if ($port < 1) {
            throw new InvalidArgumentException(sprintf(
                'Invalid port number - expecting a positive integer',
                gettype($host)
            ));
        }

        $this->host = $host;
        $this->port = $port;
    }

    /**
     * @see ZendDiagnostics\CheckInterface::check()
     */
    public function check()
    {
        if (!class_exists('Memcache', false)) {
            return new Failure('Memcache extension is not loaded');
        }

        try {
            $memcache = new \Memcache();
            $memcache->addServer($this->host, $this->port);
            $stats = @$memcache->getExtendedStats();

            if (
                !$stats ||
                !is_array($stats) ||
                !isset($stats[$this->host . ':' . $this->port]) ||
                ($stats[$this->host . ':' . $this->port] === false)
            ) {
                // Attempt a connection to make sure that the server is really down
                if (!@$memcache->connect($this->host, $this->port)) {
                    return new Failure(sprintf('No memcache server running at host %s on port %s', $this->host, $this->port));
                }
            }
        } catch (\Exception $e) {
            return new Failure($e->getMessage());
        }

        return new Success(sprintf('Memcache server running at host %s on port %s', $this->host, $this->port));
    }
}

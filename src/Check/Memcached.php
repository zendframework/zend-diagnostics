<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

namespace ZendDiagnostics\Check;

use InvalidArgumentException;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;

/**
 * Check if MemCached extension is loaded and given server is reachable.
 */
class Memcached extends AbstractCheck
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
    public function __construct($host = '127.0.0.1', $port = 11211)
    {
        if (! is_string($host)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot use %s as host - expecting a string',
                gettype($host)
            ));
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
        if (! class_exists('Memcached', false)) {
            return new Failure('Memcached extension is not loaded');
        }

        try {
            $memcached = new \Memcached();
            $memcached->addServer($this->host, $this->port);
            $stats = @$memcached->getStats();

            if (! $stats ||
                ! is_array($stats) ||
                ! isset($stats[$this->host . ':' . $this->port]) ||
                ($stats[$this->host . ':' . $this->port] === false)
            ) {
                // Attempt a connection to make sure that the server is really down
                if (! @$memcached->getLastDisconnectedServer($this->host, $this->port)) {
                    return new Failure(sprintf(
                        'No memcached server running at host %s on port %s',
                        $this->host,
                        $this->port
                    ));
                }
            }
        } catch (\Exception $e) {
            return new Failure($e->getMessage());
        }

        return new Success(sprintf(
            'Memcached server running at host %s on port %s',
            $this->host,
            $this->port
        ));
    }
}

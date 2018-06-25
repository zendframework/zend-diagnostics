<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2013-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

namespace ZendDiagnostics\Check;

use Exception;
use Memcache as MemcacheService;
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
     * @throws InvalidArgumentException
     */
    public function __construct($host = '127.0.0.1', $port = 11211)
    {
        if (! is_string($host)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot use %s as host - expecting a string',
                gettype($host)
            ));
        }

        $port = (int) $port;
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
     * @see CheckInterface::check()
     */
    public function check()
    {
        if (! class_exists('Memcache', false)) {
            return new Failure('Memcache extension is not loaded');
        }

        try {
            $memcache = new MemcacheService();
            $memcache->addServer($this->host, $this->port);
            $stats = @$memcache->getExtendedStats();

            $authority = sprintf('%s:%d', $this->host, $this->port);

            if (! $stats
                || ! is_array($stats)
                || ! isset($stats[$authority])
                || false === $stats[$authority]
            ) {
                // Attempt a connection to make sure that the server is really down
                if (! @$memcache->connect($this->host, $this->port)) {
                    return new Failure(sprintf(
                        'No memcache server running at host %s on port %s',
                        $this->host,
                        $this->port
                    ));
                }
            }
        } catch (Exception $e) {
            return new Failure($e->getMessage());
        }

        return new Success(sprintf(
            'Memcache server running at host %s on port %s',
            $this->host,
            $this->port
        ));
    }
}

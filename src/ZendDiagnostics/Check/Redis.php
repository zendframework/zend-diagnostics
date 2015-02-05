<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use Predis\Client as PredisClient;
use Redis as RedisExtensionClient;
use ZendDiagnostics\Result\Success;

/**
 * Validate that a Redis service is running
 */
class Redis extends AbstractCheck
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
     * @param string  $host
     * @param int $port
     */
    public function __construct($host = 'localhost', $port = 6379)
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * Perform the check
     *
     * @see \ZendDiagnostics\Check\CheckInterface::check()
     */
    public function check()
    {
        $this->createClient()->ping();

        return new Success();
    }

    /**
     * @return PredisClient|RedisExtensionClient
     *
     * @throws \RuntimeException
     */
    private function createClient()
    {
        if (class_exists('\Redis')) {
            $client = new RedisExtensionClient();
            $client->connect($this->host);

            return $client;
        }

        if (class_exists('Predis\Client')) {
            return new PredisClient(array(
                'host' => $this->host,
                'port' => $this->port,
            ));
        }

        throw new \RuntimeException('Neither the PHP Redis extension or Predis are installed');
    }
}

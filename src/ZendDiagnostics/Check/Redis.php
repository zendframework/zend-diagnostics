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
     * @var string|null
     */
    protected $auth;

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
     * @param string|null $auth
     */
    public function __construct($host = 'localhost', $port = 6379, $auth = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->auth = $auth;
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
     * @throws \RedisException
     * @throws \RuntimeException
     */
    private function createClient()
    {
        if (class_exists('\Redis')) {
            $client = new RedisExtensionClient();
            $client->connect($this->host, $this->port);

            if ($this->auth && false === $client->auth($this->auth)) {
                throw new \RedisException('Failed to AUTH connection');
            }

            return $client;
        }

        if (class_exists('Predis\Client')) {
            $parameters = array(
                'host' => $this->host,
                'port' => $this->port,
            );

            if ($this->auth) {
                $parameters['password'] = $this->auth;
            }

            return new PredisClient($parameters);
        }

        throw new \RuntimeException('Neither the PHP Redis extension or Predis are installed');
    }
}

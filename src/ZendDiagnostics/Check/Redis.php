<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use Predis\Client;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;

/**
 * Validate that a Redis service is running
 *
 * @author Cédric Dugat <cedric@dugat.me>
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
     * @return Failure|Success
     */
    public function check()
    {
        if (!class_exists('Predis\Client', false)) {
            return new Failure('Predis is not installed');
        }

        $client = new Client(array(
            'host' => $this->host,
            'port' => $this->port,
        ));

        if (!$client->ping()) {
            return new Failure(
                sprintf(
                    'No Redis server running at host %s on port %s',
                    $this->host,
                    $this->port
                )
            );
        }

        return new Success();
    }
}

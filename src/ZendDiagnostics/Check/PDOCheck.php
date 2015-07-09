<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use PDO;
use ZendDiagnostics\Result;

/**
 * Ensures a connection to the MySQL server/database is possible.
 */
class PDOCheck implements CheckInterface
{
    private $dsn;
    private $password;
    private $username;
    private $timeout;

    /**
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param int $timeout
     *
     * @return self
     */
    public function __construct($dsn, $username, $password, $timeout = 1)
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->timeout = $timeout;
    }

    /**
     * @return Result\Failure|Result\Success
     */
    public function check()
    {
        $msg = 'Could not talk to database server';

        try {
            $pdo = new PDO($this->dsn, $this->username, $this->password);

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_TIMEOUT, $this->timeout);

            $status = $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
            if (null !== $status) {
                return new Result\Success('Connection to database server was successful.');
            }
        } catch (\PDOException $e) {
            // skip to failure
            $msg .= ', e: ' . $e->getCode();
        }

        return new Result\Failure($msg);
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Check if the database server can be reached';
    }
}

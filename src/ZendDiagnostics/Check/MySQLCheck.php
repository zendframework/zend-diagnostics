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
class MySQLCheck implements CheckInterface
{
    private $dbname;
    private $host;
    private $passwd;
    private $port;
    private $username;

    /**
     * @param string $host
     * @param string $username
     * @param string $passwd
     * @param string $dbname
     * @param int $port
     *
     * @return self
     */
    public function __construct($host, $username, $passwd, $dbname, $port = 3306)
    {
        $this->host = $host;
        $this->username = $username;
        $this->passwd = $passwd;
        $this->dbname = $dbname;
        $this->port = $port;
    }

    /**
     * @return Result\Failure|Result\Success
     */
    public function check()
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8;port=%d',
            $this->host,
            $this->dbname,
            $this->port
        );

        $msg = 'Could not talk to mysql';

        try {
            $pdo = new PDO(
                $dsn,
                $this->username,
                $this->passwd
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_TIMEOUT, 1); // 1 second timeout
            $status = $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
            if (null !== $status) {
                return new Result\Success('Can haz MySQL');
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
        return 'Check if MySQL can be reached';
    }
}

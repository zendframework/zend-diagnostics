<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;

class HttpService extends AbstractCheck
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
     * @var string
     */
    protected $path;

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var int
     */
    protected $content;

    /**
     * @param string $host
     * @param int    $port
     * @param string $path
     * @param int    $statusCode
     * @param null   $content
     */
    public function __construct($host, $port = 80, $path = '/', $statusCode = 200, $content = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->statusCode = $statusCode;
        $this->content = $content;
    }

    /**
     * @see ZendDiagnostics\CheckInterface::check()
     */
    public function check()
    {
        $fp = @fsockopen($this->host, $this->port, $errno, $errstr, 10);
        if (!$fp) {
            return new Failure(sprintf('No http service running at host %s on port %s', $this->host, $this->port));
        }

        $header = "GET {$this->path} HTTP/1.1\r\n";
        $header .= "Host: {$this->host}\r\n";
        $header .= "Connection: close\r\n\r\n";
        fputs($fp, $header);
        $str = '';
        while (!feof($fp)) {
            $str .= fgets($fp, 1024);
        }
        fclose($fp);

        if ($this->statusCode && strpos($str, "HTTP/1.1 {$this->statusCode}") !== 0) {
            return new Failure(" Status code {$this->statusCode} does not match in response from {$this->host}:{$this->port}{$this->path}");
        }

        if ($this->content && !strpos($str, $this->content)) {
            return new Failure(" Content {$this->content} not found in response from {$this->host}:{$this->port}{$this->path}");
        }

        return new Success();
    }
}

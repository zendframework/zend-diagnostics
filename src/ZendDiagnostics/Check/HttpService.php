<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;

/**
 * Attempt connection to given HTTP host and (optionally) check status code and page content.
 */
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
     * @param string $host       Host name or IP address to check.
     * @param int    $port       Port to connect to (defaults to 80)
     * @param string $path       The path to retrieve (defaults to /)
     * @param int    $statusCode (optional) Expected status code
     * @param null   $content    (optional) Expected substring to match against the page content.
     */
    public function __construct($host, $port = 80, $path = '/', $statusCode = null, $content = null)
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

        $header = "GET {$this->path} HTTP/1.0\r\n";
        $header .= "Host: {$this->host}\r\n";
        $header .= "Connection: close\r\n\r\n";
        fputs($fp, $header);
        $str = '';
        while (!feof($fp)) {
            $str .= fgets($fp, 1024);
        }
        fclose($fp);

        if ($this->statusCode && !preg_match("/^HTTP\/[0-9]\.[0-9] {$this->statusCode}/", $str)) {
            return new Failure("Status code {$this->statusCode} does not match response from {$this->host}:{$this->port}{$this->path}");
        }

        if ($this->content && strpos($str, $this->content) === false) {
            return new Failure("Content {$this->content} not found in response from {$this->host}:{$this->port}{$this->path}");
        }

        return new Success();
    }
}

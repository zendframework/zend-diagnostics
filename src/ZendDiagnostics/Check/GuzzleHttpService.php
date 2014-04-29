<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use Guzzle\Http\Client;
use Guzzle\Http\ClientInterface;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;

class GuzzleHttpService extends AbstractCheck
{
    protected $url;
    protected $method;
    protected $body;
    protected $headers;
    protected $statusCode;
    protected $content;
    protected $guzzle;

    /**
     * @param string          $url        The absolute url to check
     * @param array           $headers    An array of headers used to create the request
     * @param array           $options    An array of guzzle options used to create the request
     * @param int             $statusCode The response status code to check
     * @param null            $content    The response content to check
     * @param ClientInterface $guzzle     Instance of guzzle to use
     * @param string          $method     The method of the request
     * @param mixed           $body       The body of the request (used for POST, PUT and DELETE requests)
     */
    public function __construct($url, array $headers = array(), array $options = array(), $statusCode = 200, $content = null, ClientInterface $guzzle = null, $method = 'GET', $body = null)
    {
        $this->url = $url;
        $this->headers = $headers;
        $this->options = $options;
        $this->statusCode = $statusCode;
        $this->content = $content;
        $this->method = $method;
        $this->body = $body;

        if (!$guzzle) {
            $guzzle = new Client();
        }

        $this->guzzle = $guzzle;
    }

    /**
     * @see ZendDiagnostics\CheckInterface::check()
     */
    public function check()
    {
        $response = $this->guzzle->createRequest($this->method, $this->url, $this->headers, $this->body, $this->options)->send();

        if ($this->statusCode !== $statusCode = $response->getStatusCode()) {
            return new Failure("Status code {$this->statusCode} does not match {$statusCode} in response from {$this->url}");
        }

        if ($this->content && (false === strpos($response->getBody(true), $this->content))) {
            return new Failure("Content {$this->content} not found in response from {$this->url}");
        }

        return new Success();
    }
}

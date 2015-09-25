<?php
/**
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDiagnostics\Check;

use Guzzle\Http\Client as Guzzle3Client;
use Guzzle\Http\ClientInterface as Guzzle3ClientInterface;
use GuzzleHttp\Client as Guzzle456Client;
use GuzzleHttp\ClientInterface as Guzzle456ClientInterface;
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
     * @param string                                 $url        The absolute url to check
     * @param array                                  $headers    An array of headers used to create the request
     * @param array                                  $options    An array of guzzle options used to create the request
     * @param int                                    $statusCode The response status code to check
     * @param null                                   $content    The response content to check
     * @param \Guzzle\Http\Client|\GuzzleHttp\Client $guzzle     Instance of guzzle to use
     * @param string                                 $method     The method of the request
     * @param mixed                                  $body       The body of the request (used for POST, PUT and DELETE requests)
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($url, array $headers = array(), array $options = array(), $statusCode = 200, $content = null, $guzzle = null, $method = 'GET', $body = null)
    {
        $this->url = $url;
        $this->headers = $headers;
        $this->options = $options;
        $this->statusCode = (int) $statusCode;
        $this->content = $content;
        $this->method = $method;
        $this->body = $body;

        if (!$guzzle) {
            $guzzle = $this->createGuzzleClient();
        }

        if ((!$guzzle instanceof Guzzle3ClientInterface) && (!$guzzle instanceof Guzzle456ClientInterface)) {
            throw new \InvalidArgumentException('Parameter "guzzle" must be an instance of "\Guzzle\Http\ClientInterface" or "\GuzzleHttp\ClientInterface"');
        }

        $this->guzzle = $guzzle;
    }

    /**
     * @see ZendDiagnostics\CheckInterface::check()
     */
    public function check()
    {
        if ($this->guzzle instanceof Guzzle3ClientInterface) {
            return $this->guzzle3Check();
        }

        return $this->guzzle456Check();
    }

    /**
     * @return Failure|Success
     */
    private function guzzle3Check()
    {
        $response = $this->guzzle->createRequest(
            $this->method,
            $this->url,
            $this->headers,
            $this->body,
            array_merge(array('exceptions' => false), $this->options)
        )->send();

        if ($this->statusCode !== $statusCode = $response->getStatusCode()) {
            return $this->createStatusCodeFailure($statusCode);
        }

        if ($this->content && (false === strpos($response->getBody(true), $this->content))) {
            return $this->createContentFailure();
        }

        return new Success();
    }

    /**
     * @return Failure|Success
     */
    private function guzzle456Check()
    {
        if (method_exists($this->guzzle, 'request')) {
            // guzzle 6
            $response = $this->guzzle->request(
                $this->method,
                $this->url,
                array_merge(
                    array('headers' => $this->headers, 'body' => $this->body, 'exceptions' => false),
                    $this->options
                )
            );
        } else {
            // guzzle 4 and 5
            $request = $this->guzzle->createRequest(
                $this->method,
                $this->url,
                array_merge(
                    array('headers' => $this->headers, 'body' => $this->body, 'exceptions' => false),
                    $this->options
                )
            );
            $response = $this->guzzle->send($request);
        }

        if ($this->statusCode !== $statusCode = (int) $response->getStatusCode()) {
            return $this->createStatusCodeFailure($statusCode);
        }

        if ($this->content && (false === strpos((string) $response->getBody(), $this->content))) {
            return $this->createContentFailure();
        }

        return new Success();
    }

    /**
     * @param int $statusCode
     *
     * @return Failure
     */
    private function createStatusCodeFailure($statusCode)
    {
        return new Failure("Status code {$this->statusCode} does not match {$statusCode} in response from {$this->url}");
    }

    /**
     * @return Failure
     */
    private function createContentFailure()
    {
        return new Failure("Content {$this->content} not found in response from {$this->url}");
    }

    /**
     * @return \Guzzle\Http\Client|\GuzzleHttp\Client
     *
     * @throws \Exception
     */
    private function createGuzzleClient()
    {
        if (class_exists('GuzzleHttp\Client')) {
            return new Guzzle456Client();
        }

        if (!class_exists('Guzzle\Http\Client')) {
            throw new \Exception('Guzzle is required.');
        }

        return new Guzzle3Client();
    }
}

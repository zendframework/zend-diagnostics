<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2013-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

namespace ZendDiagnostics\Check;

use InvalidArgumentException;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Exception\ServerErrorResponseException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
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
     * @param string $url The absolute url to check
     * @param array $headers An array of headers used to create the request
     * @param array $options An array of guzzle options used to create the request
     * @param int $statusCode The response status code to check
     * @param null $content The response content to check
     * @param null|\GuzzleHttp\ClientInterface $guzzle Instance of guzzle to use
     * @param string $method The method of the request
     * @param mixed $body The body of the request (used for POST, PUT and DELETE requests)
     * @throws \InvalidArgumentException
     */
    public function __construct(
        $url,
        array $headers = [],
        array $options = [],
        $statusCode = 200,
        $content = null,
        $guzzle = null,
        $method = 'GET',
        $body = null
    ) {
        $this->url = $url;
        $this->headers = $headers;
        $this->options = $options;
        $this->statusCode = (int) $statusCode;
        $this->content = $content;
        $this->method = $method;
        $this->body = $body;

        if (! $guzzle) {
            $guzzle = $this->createGuzzleClient();
        }

        if (! $guzzle instanceof GuzzleClientInterface) {
            throw new InvalidArgumentException(
                'Parameter "guzzle" must be an instance of \GuzzleHttp\ClientInterface'
            );
        }

        $this->guzzle = $guzzle;
    }

    /**
     * @see ZendDiagnostics\CheckInterface::check()
     */
    public function check()
    {
        if (method_exists($this->guzzle, 'request')) {
            // guzzle 6
            $response = $this->guzzle->request(
                $this->method,
                $this->url,
                array_merge(
                    ['headers' => $this->headers, 'form_params' => $this->body, 'exceptions' => false],
                    $this->options
                )
            );
        } else {
            // guzzle 4 and 5
            $request = $this->guzzle->createRequest(
                $this->method,
                $this->url,
                array_merge(
                    ['headers' => $this->headers, 'body' => $this->body, 'exceptions' => false],
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
        return new Failure(sprintf(
            'Status code %s does not match %s in response from %s',
            $this->statusCode,
            $statusCode,
            $this->url
        ));
    }

    /**
     * @return Failure
     */
    private function createContentFailure()
    {
        return new Failure(sprintf(
            'Content %s not found in response from %s',
            $this->content,
            $this->url
        ));
    }

    /**
     * @return \Guzzle\Http\Client|\GuzzleHttp\Client
     *
     * @throws \Exception
     */
    private function createGuzzleClient()
    {
        if (! class_exists(GuzzleClient::class)) {
            throw new \Exception('Guzzle is required.');
        }

        return new GuzzleClient();
    }
}

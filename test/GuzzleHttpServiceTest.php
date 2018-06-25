<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2013-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

namespace ZendDiagnosticsTest;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler as Guzzle6MockHandler;
use GuzzleHttp\Message\RequestInterface as GuzzleRequestInterface;
use GuzzleHttp\Message\Response as GuzzleResponse;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock as Guzzle5MockSubscriber;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use ReflectionClass;
use ReflectionProperty;
use ZendDiagnostics\Check\CouchDBCheck;
use ZendDiagnostics\Check\GuzzleHttpService;
use ZendDiagnostics\Result\FailureInterface;
use ZendDiagnostics\Result\SuccessInterface;

use function GuzzleHttp\Psr7\parse_response;

class GuzzleHttpServiceTest extends TestCase
{
    protected $responseTemplate = <<< 'EOR'
HTTP/1.1 %d

%s
EOR;

    /**
     * @param array $params
     *
     * @dataProvider couchDbProvider
     */
    public function testCouchDbCheck(array $params)
    {
        $check = new CouchDBCheck($params);
        $this->assertInstanceOf(CouchDbCheck::class, $check);
    }

    /**
     * @dataProvider checkProvider
     */
    public function testGuzzleCheck(
        $content,
        $actualContent,
        $actualStatusCode,
        $resultClass,
        $method = 'GET',
        $body = null
    ) {
        if (! class_exists(GuzzleClient::class)) {
            $this->markTestSkipped('guzzlehttp/guzzle not installed.');
        }

        $check = new GuzzleHttpService(
            'http://www.example.com/foobar',
            [],
            [],
            '200',
            $content,
            $this->getMockGuzzleClient($actualStatusCode, $actualContent),
            $method,
            $body
        );
        $result = $check->check();

        $this->assertInstanceOf($resultClass, $result);
    }

    public function testInvalidClient()
    {
        $this->expectException(InvalidArgumentException::class);
        new GuzzleHttpService('http://example.com', [], [], 200, null, 'not guzzle');
    }

    public function testCanSendJsonRequests()
    {
        $diagnostic = new GuzzleHttpService(
            'https://example.com/foobar',
            ['Content-Type' => 'application/json'],
            [],
            200,
            null,
            null,
            'POST',
            ['foo' => 'bar']
        );

        $r = new ReflectionProperty($diagnostic, 'request');
        $r->setAccessible(true);
        $request = $r->getValue($diagnostic);

        if ($request instanceof RequestInterface) {
            $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        } else {
            $this->assertSame('application/json', $request->getHeader('Content-Type'));
        }

        $body = (string) $request->getBody();
        $this->assertSame(['foo' => 'bar'], json_decode($body, true));
    }

    public function testCanSendArbitraryRequests()
    {
        $toMock = interface_exists(GuzzleRequestInterface::class)
            ? GuzzleRequestInterface::class
            : RequestInterface::class;
        $request = $this->prophesize($toMock)->reveal();

        $diagnostic = new GuzzleHttpService($request);

        $this->assertAttributeSame($request, 'request', $diagnostic);
    }

    public function checkProvider()
    {
        return [
            [null, null, 200, SuccessInterface::class],
            [null, null, 200, SuccessInterface::class, 'POST', ['key' => 'value']],
            [null, null, 200, SuccessInterface::class, 'PUT'],
            [null, null, 404, FailureInterface::class],
            [null, null, 404, FailureInterface::class, 'POST', ['key' => 'value']],
            [null, null, 404, FailureInterface::class, 'PUT'],
            ['foo', 'foobar', 200, SuccessInterface::class],
            ['foo', 'foobar', 200, SuccessInterface::class, 'POST', ['key' => 'value']],
            ['foo', 'foobar', 200, SuccessInterface::class, 'PUT'],
            ['baz', 'foobar', 200, FailureInterface::class],
            ['baz', 'foobar', 200, FailureInterface::class, 'POST', ['key' => 'value']],
            ['baz', 'foobar', 200, FailureInterface::class, 'PUT'],
            ['baz', 'foobar', 500, FailureInterface::class],
        ];
    }

    public function couchDbProvider()
    {
        return [
            'url' => [[
                'url' => 'http://root:party@localhost/hello'
            ]],
            'options' => [[
                'host' => '127.0.0.1',
                'port' => '443',
                'username' => 'test',
                'password' => 'test',
                'dbname' => 'database'
            ]],
        ];
    }

    private function getMockGuzzleClient($statusCode = 200, $content = null)
    {
        $r = new ReflectionClass(GuzzleClient::class);
        if ($r->hasMethod('getEmitter')) {
            // Guzzle 4 and 5:
            return $this->getMockLegacyGuzzleClient($statusCode, $content);
        }

        $response = parse_response(sprintf($this->responseTemplate, $statusCode, (string) $content));

        $handler = new Guzzle6MockHandler();
        $handler->append($response);

        return new GuzzleClient(['handler' => $handler]);
    }

    private function getMockLegacyGuzzleClient($statusCode = 200, $content = null)
    {
        $response = new GuzzleResponse($statusCode, [], Stream::factory((string) $content));
        $client = new GuzzleClient();
        $client->getEmitter()
            ->attach(new Guzzle5MockSubscriber([$response]));
        return $client;
    }
}

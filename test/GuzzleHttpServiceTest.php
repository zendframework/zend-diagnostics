<?php
/**
 * @see       https://github.com/zendframework/zend-diagnostics for the canonical source repository
 * @copyright Copyright (c) 2013-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diagnostics/blob/master/LICENSE.md New BSD License
 */

namespace ZendDiagnosticsTest;

use Guzzle\Http\Client as Guzzle3Client;
use Guzzle\Http\Message\Response as Guzzle3Response;
use GuzzleHttp\Client as Guzzle4And5Client;
use GuzzleHttp\Message\Response as Guzzle4And5Response;
use Guzzle\Plugin\Mock\MockPlugin;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;
use PHPUnit\Framework\TestCase;
use ZendDiagnostics\Check\CouchDBCheck;
use ZendDiagnostics\Check\GuzzleHttpService;
use ZendDiagnostics\Result\FailureInterface;
use ZendDiagnostics\Result\SuccessInterface;

class GuzzleHttpServiceTest extends TestCase
{
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
    public function testGuzzle3Check(
        $content,
        $actualContent,
        $actualStatusCode,
        $resultClass,
        $method = 'GET',
        $body = null
    ) {
        $check = new GuzzleHttpService(
            'http://www.example.com/foobar',
            [],
            [],
            '200',
            $content,
            $this->getMockGuzzle3Client($actualStatusCode, $actualContent),
            $method,
            $body
        );
        $result = $check->check();

        $this->assertInstanceOf($resultClass, $result);
    }

    /**
     * @dataProvider checkProvider
     */
    public function testGuzzle4And5Check(
        $content,
        $actualContent,
        $actualStatusCode,
        $resultClass,
        $method = 'GET',
        $body = null
    ) {
        if (! class_exists(Guzzle4And5Client::class)) {
            $this->markTestSkipped('guzzlehttp/guzzle not installed.');
        }

        $check = new GuzzleHttpService(
            'http://www.example.com/foobar',
            [],
            [],
            '200',
            $content,
            $this->getMockGuzzle4And5Client($actualStatusCode, $actualContent),
            $method,
            $body
        );
        $result = $check->check();

        $this->assertInstanceOf($resultClass, $result);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidClient()
    {
        $check = new GuzzleHttpService('http://example.com', [], [], 200, null, 'not guzzle');
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

    private function getMockGuzzle3Client($statusCode = 200, $content = null)
    {
        $plugin = new MockPlugin();
        $plugin->addResponse(new Guzzle3Response($statusCode, null, $content));

        $client = new Guzzle3Client();
        $client->addSubscriber($plugin);

        return $client;
    }

    private function getMockGuzzle4And5Client($statusCode = 200, $content = null)
    {
        $client = new Guzzle4And5Client();
        $client->getEmitter()
            ->attach(new Mock([new Guzzle4And5Response($statusCode, [], Stream::factory((string) $content))]));

        return $client;
    }
}

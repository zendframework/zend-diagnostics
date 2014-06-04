<?php

namespace ZendDiagnosticsTest;

use Guzzle\Http\Client;
use Guzzle\Http\Message\Response;
use Guzzle\Plugin\Mock\MockPlugin;
use ZendDiagnostics\Check\GuzzleHttpService;

class GuzzleHttpServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider checkProvider
     */
    public function testCheck($content, $actualContent, $actualStatusCode, $resultClass, $method = 'GET', $body = null)
    {
        $check = new GuzzleHttpService(
            'http://www.example.com/foobar',
            array(),
            array(),
            200,
            $content,
            $this->getMockClient($actualStatusCode, $actualContent),
            $method,
            $body
        );
        $result = $check->check();

        $this->assertInstanceOf($resultClass, $result);
    }

    public function checkProvider()
    {
        return array(
            array(null, null, 200, 'ZendDiagnostics\Result\SuccessInterface'),
            array(null, null, 200, 'ZendDiagnostics\Result\SuccessInterface', 'POST', array('key' => 'value')),
            array(null, null, 200, 'ZendDiagnostics\Result\SuccessInterface', 'PUT'),
            array(null, null, 404, 'ZendDiagnostics\Result\FailureInterface'),
            array(null, null, 404, 'ZendDiagnostics\Result\FailureInterface', 'POST', array('key' => 'value')),
            array(null, null, 404, 'ZendDiagnostics\Result\FailureInterface', 'PUT'),
            array('foo', 'foobar', 200, 'ZendDiagnostics\Result\SuccessInterface'),
            array('foo', 'foobar', 200, 'ZendDiagnostics\Result\SuccessInterface', 'POST', array('key' => 'value')),
            array('foo', 'foobar', 200, 'ZendDiagnostics\Result\SuccessInterface', 'PUT'),
            array('baz', 'foobar', 200, 'ZendDiagnostics\Result\FailureInterface'),
            array('baz', 'foobar', 200, 'ZendDiagnostics\Result\FailureInterface', 'POST', array('key' => 'value')),
            array('baz', 'foobar', 200, 'ZendDiagnostics\Result\FailureInterface', 'PUT'),
        );
    }

    private function getMockClient($statusCode = 200, $content = null)
    {
        $plugin = new MockPlugin();
        $plugin->addResponse(new Response($statusCode, null, $content));

        $client = new Client(null, array(
            'request.options' => array(
                'exceptions' => false
            )
        ));
        $client->addSubscriber($plugin);

        return $client;
    }
}

<?php
namespace OpiloClientTest\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Mockery\Mock;
use OpiloClient\Request\OutgoingSMS;
use OpiloClient\Response\SMSId;
use PHPUnit_Framework_TestCase;
use OpiloClient\V2\HttpClient;

class HttpClientTest extends PHPUnit_Framework_TestCase {

    /**
     * Test sendSMS method of HttpClient
     */
    public function testSendSMS() {
        // Mock a Guzzle client to be used
        $responseArray = [
            'messages' => [
                [
                    'id' => 12345
                ]
            ]
        ];
        $mockedGuzzleClient = new MockHandler([
            new Response(200, [], json_encode($responseArray))
        ]);
        // Also, attach a history middleware to later inspect requests and responses
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mockedGuzzleClient);
        $stack->push($history);
        $client = new Client([
            'handler' => $stack,
            'base_url' => getenv('OPILO_URL') . '/ws/api/v2/'
        ]);

        // Make HttpClient to use mocked Guzzle client
        $httpClient = new HttpClient('no-need-for-username', 'no-need-for-password', $client);

        // Send sms
        $message = new OutgoingSMS('9121231234', '9123214321', 'hey');
        $result = $httpClient->sendSMS($message);

        // Inspect response
        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey(0, $result);
        $smsId = $result[0];
        $this->assertInstanceOf(SMSId::class, $smsId);
        $this->assertEquals(12345, $smsId->getId());

        // Inspect request
        $this->assertArrayHasKey(0, $container);
        $this->assertArrayHasKey('request', $container[0]);
    }
}

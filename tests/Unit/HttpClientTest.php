<?php
namespace OpiloClientTest\Unit;

use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Mockery\Mock;
use OpiloClient\Request\IncomingSMS;
use OpiloClient\Request\OutgoingSMS;
use OpiloClient\Response\Inbox;
use OpiloClient\Response\SMSId;
use PHPUnit_Framework_TestCase;
use OpiloClient\Response\Credit;
use OpiloClient\V2\HttpClient;

class HttpClientTest extends PHPUnit_Framework_TestCase {

    /**
     * Mocks a GuzzleHttp client, to return gives responses.
     * @param array $responses Responses to be returned, array of GuzzleHttp\Psr7\Response
     * @return GuzzleHttp\Client Mocked client
     */
    private function mockGuzzleClient($responses) {
        $mockedGuzzleClient = new MockHandler($responses);
        $handler = HandlerStack::create($mockedGuzzleClient);
        $client = new Client([
            'handler' => $handler,
            'base_url' => getenv('OPILO_URL') . '/ws/api/v2/'
        ]);
        return $client;
    }

    public function testSendSMS() {
        // Mock a Guzzle client to be used
        $responseArray = [
            'messages' => [
                [
                    'id' => 12345
                ]
            ]
        ];
        $responses = [
            new Response(200, [], json_encode($responseArray))
        ];
        $client = $this->mockGuzzleClient($responses);

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
    }

    public function testCheckInbox() {
        // Mock a Guzzle client to be used
        $responseArray = [
            'messages' => [
                12345 => [
                    'from' => '9121231234',
                    'to' => '9123214321',
                    'text' => 'سلام دوست عزیز',
                    'received_at' => '2016-03-15 14:27:30',
                ],
                54321 => [
                    'from' => '9123214321',
                    'to' => '9121231234',
                    'text' => 'Hello mate',
                    'received_at' => '2012-05-25 02:10:27',
                ],
            ]
        ];
        $responses = [
            new Response(200, [], json_encode($responseArray))
        ];
        $client = $this->mockGuzzleClient($responses);

        // Make HttpClient to use mocked Guzzle client
        $httpClient = new HttpClient('no-need-for-username', 'no-need-for-password', $client);

        // Call checkInbox
        $result = $httpClient->checkInbox();

        // Inspect $result
        $this->assertInstanceOf(Inbox::class, $result);
        $messages = $result->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertCount(2, $messages);
        $first = $messages[0];
        $this->assertInstanceOf(IncomingSMS::class, $first);
        $this->assertEquals(12345, $first->getOpiloId());
        $this->assertEquals('9121231234', $first->getFrom());
        $this->assertEquals('9123214321', $first->getTo());
        $this->assertEquals('سلام دوست عزیز', $first->getText());
        $this->assertInstanceOf(DateTime::class, $first->getReceivedAt());
        $this->assertEquals('2016-03-15 14:27:30', $first->getReceivedAt()->format('Y-m-d H:i:s'));
        $second = $messages[1];
        $this->assertInstanceOf(IncomingSMS::class, $second);
        $this->assertEquals(54321, $second->getOpiloId());
        $this->assertEquals('9123214321', $second->getFrom());
        $this->assertEquals('9121231234', $second->getTo());
        $this->assertEquals('Hello mate', $second->getText());
        $this->assertInstanceOf(DateTime::class, $second->getReceivedAt());
        $this->assertEquals('2012-05-25 02:10:27', $second->getReceivedAt()->format('Y-m-d H:i:s'));
    }

    public function testGetCredit() {
        // Mock a Guzzle client to be used
        $responseArray = [
            'sms_page_count' => 33
        ];
        $responses = [
            new Response(200, [], json_encode($responseArray))
        ];
        $client = $this->mockGuzzleClient($responses);

        // Make HttpClient to use mocked Guzzle client
        $httpClient = new HttpClient('no-need-for-username', 'no-need-for-password', $client);

        // Call getCredit
        $result = $httpClient->getCredit();

        // Inspect $result
        $this->assertInstanceOf(Credit::class, $result);
        $this->assertEquals(33, $result->getSmsPageCount());
    }
}

<?php
namespace DelugePHP;

use TestDoubles\ClientStub;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\json_encode;

class DelugeClientTest extends \PHPUnit_Framework_TestCase
{
    const TORRENT_INFO_HASH = 'AAAABBBBCCCCDDDD1234';
    const SERVER_URI = 'http://torrent.client.uri';
    const SERVER_PASSWORD = 'password';

    /**
     * @var string
     */
    private $magnetUri;

    /**
     * @var ClientStub
     */
    private $guzzleClient;
    /**
     * @var DelugeClient
     */
    private $client;

    public function setUp()
    {
        $this->magnetUri = 'magnet:?xt=urn:btih:' . static::TORRENT_INFO_HASH;

        $this->guzzleClient = new ClientStub();
        $this->client = new DelugeClient(
            $this->guzzleClient,
            new CookieJar(true),
            static::SERVER_URI,
            static::SERVER_PASSWORD
        );
    }

    public function testAddTorrent()
    {
        $this->authenticateSuccessfully();
        $this->setAddTorrentResponse(static::TORRENT_INFO_HASH);

        $this->client->addTorrentByMagnetUri($this->magnetUri);
    }

    /**
     * @expectedException \DelugePHP\AuthenticationException
     */
    public function testAddTorrentWithFailedAuthentication()
    {
        $this->authenticateUnsuccessfully();

        $this->client->addTorrentByMagnetUri($this->magnetUri);
    }

    /**
     * @expectedException \DelugePHP\AddTorrentException
     */
    public function testAddTorrentWithErrorResponse()
    {
        $this->authenticateSuccessfully();
        $this->setAddTorrentResponse(static::TORRENT_INFO_HASH, 'error');

        $this->client->addTorrentByMagnetUri($this->magnetUri);
    }

    /**
     * @expectedException \DelugePHP\AddTorrentException
     */
    public function testAddTorrentWithNullResult()
    {
        $this->authenticateSuccessfully();
        $this->setAddTorrentResponse(null);

        $this->client->addTorrentByMagnetUri($this->magnetUri);
    }

    /**
     * @expectedException \DelugePHP\InvalidHashException
     */
    public function testAddTorrentWithInvalidHash()
    {
        $this->authenticateSuccessfully();
        $this->setAddTorrentResponse('foobar');

        $this->client->addTorrentByMagnetUri($this->magnetUri);
    }

    private function authenticateSuccessfully()
    {
        $this->setClientResponse(
            DelugeClient::METHOD_AUTHENTICATE,
            200,
            ['Set-Cookie' => ['_session_id=foobarbaz']],
            json_encode(['result' => true])
        );
    }

    private function authenticateUnsuccessfully()
    {
        $this->setClientResponse(DelugeClient::METHOD_AUTHENTICATE, 200, [], json_encode(['result' => false]));
    }

    /**
     * @param string $delugeMethod
     * @param int $status
     * @param array $headers
     * @param string $body
     */
    private function setClientResponse($delugeMethod, $status, $headers, $body)
    {
        $this->guzzleClient->setResponse($delugeMethod, new Response($status, $headers, $body));
    }

    /**
     * @param string $result
     * @param string|null $error
     */
    private function setAddTorrentResponse($result, $error = null)
    {
        $this->guzzleClient->setResponse(
            DelugeClient::METHOD_ADD_MAGNET,
            new Response(
                200,
                [],
                json_encode(
                    [
                        'result' => $result,
                        'error' => $error,
                    ]
                )
            )
        );
    }
}

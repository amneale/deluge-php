<?php
namespace DelugePHP;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Cookie\SetCookie;
use function GuzzleHttp\json_decode;

class DelugeClient
{
    const METHOD_AUTHENTICATE = 'auth.login';
    const METHOD_ADD_MAGNET = 'core.add_torrent_magnet';

    /**
     * @var ClientInterface
     */
    private $guzzleClient;

    /**
     * @var CookieJarInterface
     */
    private $cookieJar;

    /**
     * @var string
     */
    private $uri;

    /**
     * @var string
     */
    private $password;

    /**
     * @param ClientInterface $guzzleClient
     * @param CookieJarInterface $cookieJar
     * @param string $uri
     * @param string $password
     */
    public function __construct(ClientInterface $guzzleClient, CookieJarInterface $cookieJar, $uri, $password)
    {
        $this->guzzleClient = $guzzleClient;
        $this->cookieJar = $cookieJar;
        $this->uri = $uri;
        $this->password = $password;
    }

    /**
     * @return bool
     */
    private function isAuthenticated()
    {
        /** @var SetCookie $cookie */
        foreach ($this->cookieJar as $cookie) {
            if ('_session_id' === $cookie->getName() && false === $cookie->isExpired()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws AuthenticationException
     */
    private function authenticate()
    {
        $response = $this->guzzleClient->request(
            'post',
            $this->uri,
            [
                'json' => [
                    'method' => static::METHOD_AUTHENTICATE,
                    'params' => [$this->password],
                    'id' => uniqid(),
                ],
                'cookies' => $this->cookieJar,
            ]
        );

        $responseBody = \GuzzleHttp\json_decode((string) $response->getBody());

        if (!$responseBody->result || !$this->isAuthenticated()) {
            throw new AuthenticationException('Failed to authenticate with torrent client');
        }
    }

    /**
     * @param string $magnetUri
     *
     * @throws AddTorrentException
     * @throws InvalidHashException
     */
    public function addTorrentByMagnetUri($magnetUri)
    {
        if (!$this->isAuthenticated()) {
            $this->authenticate();
        }

        $response = $this->guzzleClient->request(
            'post',
            $this->uri,
            [
                'json' => [
                    'method' => static::METHOD_ADD_MAGNET,
                    'params' => [$magnetUri, []],
                    'id' => uniqid(),
                ],
                'cookies' => $this->cookieJar,
            ]
        );

        $responseBody = json_decode((string) $response->getBody());

        if (null !== $responseBody->error) {
            throw new AddTorrentException($responseBody->error);
        }

        if (null === $responseBody->result) {
            throw new AddTorrentException('It looks like this torrent has already been added');
        }

        $infoHash = $this->getInfoHashFromMagnetUri($magnetUri);
        if (0 !== strcasecmp($responseBody->result, $infoHash)) {
            throw new InvalidHashException(
                "The hash returned '{$responseBody->result}'" .
                " did not match the hash submitted '$infoHash'"
            );
        }
    }

    /**
     * @param string $magnetUri
     *
     * @return string|null
     */
    private function getInfoHashFromMagnetUri($magnetUri)
    {
        preg_match('/xt=urn:btih:([a-zA-Z0-9]*)/', $magnetUri, $matches);

        return isset($matches[1]) ? $matches[1] : null;
    }
}

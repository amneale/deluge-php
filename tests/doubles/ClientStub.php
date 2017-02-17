<?php
namespace TestDoubles;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ClientStub implements ClientInterface
{
    /**
     * @var ResponseInterface[]
     */
    private $response;

    /**
     * @inheritdoc
     */
    public function send(RequestInterface $request, array $options = [])
    {
        throw new MethodNotImplementedException();
    }

    /**
     * @inheritdoc
     */
    public function sendAsync(RequestInterface $request, array $options = [])
    {
        throw new MethodNotImplementedException();
    }

    /**
     * @inheritdoc
     */
    public function request($method, $uri, array $options = [])
    {
        $delugeMethod = $options['json']['method'];

        if (!isset($this->response[$delugeMethod])) {
            throw new \Exception("Response not set for $delugeMethod deluge method");
        }

        $response = $this->response[$delugeMethod];

        if (isset($options['cookies']) && $options['cookies'] instanceof CookieJarInterface) {
            /** @var CookieJarInterface $cookieJar */
            $cookieJar = $options['cookies'];
            $cookieJar->extractCookies(new Request($method, $uri), $response);
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function requestAsync($method, $uri, array $options = [])
    {
        throw new MethodNotImplementedException();
    }

    /**
     * @inheritdoc
     */
    public function getConfig($option = null)
    {
        throw new MethodNotImplementedException();
    }

    /**
     * @param string $delugeMethod
     * @param ResponseInterface $response
     */
    public function setResponse($delugeMethod, ResponseInterface $response)
    {
        $this->response[$delugeMethod] = $response;
    }
}

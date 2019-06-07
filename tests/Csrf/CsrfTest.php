<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Csrf\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Container;
use Spiral\Csrf\Config\CsrfConfig;
use Spiral\Csrf\Middleware\CsrfFirewall;
use Spiral\Csrf\Middleware\CsrfMiddleware;
use Spiral\Csrf\Middleware\StrictCsrfFirewall;
use Spiral\Http\Config\HttpConfig;
use Spiral\Http\Http;
use Spiral\Http\Pipeline;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

class CsrfTest extends TestCase
{
    private $container;

    public function setUp()
    {
        $this->container = new Container();
        $this->container->bind(CsrfConfig::class, new CsrfConfig([
            'cookie'   => 'csrf-token',
            'length'   => 16,
            'lifetime' => 86400
        ]));

        $this->container->bind(
            ResponseFactoryInterface::class,
            new TestResponseFactory(new HttpConfig(['headers' => []]))
        );
    }

    public function testGet()
    {
        $core = $this->httpCore([CsrfMiddleware::class]);
        $core->setHandler(function ($r) {
            return $r->getAttribute(CsrfMiddleware::ATTRIBUTE);
        });

        $response = $this->get($core, '/');
        $this->assertSame(200, $response->getStatusCode());

        $cookies = $this->fetchCookies($response);

        $this->assertArrayHasKey('csrf-token', $cookies);
        $this->assertSame($cookies['csrf-token'], (string)$response->getBody());

    }

    /**
     * @expectedException \RuntimeException
     */
    public function testLengthException()
    {
        $this->container->bind(CsrfConfig::class, new CsrfConfig([
            'cookie'   => 'csrf-token',
            'length'   => 0,
            'lifetime' => 86400
        ]));

        $core = $this->httpCore([CsrfMiddleware::class]);
        $core->setHandler(function () {
            return 'all good';
        });

        $response = $this->get($core, '/');
    }

    public function testPostForbidden()
    {
        $core = $this->httpCore([CsrfMiddleware::class, CsrfFirewall::class]);
        $core->setHandler(function () {
            return 'all good';
        });

        $response = $this->post($core, '/');
        $this->assertSame(412, $response->getStatusCode());
    }

    /**
     * @expectedException \LogicException
     */
    public function testLogicException()
    {
        $core = $this->httpCore([CsrfFirewall::class]);
        $core->setHandler(function () {
            return 'all good';
        });

        $response = $this->post($core, '/');
    }

    public function testPostOK()
    {
        $core = $this->httpCore([CsrfMiddleware::class, CsrfFirewall::class]);
        $core->setHandler(function () {
            return 'all good';
        });

        $response = $this->get($core, '/');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('all good', (string)$response->getBody());

        $cookies = $this->fetchCookies($response);

        $response = $this->post($core, '/', [], [], ['csrf-token' => $cookies['csrf-token']]);

        $this->assertSame(412, $response->getStatusCode());

        $response = $this->post($core, '/', [
            'csrf-token' => $cookies['csrf-token']
        ], [], ['csrf-token' => $cookies['csrf-token']]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('all good', (string)$response->getBody());
    }

    public function testHeaderOK()
    {
        $core = $this->httpCore([CsrfMiddleware::class, CsrfFirewall::class]);
        $core->setHandler(function () {
            return 'all good';
        });

        $response = $this->get($core, '/');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('all good', (string)$response->getBody());

        $cookies = $this->fetchCookies($response);

        $response = $this->post($core, '/', [], [], ['csrf-token' => $cookies['csrf-token']]);

        $this->assertSame(412, $response->getStatusCode());

        $response = $this->post($core, '/', [], [
            'X-CSRF-Token' => $cookies['csrf-token']
        ], ['csrf-token' => $cookies['csrf-token']]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('all good', (string)$response->getBody());
    }

    public function testHeaderOKStrict()
    {
        $core = $this->httpCore([CsrfMiddleware::class, StrictCsrfFirewall::class]);
        $core->setHandler(function () {
            return 'all good';
        });

        $response = $this->get($core, '/');
        $this->assertSame(412, $response->getStatusCode());

        $cookies = $this->fetchCookies($response);

        $response = $this->get($core, '/', [], [], ['csrf-token' => $cookies['csrf-token']]);

        $this->assertSame(412, $response->getStatusCode());

        $response = $this->get($core, '/', [], [
            'X-CSRF-Token' => $cookies['csrf-token']
        ], ['csrf-token' => $cookies['csrf-token']]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('all good', (string)$response->getBody());
    }

    protected function httpCore(array $middleware = []): Http
    {
        $config = new HttpConfig([
            'basePath'   => '/',
            'headers'    => [
                'Content-Type' => 'text/html; charset=UTF-8'
            ],
            'middleware' => $middleware
        ]);

        return new Http(
            $config,
            new Pipeline($this->container),
            new TestResponseFactory($config),
            $this->container
        );
    }

    protected function get(
        Http $core,
        $uri,
        array $query = [],
        array $headers = [],
        array $cookies = []
    ): ResponseInterface {
        return $core->handle($this->request($uri, 'GET', $query, $headers, $cookies));
    }

    protected function post(
        Http $core,
        $uri,
        array $data = [],
        array $headers = [],
        array $cookies = []
    ): ResponseInterface {
        return $core->handle($this->request($uri, 'POST', [], $headers, $cookies)->withParsedBody($data));
    }

    protected function request(
        $uri,
        string $method,
        array $query = [],
        array $headers = [],
        array $cookies = []
    ): ServerRequest {
        return new ServerRequest(
            [],
            [],
            $uri,
            $method,
            'php://input',
            $headers, $cookies,
            $query
        );
    }

    protected function fetchCookies(ResponseInterface $response)
    {
        $result = [];

        foreach ($response->getHeaders() as $line) {
            $cookie = explode('=', join("", $line));
            $result[$cookie[0]] = rawurldecode(substr($cookie[1], 0, strpos($cookie[1], ';')));
        }

        return $result;
    }
}

final class TestResponseFactory implements ResponseFactoryInterface
{
    /** @var HttpConfig */
    protected $config;

    /**
     * @param HttpConfig $config
     */
    public function __construct(HttpConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param int    $code
     * @param string $reasonPhrase
     *
     * @return ResponseInterface
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        $response = new Response('php://memory', $code, []);
        $response = $response->withStatus($code, $reasonPhrase);

        foreach ($this->config->getBaseHeaders() as $header => $value) {
            $response = $response->withAddedHeader($header, $value);
        }

        return $response;
    }
}
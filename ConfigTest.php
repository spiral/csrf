<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Http\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Http\Config\HttpConfig;
use Spiral\Http\Middleware\CookiesMiddleware;
use Spiral\Http\Uri;

class ConfigTest extends TestCase
{
    public function testBasePath()
    {
        $c = new HttpConfig([
            'basePath' => '/'
        ]);

        $this->assertSame('/', $c->getBasePath());
    }

    public function testBaseHeaders()
    {
        $c = new HttpConfig([
            'headers' => [
                'key' => 'value'
            ]
        ]);

        $this->assertSame(['key' => 'value'], $c->getBaseHeaders());
    }

    public function testBaseMiddleware()
    {
        $c = new HttpConfig([
            'middleware' => [CookiesMiddleware::class]
        ]);

        $this->assertSame([CookiesMiddleware::class], $c->getMiddlewares());
    }

    public function testBaseMiddlewareFallback()
    {
        $c = new HttpConfig([
            'middlewares' => [CookiesMiddleware::class]
        ]);

        $this->assertSame([CookiesMiddleware::class], $c->getMiddlewares());
    }

    public function testCsrf()
    {
        $c = new HttpConfig([
            'csrf' => [
                'cookie'   => 'csrf-token',
                'length'   => 16,
                'lifetime' => 86400
            ]
        ]);

        $this->assertSame('csrf-token', $c->getCsrfCookie());
        $this->assertSame(16, $c->getCsrfLength());
        $this->assertSame(86400, $c->getCsrfLifetime());
        $this->assertSame(false, $c->csrfSecure());

        $c = new HttpConfig([
            'csrf' => [
                'cookie' => 'csrf-token',
                'length' => 16,
                'secure' => true
            ]
        ]);

        $this->assertSame(null, $c->getCsrfLifetime());
        $this->assertSame(true, $c->csrfSecure());
    }

    public function testCookies()
    {
        $c = new HttpConfig([
            'cookies' => [
                'domain'   => '.%s',
                'method'   => HttpConfig::COOKIE_ENCRYPT,
                'excluded' => ['PHPSESSID', 'csrf-token']
            ],
        ]);

        $this->assertSame(HttpConfig::COOKIE_ENCRYPT, $c->cookieProtectionMethod());
        $this->assertSame(['PHPSESSID', 'csrf-token'], $c->cookiesExcluded());
    }

    public function testCookieDomain()
    {
        $c = new HttpConfig([
            'cookies' => [
                'domain' => '.%s',
            ],
        ]);

        $this->assertSame('.domain.com', $c->cookieDomain(new Uri('http://domain.com/')));
        $this->assertSame('.domain.com', $c->cookieDomain(new Uri('https://domain.com/')));
        $this->assertSame('.domain.com', $c->cookieDomain(new Uri('https://domain.com:9090/')));
        $this->assertSame(null, $c->cookieDomain(new Uri('/')));
        $this->assertSame('localhost', $c->cookieDomain(new Uri('localhost:9090/')));

        $this->assertSame('192.169.1.10', $c->cookieDomain(new Uri('http://192.169.1.10:8080/')));

        $c = new HttpConfig([
            'cookies' => [
                'domain' => '.doo.com',
            ],
        ]);

        $this->assertSame('.doo.com', $c->cookieDomain(new Uri('http://domain.com/')));
    }
}
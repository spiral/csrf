<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Csrf\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\Csrf\Config\CsrfConfig;

/**
 * Provides generic CSRF protection using cookie as token storage. Set "csrfToken" attribute to
 * request.
 *
 * Do not use middleware without CookieManager at top!
 *
 * @see https://www.owasp.org/index.php/Cross-Site_Request_Forgery_(CSRF)_Prevention_Cheat_Sheet#Double_Submit_Cookie
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    public const ATTRIBUTE = 'csrfToken';

    /** @var CsrfConfig */
    protected $config = null;

    /**
     * @param CsrfConfig $config
     */
    public function __construct(CsrfConfig $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if (isset($request->getCookieParams()[$this->config->getCookie()])) {
            $token = $request->getCookieParams()[$this->config->getCookie()];
        } else {
            //Making new token
            $token = $this->random($this->config->getTokenLength());

            //Token cookie!
            $cookie = $this->tokenCookie($token);
        }

        //CSRF issues must be handled by Firewall middleware
        $response = $handler->handle($request->withAttribute(static::ATTRIBUTE, $token));

        if (!empty($cookie)) {
            return $response->withAddedHeader('Set-Cookie', $cookie);
        }

        return $response;
    }

    /**
     * Create a random string with desired length.
     *
     * @param int $length String length. 32 symbols by default.
     * @return string
     */
    private function random(int $length = 32): string
    {
        try {
            if (empty($string = random_bytes($length))) {
                throw new \RuntimeException("Unable to generate random string");
            }
        } catch (\Throwable $e) {
            throw new \RuntimeException("Unable to generate random string", $e->getCode(), $e);
        }

        return substr(base64_encode($string), 0, $length);
    }

    /**
     * Generate CSRF cookie.
     *
     * @param string $token
     * @return string
     */
    protected function tokenCookie(string $token): string
    {
        $header = [rawurlencode($this->config->getCookie()) . '=' . rawurlencode((string)$token)];

        if ($this->config->getCookieLifetime() !== null) {
            $header[] = 'Expires=' . gmdate(\DateTime::COOKIE, time() + $this->config->getCookieLifetime());
            $header[] = 'Max-Age=' . $this->config->getCookieLifetime();
        }

        if ($this->config->isCookieSecure()) {
            $header[] = 'Secure';
        }

        $header[] = 'HttpOnly';

        return join('; ', $header);
    }
}
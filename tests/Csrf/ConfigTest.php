<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Csrf\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Csrf\Config\CsrfConfig;

class ConfigTest extends TestCase
{
    public function testCsrf()
    {
        $c = new CsrfConfig([
            'cookie'   => 'csrf-token',
            'length'   => 16,
            'lifetime' => 86400
        ]);

        $this->assertSame('csrf-token', $c->getCookie());
        $this->assertSame(16, $c->getTokenLength());
        $this->assertSame(86400, $c->getCookieLifetime());
        $this->assertSame(false, $c->isCookieSecure());

        $c = new CsrfConfig([
            'cookie' => 'csrf-token',
            'length' => 16,
            'secure' => true
        ]);

        $this->assertSame(null, $c->getCookieLifetime());
        $this->assertSame(true, $c->isCookieSecure());
    }
}
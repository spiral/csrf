<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Csrf\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Csrf\Config\CsrfConfig;

class ConfigTest extends TestCase
{
    public function testCsrf(): void
    {
        $c = new CsrfConfig([
            'cookie'   => 'csrf-token',
            'length'   => 16,
            'lifetime' => 86400
        ]);

        self::assertSame('csrf-token', $c->getCookie());
        self::assertSame(16, $c->getTokenLength());
        self::assertSame(86400, $c->getCookieLifetime());
        self::assertFalse($c->isCookieSecure());

        $c = new CsrfConfig([
            'cookie' => 'csrf-token',
            'length' => 16,
            'secure' => true
        ]);

        self::assertNull($c->getCookieLifetime());
        self::assertTrue($c->isCookieSecure());
    }
}

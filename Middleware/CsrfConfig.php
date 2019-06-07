<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Http\Config;

use Spiral\Core\InjectableConfig;

final class CsrfConfig extends InjectableConfig
{
    const CONFIG = 'csrf';

    /**
     * @var array
     */
    protected $config = [
        'cookie'   => 'csrf-token',
        'length'   => 16,
        'lifetime' => 86400
    ];

    /**
     * @return string
     */
    public function getCsrfCookie(): string
    {
        return $this->config['csrf']['cookie'];
    }

    /**
     * @return int
     */
    public function getCsrfLength(): int
    {
        return $this->config['csrf']['length'];
    }

    /**
     * @return int|null
     */
    public function getCsrfLifetime(): ?int
    {
        return $this->config['csrf']['lifetime'] ?? null;
    }

    /**
     * @return bool
     */
    public function csrfSecure(): bool
    {
        return !empty($this->config['csrf']['secure']);
    }
}
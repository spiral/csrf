<?php

declare(strict_types=1);

namespace Spiral\Tests\Csrf;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Spiral\Http\Config\HttpConfig;
use Nyholm\Psr7\Response;

final class TestResponseFactory implements ResponseFactoryInterface
{
    protected readonly HttpConfig $config;

    public function __construct(HttpConfig $config)
    {
        $this->config = $config;
    }

    
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        $response = new Response($code);
        $response = $response->withStatus($code, $reasonPhrase);

        foreach ($this->config->getBaseHeaders() as $header => $value) {
            $response = $response->withAddedHeader($header, $value);
        }

        return $response;
    }
}

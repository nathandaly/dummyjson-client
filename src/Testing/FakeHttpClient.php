<?php

declare(strict_types=1);

namespace Natedaly\DummyjsonClient\Testing;

use Natedaly\DummyjsonClient\Contracts\HttpClient;

final class FakeHttpClient implements HttpClient
{
    private array $responses = [];

    public function queue(array $response): void
    {
        $this->responses[] = $response;
    }

    public function get(string $uri, array $query = [], array $options = []): array
    {
        return array_shift($this->responses) ?? [];
    }

    public function post(string $uri, array $payload = [], array $options = []): array
    {
        return array_shift($this->responses) ?? [];
    }
}

<?php

declare(strict_types=1);

namespace Natedaly\DummyjsonClient\Contracts;

interface HttpClient
{
    public function get(string $uri, array $query = [], array $options = []): array;

    public function post(string $uri, array $payload = [], array $options = []): array;
}

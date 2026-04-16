<?php

declare(strict_types=1);

namespace Natedaly\DummyjsonClient\Contracts;

interface HttpClient
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function get(string $uri, array $query = [], array $options = []): array;

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function post(string $uri, array $payload = [], array $options = []): array;
}

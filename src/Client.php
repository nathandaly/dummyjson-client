<?php

declare(strict_types=1);

namespace Natedaly\DummyjsonClient;

use Natedaly\DummyjsonClient\Contracts\DummyJsonClientInterface;
use Natedaly\DummyjsonClient\Contracts\HttpClient;
use Natedaly\DummyjsonClient\Contracts\UserServiceInterface;
use Natedaly\DummyjsonClient\Http\GuzzleHttpClient;
use Natedaly\DummyjsonClient\Services\UserService;

final class Client implements DummyJsonClientInterface
{
    private ?UserServiceInterface $usersService = null;

    public function __construct(
        private readonly HttpClient $httpClient,
    ) {
    }

    public static function make(string $baseUri, array $options = []): self
    {
        return new self(GuzzleHttpClient::make($baseUri, $options));
    }

    public function users(): UserServiceInterface
    {
        return $this->usersService ??= new UserService($this->httpClient);
    }
}

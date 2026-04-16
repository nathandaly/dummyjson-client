<?php

declare(strict_types=1);

namespace Natedaly\DummyjsonClient;

use Natedaly\DummyjsonClient\Contracts\DummyJsonClientInterface;
use Natedaly\DummyjsonClient\Contracts\HttpClient;
use Natedaly\DummyjsonClient\Contracts\UserServiceInterface;
use Natedaly\DummyjsonClient\Http\GuzzleHttpClient;
use Natedaly\DummyjsonClient\Services\UserService;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Client implements DummyJsonClientInterface
{
    private ?UserServiceInterface $usersService = null;

    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public static function make(
        string $baseUri,
        array $options = [],
        LoggerInterface $logger = new NullLogger(),
    ): self {
        return new self(GuzzleHttpClient::make($baseUri, $options), $logger);
    }

    public function users(): UserServiceInterface
    {
        return $this->usersService ??= new UserService($this->httpClient, $this->logger);
    }
}

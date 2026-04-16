<?php

declare(strict_types=1);

namespace Natedaly\DummyjsonClient\Services;

use Natedaly\DummyjsonClient\Contracts\HttpClient;
use Natedaly\DummyjsonClient\Dto\UserDto;
use RuntimeException;

final readonly class UserService
{
    public function __construct(
        private HttpClient $client,
    ) {
    }

    public function getUser(int $id): UserDto
    {
        $data = $this->client->get("/users/{$id}");

        if (empty($data)) {
            throw new RuntimeException('User not found');
        }

        return UserDto::fromArray($data);
    }
}
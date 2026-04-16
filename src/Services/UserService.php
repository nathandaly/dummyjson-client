<?php

declare(strict_types=1);

namespace Natedaly\DummyjsonClient\Services;

use Natedaly\DummyjsonClient\Contracts\HttpClient;
use Natedaly\DummyjsonClient\Dto\UserDto;
use Natedaly\DummyjsonClient\Exceptions\ApiNotFoundException;
use Natedaly\DummyjsonClient\Query\UserQuery;

final readonly class UserService
{
    public function __construct(
        private HttpClient $client,
    ) {
    }

    public function getById(int $id): UserDto
    {
        $data = $this->client->get("/users/{$id}");

        if (empty($data)) {
            throw new ApiNotFoundException('User not found');
        }

        return UserDto::fromArray($data);
    }

    public function get(): UserQuery
    {
        return new UserQuery($this->client);
    }

    public function create(string $firstName, string $lastName, string $email): UserDto
    {
        $data = $this->client->post('/users/add', [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
        ]);

        return UserDto::fromArray($data);
    }
}
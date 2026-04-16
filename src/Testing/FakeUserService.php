<?php

declare(strict_types=1);

namespace Natedaly\DummyjsonClient\Testing;

use Natedaly\DummyjsonClient\Contracts\UserServiceInterface;
use Natedaly\DummyjsonClient\Dto\UserDto;
use Natedaly\DummyjsonClient\Exceptions\ApiNotFoundException;
use Natedaly\DummyjsonClient\Query\UserQuery;

final class FakeUserService implements UserServiceInterface
{
    private array $users = [];

    private int $nextId = 1;

    public function addUser(UserDto $user): void
    {
        $this->users[$user->id] = $user;
        $this->nextId = max($this->nextId, $user->id + 1);
    }

    public function getById(int $id): UserDto
    {
        return $this->users[$id] ?? throw new ApiNotFoundException(
            sprintf('User %d not found.', $id),
        );
    }

    public function get(): UserQuery
    {
        $httpClient = new FakeHttpClient();
        $httpClient->queue([
            'users' => array_values(array_map(
                static fn (UserDto $user): array => $user->toArray(),
                $this->users,
            )),
            'total' => count($this->users),
            'skip' => 0,
            'limit' => count($this->users),
        ]);

        return new UserQuery($httpClient);
    }

    public function create(string $firstName, string $lastName, string $email): UserDto
    {
        $user = new UserDto(
            id: $this->nextId++,
            firstName: $firstName,
            lastName: $lastName,
            email: $email,
        );

        $this->users[$user->id] = $user;

        return $user;
    }
}

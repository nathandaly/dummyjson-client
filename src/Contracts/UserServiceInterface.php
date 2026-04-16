<?php

declare(strict_types=1);

namespace Natedaly\DummyjsonClient\Contracts;

use Natedaly\DummyjsonClient\Dto\UserDto;
use Natedaly\DummyjsonClient\Query\UserQuery;

interface UserServiceInterface
{
    public function getById(int $id): UserDto;

    public function get(): UserQuery;

    public function create(string $firstName, string $lastName, string $email): UserDto;
}

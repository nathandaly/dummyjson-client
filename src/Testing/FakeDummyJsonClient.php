<?php

declare(strict_types=1);

namespace Natedaly\DummyjsonClient\Testing;

use Natedaly\DummyjsonClient\Contracts\DummyJsonClientInterface;
use Natedaly\DummyjsonClient\Contracts\UserServiceInterface;

final class FakeDummyJsonClient implements DummyJsonClientInterface
{
    public function __construct(
        private readonly UserServiceInterface $users,
    ) {
    }

    public function users(): UserServiceInterface
    {
        return $this->users;
    }
}

<?php

declare(strict_types=1);

namespace Natedaly\DummyjsonClient\Contracts;

interface DummyJsonClientInterface
{
    public function users(): UserServiceInterface;
}

<?php

declare(strict_types=1);

namespace Natedaly\DummyjsonClient\Dto;

use JsonSerializable;
use Natedaly\DummyjsonClient\Traits\HasArrayable;

final readonly class UserDto implements JsonSerializable
{
    use HasArrayable;

    public function __construct(
        public int $id,
        public string $firstName,
        public string $lastName,
        public string $email,
    ) {}

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

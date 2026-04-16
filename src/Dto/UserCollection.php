<?php

declare(strict_types=1);

namespace Natedaly\DummyjsonClient\Dto;

final readonly class UserCollection
{
    /**
     * @param UserDto[] $users
     */
    public function __construct(
        public array $users,
        public int $total,
        public int $skip,
        public int $limit,
    ) {}

    /**
     * @param array{users: array<int, array<string, mixed>>, total: int, skip: int, limit: int} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            users: array_map(
                static fn(array $user): UserDto => UserDto::fromArray($user),
                $data['users'],
            ),
            total: $data['total'],
            skip: $data['skip'],
            limit: $data['limit'],
        );
    }
}

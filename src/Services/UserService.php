<?php

declare(strict_types=1);

namespace Natedaly\DummyjsonClient\Services;

use Natedaly\DummyjsonClient\Contracts\HttpClient;
use Natedaly\DummyjsonClient\Contracts\UserServiceInterface;
use Natedaly\DummyjsonClient\Dto\UserDto;
use Natedaly\DummyjsonClient\Exceptions\ApiNotFoundException;
use Natedaly\DummyjsonClient\Exceptions\DummyJsonException;
use Natedaly\DummyjsonClient\Query\UserQuery;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class UserService implements UserServiceInterface
{
    public function __construct(
        private HttpClient $client,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getById(int $id): UserDto
    {
        try {
            $data = $this->client->get("/users/{$id}");

            if (empty($data)) {
                throw new ApiNotFoundException('User not found');
            }

            return UserDto::fromArray($data);
        } catch (DummyJsonException $exception) {
            $this->logger->error('Failed to fetch user', [
                'user_id' => $id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'status_code' => $exception->statusCode(),
            ]);

            throw $exception;
        }
    }

    public function get(): UserQuery
    {
        return new UserQuery($this->client, $this->logger);
    }

    public function create(string $firstName, string $lastName, string $email): UserDto
    {
        try {
            $data = $this->client->post('/users/add', [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $email,
            ]);

            return UserDto::fromArray($data);
        } catch (DummyJsonException $exception) {
            $this->logger->error('Failed to create user', [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'status_code' => $exception->statusCode(),
            ]);

            throw $exception;
        }
    }
}

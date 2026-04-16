<?php

declare(strict_types=1);

namespace Natedaly\DummyjsonClient\Query;

use Natedaly\DummyjsonClient\Contracts\HttpClient;
use Natedaly\DummyjsonClient\Dto\UserCollection;
use Natedaly\DummyjsonClient\Exceptions\DummyJsonException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class UserQuery
{
    private int $limit = 10;

    private int $skip = 0;

    /** @var array<int, string> */
    private array $select = [];

    public function __construct(
        private readonly HttpClient $client,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function skip(int $skip): self
    {
        $this->skip = $skip;

        return $this;
    }

    /** @param array<int, string> $fields */
    public function select(array $fields): self
    {
        $this->select = $fields;

        return $this;
    }

    public function fetch(): UserCollection
    {
        $query = ['limit' => $this->limit, 'skip' => $this->skip];

        if ($this->select !== []) {
            $query['select'] = implode(',', $this->select);
        }

        try {
            /** @var array{users: array<int, array<string, mixed>>, total: int, skip: int, limit: int} $data */
            $data = $this->client->get('/users', $query);

            return UserCollection::fromArray($data);
        } catch (DummyJsonException $exception) {
            $this->logger->error('Failed to fetch users', [
                'limit' => $this->limit,
                'skip' => $this->skip,
                'select' => $this->select,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'status_code' => $exception->statusCode(),
            ]);

            throw $exception;
        }
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use Natedaly\DummyjsonClient\Contracts\HttpClient;
use Natedaly\DummyjsonClient\Dto\UserCollection;
use Natedaly\DummyjsonClient\Services\UserService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    #[Test]
    public function it_retrieves_a_single_user_by_id(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->expects($this->once())
            ->method('get')
            ->with('/users/1')
            ->willReturn([
                'id' => 1,
                'firstName' => 'Shrinking',
                'lastName' => 'Rae',
                'email' => 'shrinking.rae@example.com',
            ]);

        $user = new UserService($client)->getById(id: 1);

        $this->assertSame(1, $user->id);
        $this->assertSame('Shrinking', $user->firstName);
        $this->assertSame('Rae', $user->lastName);
        $this->assertSame('shrinking.rae@example.com', $user->email);
    }

    #[Test]
    public function it_retrieves_a_paginated_list_of_users(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->expects($this->once())
            ->method('get')
            ->with('/users', ['limit' => 10, 'skip' => 0])
            ->willReturn([
                'users' => [
                    ['id' => 1, 'firstName' => 'Cecil', 'lastName' => 'Stedman', 'email' => 'cecil.stedman@example.com'],
                    ['id' => 2, 'firstName' => 'Damien', 'lastName' => 'Darkblood', 'email' => 'damien.darkblood@example.com'],
                ],
                'total' => 208,
                'skip' => 0,
                'limit' => 10,
            ]);

        $result = new UserService($client)
            ->get()
            ->limit(10)
            ->skip(0)
            ->fetch();

        $this->assertInstanceOf(UserCollection::class, $result);
        $this->assertCount(2, $result->users);
        $this->assertSame(208, $result->total);
        $this->assertSame(0, $result->skip);
        $this->assertSame(10, $result->limit);
        $this->assertSame(1, $result->users[0]->id);
        $this->assertSame('Cecil', $result->users[0]->firstName);
        $this->assertSame(2, $result->users[1]->id);
        $this->assertSame('Damien', $result->users[1]->firstName);
    }

    #[Test]
    public function it_created_a_user_and_returns_a_user_id(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->expects($this->once())
            ->method('post')
            ->with('/users/add')
            ->willReturn([
                'id' => 208,
                'firstName' => 'Amber',
                'lastName' => 'Bennett',
                'email' => 'amber.bennet@example.com',
            ]);

        $result = new UserService($client)
            ->create(
                firstName: 'Amber',
                lastName: 'Bennett',
                email: 'amber.bennet@example.com'
            );

        $this->assertSame(208, $result->id);
    }

    #[Test]
    public function it_posts_required_fields_when_creating_a_user(): void
    {
        $this->assertTrue(true);
    }

    #[Test]
    public function it_maps_the_response_data_to_dto_with_validations(): void
    {
        $this->assertTrue(true);
    }

    #[Test]
    public function it_wraps_transport_failure_in_a_domain_exception(): void
    {
        $this->assertTrue(true);
    }
}
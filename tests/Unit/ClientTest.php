<?php

declare(strict_types=1);

namespace Tests\Unit;

use Natedaly\DummyjsonClient\Client;
use Natedaly\DummyjsonClient\Contracts\DummyJsonClientInterface;
use Natedaly\DummyjsonClient\Contracts\UserServiceInterface;
use Natedaly\DummyjsonClient\Dto\UserDto;
use Natedaly\DummyjsonClient\Exceptions\ApiNotFoundException;
use Natedaly\DummyjsonClient\Testing\FakeDummyJsonClient;
use Natedaly\DummyjsonClient\Testing\FakeUserService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ClientTest extends TestCase
{
    #[Test]
    public function it_implements_the_client_interface(): void
    {
        $this->assertInstanceOf(
            DummyJsonClientInterface::class,
            Client::make('https://dummyjson.com'),
        );
    }

    #[Test]
    public function it_returns_the_same_user_service_instance_on_repeated_calls(): void
    {
        $client = Client::make('https://dummyjson.com');

        $this->assertSame($client->users(), $client->users());
    }

    #[Test]
    public function it_exposes_a_user_service(): void
    {
        $client = Client::make('https://dummyjson.com');

        $this->assertInstanceOf(UserServiceInterface::class, $client->users());
    }

    #[Test]
    public function fake_client_returns_the_injected_user_service(): void
    {
        $users = new FakeUserService();
        $client = new FakeDummyJsonClient($users);

        $this->assertSame($users, $client->users());
    }

    #[Test]
    public function fake_user_service_returns_configured_users(): void
    {
        $users = new FakeUserService();
        $users->addUser(new UserDto(id: 1, firstName: 'Emily', lastName: 'Johnson', email: 'emily@example.com'));

        $client = new FakeDummyJsonClient($users);

        $user = $client->users()->getById(1);

        $this->assertSame(1, $user->id);
        $this->assertSame('Emily', $user->firstName);
    }

    #[Test]
    public function fake_user_service_throws_not_found_for_missing_users(): void
    {
        $client = new FakeDummyJsonClient(new FakeUserService());

        $this->expectException(ApiNotFoundException::class);

        $client->users()->getById(999);
    }

    #[Test]
    public function fake_user_service_creates_users_with_auto_incremented_ids(): void
    {
        $users = new FakeUserService();
        $client = new FakeDummyJsonClient($users);

        $user = $client->users()->create('Jane', 'Smith', 'jane@example.com');

        $this->assertSame(1, $user->id);
        $this->assertSame('Jane', $user->firstName);

        $second = $client->users()->create('John', 'Doe', 'john@example.com');

        $this->assertSame(2, $second->id);
    }

    #[Test]
    public function fake_user_service_lists_all_configured_users(): void
    {
        $users = new FakeUserService();
        $users->addUser(new UserDto(id: 1, firstName: 'Emily', lastName: 'Johnson', email: 'emily@example.com'));
        $users->addUser(new UserDto(id: 2, firstName: 'Michael', lastName: 'Williams', email: 'michael@example.com'));

        $collection = (new FakeDummyJsonClient($users))
            ->users()
            ->get()
            ->fetch();

        $this->assertCount(2, $collection->users);
        $this->assertSame(2, $collection->total);
    }
}

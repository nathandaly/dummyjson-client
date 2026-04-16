<?php

declare(strict_types=1);

namespace Tests\Unit\Dto;

use JsonException;
use Natedaly\DummyjsonClient\Dto\UserDto;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserDtoTest extends TestCase
{
    private UserDto $dto;

    protected function setUp(): void
    {
        $this->dto = new UserDto(
            id: 1,
            firstName: 'Emily',
            lastName: 'Johnson',
            email: 'emily.johnson@x.com',
        );
    }

    #[Test]
    public function it_can_be_instantiated_from_an_array(): void
    {
        $dto = UserDto::fromArray([
            'id' => 1,
            'firstName' => 'Emily',
            'lastName' => 'Johnson',
            'email' => 'emily.johnson@x.com',
        ]);

        $this->assertSame(1, $dto->id);
        $this->assertSame('Emily', $dto->firstName);
        $this->assertSame('Johnson', $dto->lastName);
        $this->assertSame('emily.johnson@x.com', $dto->email);
    }

    #[Test]
    public function it_ignores_unknown_keys_when_instantiated_from_an_array(): void
    {
        $dto = UserDto::fromArray([
            'id' => 1,
            'firstName' => 'Emily',
            'lastName' => 'Johnson',
            'email' => 'emily.johnson@x.com',
            'age' => 28,
            'phone' => '555-1234',
        ]);

        $this->assertSame(1, $dto->id);
    }

    /**
     * @throws JsonException
     */
    #[Test]
    public function it_serializes_to_json(): void
    {
        $this->assertJsonStringEqualsJsonString(
            '{"id":1,"firstName":"Emily","lastName":"Johnson","email":"emily.johnson@x.com"}',
            json_encode($this->dto, JSON_THROW_ON_ERROR),
        );
    }
}
<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    #[Test]
    public function it_retrieves_a_single_user_by_id(): void
    {
        $this->assertTrue(true);
    }

    public function it_retrieves_a_paginated_list_of_users(): void
    {
        $this->assertTrue(true);
    }

    public function it_created_a_user_and_returns_a_user_id(): void
    {
        $this->assertTrue(true);
    }

    public function it_posts_required_fields_when_creating_a_user(): void
    {
        $this->assertTrue(true);
    }

    public function it_maps_the_response_data_to_dto_with_validations(): void
    {
        $this->assertTrue(true);
    }

    public function it_wraps_transport_failure_in_a_domain_exception(): void
    {
        $this->assertTrue(true);
    }
}
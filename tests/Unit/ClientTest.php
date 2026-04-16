<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ClientTest extends TestCase
{
    #[Test]
    public function it_passes(): void
    {
        $this->assertTrue(true);
    }
}
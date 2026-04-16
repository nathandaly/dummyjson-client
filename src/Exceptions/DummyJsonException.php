<?php

declare(strict_types=1);

namespace Natedaly\DummyjsonClient\Exceptions;

use RuntimeException;
use Throwable;

class DummyJsonException extends RuntimeException
{
    public function __construct(
        string $message,
        protected readonly ?int $statusCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function statusCode(): ?int
    {
        return $this->statusCode;
    }
}

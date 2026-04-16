<?php

declare(strict_types=1);

namespace Natedaly\DummyjsonClient\Traits;

use ReflectionClass;

trait HasArrayable
{
    public static function fromArray(array $attributes): static
    {
        $params = [];

        // 🔆 I wanted to keep php 8.4 constructor promotion and allow fromArray().
        foreach (new ReflectionClass(static::class)->getConstructor()->getParameters() as $param) {
            if (!isset($attributes[$param->getName()])) {
                continue;
            }

            $paramName = $param->getName();
            $params[$paramName] = $attributes[$paramName];
        }

        return new static(...$params);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
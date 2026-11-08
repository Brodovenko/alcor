<?php

declare(strict_types=1);

namespace Alcor\Shared\Domain;

abstract readonly class StringIdentifier
{
    public string $value;

    final public function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '') {
            throw InvalidIdentifier::empty(static::class);
        }

        $this->value = $value;
    }

    public function equals(self $other): bool
    {
        return $other::class === static::class && $other->value === $this->value;
    }
}

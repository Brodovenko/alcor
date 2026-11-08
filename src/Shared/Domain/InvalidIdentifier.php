<?php

declare(strict_types=1);

namespace Alcor\Shared\Domain;

final class InvalidIdentifier extends \DomainException
{
    public static function empty(string $type): self
    {
        return new self(sprintf('%s cannot be empty.', $type));
    }
}

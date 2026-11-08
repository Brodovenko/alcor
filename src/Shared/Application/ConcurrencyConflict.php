<?php

declare(strict_types=1);

namespace Alcor\Shared\Application;

final class ConcurrencyConflict extends \RuntimeException
{
    public static function forStream(string $streamId, int $expectedVersion, int $actualVersion): self
    {
        return new self(sprintf(
            'Stream "%s" is at version %d, expected %d. Reload and retry.',
            $streamId,
            $actualVersion,
            $expectedVersion,
        ));
    }
}

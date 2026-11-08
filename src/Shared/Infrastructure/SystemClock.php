<?php

declare(strict_types=1);

namespace Alcor\Shared\Infrastructure;

use Alcor\Shared\Application\Clock;

final readonly class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}

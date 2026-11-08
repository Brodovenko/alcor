<?php

declare(strict_types=1);

namespace Alcor\Tests\Support;

use Alcor\Shared\Application\Clock;

final class FrozenClock implements Clock
{
    public function __construct(private \DateTimeImmutable $now) {}

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }

    public function advance(string $interval): void
    {
        $this->now = $this->now->modify($interval);
    }
}

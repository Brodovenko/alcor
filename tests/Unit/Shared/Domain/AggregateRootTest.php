<?php

declare(strict_types=1);

namespace Alcor\Tests\Unit\Shared\Domain;

use Alcor\Tests\Support\Counter;
use Alcor\Tests\Support\Incremented;
use PHPUnit\Framework\TestCase;

final class AggregateRootTest extends TestCase
{
    public function test_recorded_events_bump_the_version_and_are_released_once(): void
    {
        $counter = Counter::start();
        $counter->increment();

        self::assertSame(2, $counter->version());
        self::assertSame(2, $counter->value);
        self::assertCount(2, $counter->releaseEvents());
        self::assertSame([], $counter->releaseEvents());
    }

    public function test_reconstitution_replays_history_without_pending_events(): void
    {
        $counter = Counter::reconstitute([new Incremented(), new Incremented(), new Incremented()]);

        self::assertSame(3, $counter->version());
        self::assertSame(3, $counter->value);
        self::assertSame([], $counter->releaseEvents());
    }
}

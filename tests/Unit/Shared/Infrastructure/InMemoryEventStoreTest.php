<?php

declare(strict_types=1);

namespace Alcor\Tests\Unit\Shared\Infrastructure;

use Alcor\Payroll\Domain\EarningLineId;
use Alcor\Payroll\Domain\Event\SystemValueCalculated;
use Alcor\Payroll\Domain\Money;
use Alcor\Shared\Application\ConcurrencyConflict;
use Alcor\Shared\Infrastructure\InMemoryEventStore;
use PHPUnit\Framework\TestCase;

final class InMemoryEventStoreTest extends TestCase
{
    public function test_unknown_streams_are_empty(): void
    {
        self::assertSame([], new InMemoryEventStore()->load('missing'));
    }

    public function test_appended_events_are_loaded_in_order(): void
    {
        $store = new InMemoryEventStore();
        $first = $this->event('1000.00');
        $second = $this->event('1050.00');

        $store->append('line-1', 0, [$first]);
        $store->append('line-1', 1, [$second]);

        self::assertSame([$first, $second], $store->load('line-1'));
    }

    public function test_appending_with_a_stale_version_is_rejected(): void
    {
        $store = new InMemoryEventStore();
        $store->append('line-1', 0, [$this->event('1000.00')]);

        $this->expectException(ConcurrencyConflict::class);

        $store->append('line-1', 0, [$this->event('1050.00')]);
    }

    private function event(string $amount): SystemValueCalculated
    {
        return new SystemValueCalculated(new EarningLineId('line-1'), Money::fromDecimal($amount), new \DateTimeImmutable());
    }
}

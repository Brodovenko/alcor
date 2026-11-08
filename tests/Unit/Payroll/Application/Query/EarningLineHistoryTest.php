<?php

declare(strict_types=1);

namespace Alcor\Tests\Unit\Payroll\Application\Query;

use Alcor\Payroll\Application\Query\AdjustmentHistoryEntry;
use Alcor\Payroll\Application\Query\EarningLineHistory;
use Alcor\Payroll\Domain\Money;
use PHPUnit\Framework\TestCase;

final class EarningLineHistoryTest extends TestCase
{
    public function test_the_current_value_always_includes_adjustments_regardless_of_event_order(): void
    {
        $history = EarningLineHistory::start('line-1', Money::fromDecimal('1000.00'))
            ->withAdjustment(new AdjustmentHistoryEntry(1, Money::fromDecimal('-45.55'), 'Correction', 'specialist-1', new \DateTimeImmutable()))
            ->withSystemValue(Money::fromDecimal('1050.00'));

        self::assertSame('$1,050.00', $history->systemValue->format());
        self::assertSame('$1,004.45', $history->currentValue->format());
    }
}

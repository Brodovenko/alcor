<?php

declare(strict_types=1);

namespace Alcor\Tests\Unit\Payroll\Infrastructure\Cli;

use Alcor\Payroll\Application\Query\AdjustmentHistoryEntry;
use Alcor\Payroll\Application\Query\EarningLineHistory;
use Alcor\Payroll\Application\Query\IgnoredRecalculationEntry;
use Alcor\Payroll\Domain\Money;
use Alcor\Payroll\Infrastructure\Cli\HistoryTablePrinter;
use PHPUnit\Framework\TestCase;

final class HistoryTablePrinterTest extends TestCase
{
    public function test_an_unadjusted_line_shows_a_live_system_value(): void
    {
        $output = new HistoryTablePrinter()->render(EarningLineHistory::start('line-1', Money::fromDecimal('1050.00')));

        self::assertStringContainsString('System value               $1,050.00', $output);
        self::assertStringNotContainsString('frozen', $output);
    }

    public function test_an_adjusted_line_shows_frozen_value_adjustments_and_ignored_recalculations(): void
    {
        $at = new \DateTimeImmutable('2026-11-07 10:00:00');
        $history = EarningLineHistory::start('line-1', Money::fromDecimal('1050.00'))
            ->withAdjustment(new AdjustmentHistoryEntry(1, Money::fromDecimal('-45.55'), 'Declined benefit', 'specialist-1', $at))
            ->withIgnoredRecalculation(new IgnoredRecalculationEntry(Money::fromDecimal('1080.00'), $at));

        self::assertSame(
            <<<'TEXT'
                Audit history for line "line-1"

                System value (frozen)      $1,050.00
                Adjustment 1                 -$45.55   Declined benefit (by specialist-1)
                Current value              $1,004.45

                Ignored system recalculation to $1,080.00 at 2026-11-07 10:00:00

                TEXT,
            new HistoryTablePrinter()->render($history),
        );
    }
}

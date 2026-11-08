<?php

declare(strict_types=1);

namespace Alcor\Tests\Integration;

use Alcor\Payroll\Application\Command\AddManualAdjustment;
use Alcor\Payroll\Application\Command\CalculateEarningLine;
use Alcor\Payroll\Application\Command\RecalculateEarningLine;
use Alcor\Payroll\Application\Query\AdjustmentHistoryEntry;
use Alcor\Payroll\Application\Query\GetEarningLineHistory;
use Alcor\Payroll\Infrastructure\PayrollModule;
use Alcor\Shared\Application\Command;
use Alcor\Tests\Support\FrozenClock;
use PHPUnit\Framework\TestCase;

/**
 * The assignment's example played through the command bus and read back from the projection.
 */
final class AssignmentScenarioTest extends TestCase
{
    public function test_the_full_scenario_matches_the_expected_values_and_audit_history(): void
    {
        $payroll = PayrollModule::inMemory(new FrozenClock(new \DateTimeImmutable('2026-11-07 10:00:00')));

        foreach (self::steps() as $step => [$command, $expectedValue]) {
            $payroll->commands->dispatch($command);

            self::assertSame(
                $expectedValue,
                ($payroll->history)(new GetEarningLineHistory('line-1'))->currentValue->format(),
                sprintf('Unexpected current value after step %d.', $step),
            );
        }

        $history = ($payroll->history)(new GetEarningLineHistory('line-1'));

        self::assertTrue($history->isSystemValueFrozen());
        self::assertSame('$1,050.00', $history->systemValue->format());
        self::assertSame(
            [
                [1, '-$45.55', 'Employee declined dental benefit; reversing deduction'],
                [2, '+$100.10', 'Late correction: missed approved overtime bonus'],
                [3, '-$0.10', 'Minor rounding adjustment'],
                [4, '-$0.20', 'Second minor rounding adjustment'],
                [5, '+$0.20', 'Correcting mistake in adjustment #4'],
            ],
            array_map(
                static fn(AdjustmentHistoryEntry $entry): array => [$entry->number, $entry->amount->format(signed: true), $entry->comment],
                $history->adjustments,
            ),
        );
        self::assertSame('$1,104.45', $history->currentValue->format());
        self::assertCount(1, $history->ignoredRecalculations);
    }

    /**
     * @return array<int, array{Command, string}>
     */
    private static function steps(): array
    {
        $adjust = static fn(string $amount, string $comment): AddManualAdjustment => new AddManualAdjustment('line-1', $amount, $comment, 'specialist-1');

        return [
            1 => [new CalculateEarningLine('line-1', '1000.00'), '$1,000.00'],
            2 => [new RecalculateEarningLine('line-1', '1050.00'), '$1,050.00'],
            3 => [$adjust('-45.55', 'Employee declined dental benefit; reversing deduction'), '$1,004.45'],
            4 => [new RecalculateEarningLine('line-1', '1080.00'), '$1,004.45'],
            5 => [$adjust('+100.10', 'Late correction: missed approved overtime bonus'), '$1,104.55'],
            6 => [$adjust('-0.10', 'Minor rounding adjustment'), '$1,104.45'],
            7 => [$adjust('-0.20', 'Second minor rounding adjustment'), '$1,104.25'],
            8 => [$adjust('+0.20', 'Correcting mistake in adjustment #4'), '$1,104.45'],
        ];
    }
}

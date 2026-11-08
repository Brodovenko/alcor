<?php

declare(strict_types=1);

namespace Alcor\Tests\Unit\Payroll\Domain;

use Alcor\Payroll\Domain\AdjustmentComment;
use Alcor\Payroll\Domain\EarningLine;
use Alcor\Payroll\Domain\EarningLineId;
use Alcor\Payroll\Domain\Event\ManualAdjustmentAdded;
use Alcor\Payroll\Domain\Event\SystemRecalculationIgnored;
use Alcor\Payroll\Domain\Event\SystemValueCalculated;
use Alcor\Payroll\Domain\Exception\CurrencyMismatch;
use Alcor\Payroll\Domain\Exception\ZeroAdjustment;
use Alcor\Payroll\Domain\Money;
use Alcor\Payroll\Domain\SpecialistId;
use PHPUnit\Framework\TestCase;

final class EarningLineTest extends TestCase
{
    private EarningLineId $id;

    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->id = new EarningLineId('line-1');
        $this->now = new \DateTimeImmutable('2026-11-07 10:00:00');
    }

    public function test_calculation_records_the_system_value(): void
    {
        $line = EarningLine::calculate($this->id, Money::fromDecimal('1000.00'), $this->now);

        self::assertEquals([$this->calculated('1000.00')], $line->releaseEvents());
        self::assertSame('USD', $line->currency());
    }

    public function test_recalculation_replaces_the_value_while_there_are_no_adjustments(): void
    {
        $line = $this->given($this->calculated('1000.00'));

        $line->recalculate(Money::fromDecimal('1050.00'), $this->now);

        self::assertEquals([$this->calculated('1050.00')], $line->releaseEvents());
    }

    public function test_recalculation_to_the_same_amount_records_nothing(): void
    {
        $line = $this->given($this->calculated('1000.00'));

        $line->recalculate(Money::fromDecimal('1000.00'), $this->now);

        self::assertSame([], $line->releaseEvents());
    }

    public function test_recalculation_is_ignored_once_the_line_has_an_adjustment(): void
    {
        $line = $this->given($this->calculated('1050.00'), $this->adjusted(1, '-45.55'));

        $line->recalculate(Money::fromDecimal('2000.00'), $this->now);

        self::assertEquals(
            [new SystemRecalculationIgnored($this->id, Money::fromDecimal('2000.00'), $this->now)],
            $line->releaseEvents(),
        );
    }

    public function test_an_adjustment_records_the_full_audit_data(): void
    {
        $line = $this->given($this->calculated('1050.00'));

        $line->addAdjustment(
            Money::fromDecimal('-45.55'),
            new AdjustmentComment('Correction'),
            new SpecialistId('specialist-1'),
            $this->now,
        );

        self::assertEquals([$this->adjusted(1, '-45.55')], $line->releaseEvents());
    }

    public function test_adjustments_continue_numbering_after_replay(): void
    {
        $line = $this->given(
            $this->calculated('1050.00'),
            $this->adjusted(1, '-45.55'),
            new SystemRecalculationIgnored($this->id, Money::fromDecimal('1080.00'), $this->now),
            $this->adjusted(2, '100.10'),
        );

        $this->adjust($line, '-0.10');

        self::assertSame(5, $line->version());
        self::assertEquals([$this->adjusted(3, '-0.10')], $line->releaseEvents());
    }

    public function test_zero_adjustments_are_rejected(): void
    {
        $line = $this->given($this->calculated('1050.00'));

        $this->expectException(ZeroAdjustment::class);

        $this->adjust($line, '0.00');
    }

    public function test_adjustments_in_another_currency_are_rejected(): void
    {
        $line = $this->given($this->calculated('1050.00'));

        $this->expectException(CurrencyMismatch::class);

        $line->addAdjustment(
            Money::fromDecimal('10.00', 'EUR'),
            new AdjustmentComment('Wrong currency'),
            new SpecialistId('specialist-1'),
            $this->now,
        );
    }

    public function test_recalculation_in_another_currency_is_rejected(): void
    {
        $line = $this->given($this->calculated('1050.00'));

        $this->expectException(CurrencyMismatch::class);

        $line->recalculate(Money::fromDecimal('1050.00', 'EUR'), $this->now);
    }

    private function given(SystemValueCalculated $first, SystemValueCalculated|ManualAdjustmentAdded|SystemRecalculationIgnored ...$rest): EarningLine
    {
        return EarningLine::reconstitute([$first, ...$rest]);
    }

    private function calculated(string $amount): SystemValueCalculated
    {
        return new SystemValueCalculated($this->id, Money::fromDecimal($amount), $this->now);
    }

    private function adjusted(int $number, string $amount): ManualAdjustmentAdded
    {
        return new ManualAdjustmentAdded(
            $this->id,
            $number,
            Money::fromDecimal($amount),
            new AdjustmentComment('Correction'),
            new SpecialistId('specialist-1'),
            $this->now,
        );
    }

    private function adjust(EarningLine $line, string $amount): void
    {
        $line->addAdjustment(
            Money::fromDecimal($amount),
            new AdjustmentComment('Correction'),
            new SpecialistId('specialist-1'),
            $this->now,
        );
    }
}

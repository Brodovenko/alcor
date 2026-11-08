<?php

declare(strict_types=1);

namespace Alcor\Tests\Integration;

use Alcor\Payroll\Application\Command\AddManualAdjustment;
use Alcor\Payroll\Application\Command\CalculateEarningLine;
use Alcor\Payroll\Application\Command\RecalculateEarningLine;
use Alcor\Payroll\Application\Query\GetEarningLineHistory;
use Alcor\Payroll\Domain\Exception\EarningLineAlreadyExists;
use Alcor\Payroll\Domain\Exception\EarningLineNotFound;
use Alcor\Payroll\Domain\Exception\InvalidAdjustmentComment;
use Alcor\Payroll\Infrastructure\PayrollModule;
use Alcor\Tests\Support\FrozenClock;
use PHPUnit\Framework\TestCase;

final class EarningLineCommandsTest extends TestCase
{
    private FrozenClock $clock;

    private PayrollModule $payroll;

    protected function setUp(): void
    {
        $this->clock = new FrozenClock(new \DateTimeImmutable('2026-11-07 10:00:00'));
        $this->payroll = PayrollModule::inMemory($this->clock);
    }

    public function test_a_line_cannot_be_calculated_twice(): void
    {
        $this->payroll->commands->dispatch(new CalculateEarningLine('line-1', '1000.00'));

        $this->expectException(EarningLineAlreadyExists::class);

        $this->payroll->commands->dispatch(new CalculateEarningLine('line-1', '1050.00'));
    }

    public function test_commands_for_an_unknown_line_are_rejected(): void
    {
        $this->expectException(EarningLineNotFound::class);

        $this->payroll->commands->dispatch(new RecalculateEarningLine('missing', '1000.00'));
    }

    public function test_querying_an_unknown_line_is_rejected(): void
    {
        $this->expectException(EarningLineNotFound::class);

        ($this->payroll->history)(new GetEarningLineHistory('missing'));
    }

    public function test_commands_and_queries_treat_padded_ids_as_the_same_line(): void
    {
        $this->payroll->commands->dispatch(new CalculateEarningLine(' line-1 ', '1050.00'));
        $this->payroll->commands->dispatch(new AddManualAdjustment("line-1\n", '-45.55', 'Declined benefit', 'specialist-1'));

        $history = ($this->payroll->history)(new GetEarningLineHistory('  line-1'));

        self::assertSame('line-1', $history->lineId);
        self::assertSame('$1,004.45', $history->currentValue->format());
    }

    public function test_a_rejected_adjustment_leaves_no_trace(): void
    {
        $this->payroll->commands->dispatch(new CalculateEarningLine('line-1', '1050.00'));

        try {
            $this->payroll->commands->dispatch(new AddManualAdjustment('line-1', '-45.55', '  ', 'specialist-1'));
            self::fail('A blank comment must be rejected.');
        } catch (InvalidAdjustmentComment) {
        }

        $history = ($this->payroll->history)(new GetEarningLineHistory('line-1'));
        self::assertSame([], $history->adjustments);
        self::assertSame('$1,050.00', $history->currentValue->format());
    }

    public function test_adjustments_record_who_made_them_and_when(): void
    {
        $this->payroll->commands->dispatch(new CalculateEarningLine('line-1', '1050.00'));
        $this->clock->advance('+2 days');
        $this->payroll->commands->dispatch(new AddManualAdjustment('line-1', '-45.55', 'Declined benefit', 'specialist-7'));

        $adjustment = ($this->payroll->history)(new GetEarningLineHistory('line-1'))->adjustments[0];

        self::assertSame('specialist-7', $adjustment->adjustedBy);
        self::assertEquals(new \DateTimeImmutable('2026-11-09 10:00:00'), $adjustment->adjustedAt);
    }
}

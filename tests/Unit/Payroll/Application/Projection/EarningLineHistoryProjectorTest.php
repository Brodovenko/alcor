<?php

declare(strict_types=1);

namespace Alcor\Tests\Unit\Payroll\Application\Projection;

use Alcor\Payroll\Application\Projection\EarningLineHistoryProjector;
use Alcor\Payroll\Application\Query\AdjustmentHistoryEntry;
use Alcor\Payroll\Application\Query\IgnoredRecalculationEntry;
use Alcor\Payroll\Domain\AdjustmentComment;
use Alcor\Payroll\Domain\EarningLineId;
use Alcor\Payroll\Domain\Event\ManualAdjustmentAdded;
use Alcor\Payroll\Domain\Event\SystemRecalculationIgnored;
use Alcor\Payroll\Domain\Event\SystemValueCalculated;
use Alcor\Payroll\Domain\Money;
use Alcor\Payroll\Domain\SpecialistId;
use Alcor\Payroll\Infrastructure\InMemoryEarningLineHistoryReadModel;
use PHPUnit\Framework\TestCase;

final class EarningLineHistoryProjectorTest extends TestCase
{
    private InMemoryEarningLineHistoryReadModel $readModel;

    private EarningLineHistoryProjector $projector;

    private EarningLineId $lineId;

    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->readModel = new InMemoryEarningLineHistoryReadModel();
        $this->projector = new EarningLineHistoryProjector($this->readModel);
        $this->lineId = new EarningLineId('line-1');
        $this->now = new \DateTimeImmutable('2026-11-07 10:00:00');
    }

    public function test_the_latest_system_calculation_is_the_projected_system_value(): void
    {
        $this->projector->handle(new SystemValueCalculated($this->lineId, Money::fromDecimal('1000.00'), $this->now));
        $this->projector->handle(new SystemValueCalculated($this->lineId, Money::fromDecimal('1050.00'), $this->now));

        $history = $this->readModel->find('line-1');

        self::assertNotNull($history);
        self::assertTrue($history->systemValue->equals(Money::fromDecimal('1050.00')));
        self::assertTrue($history->currentValue->equals(Money::fromDecimal('1050.00')));
        self::assertFalse($history->isSystemValueFrozen());
    }

    public function test_adjustments_and_ignored_recalculations_are_kept_apart(): void
    {
        $this->projector->handle(new SystemValueCalculated($this->lineId, Money::fromDecimal('1050.00'), $this->now));
        $this->projector->handle(new ManualAdjustmentAdded(
            $this->lineId,
            1,
            Money::fromDecimal('-45.55'),
            new AdjustmentComment('Employee declined dental benefit'),
            new SpecialistId('specialist-1'),
            $this->now,
        ));
        $this->projector->handle(new SystemRecalculationIgnored($this->lineId, Money::fromDecimal('1080.00'), $this->now));

        $history = $this->readModel->find('line-1');

        self::assertNotNull($history);
        self::assertTrue($history->isSystemValueFrozen());
        self::assertTrue($history->currentValue->equals(Money::fromDecimal('1004.45')));
        self::assertEquals(
            [new AdjustmentHistoryEntry(1, Money::fromDecimal('-45.55'), 'Employee declined dental benefit', 'specialist-1', $this->now)],
            $history->adjustments,
        );
        self::assertEquals(
            [new IgnoredRecalculationEntry(Money::fromDecimal('1080.00'), $this->now)],
            $history->ignoredRecalculations,
        );
    }

    public function test_events_for_an_unprojected_line_are_a_programming_error(): void
    {
        $this->expectException(\LogicException::class);

        $this->projector->handle(new SystemRecalculationIgnored($this->lineId, Money::fromDecimal('1.00'), $this->now));
    }
}

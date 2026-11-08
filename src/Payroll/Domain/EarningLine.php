<?php

declare(strict_types=1);

namespace Alcor\Payroll\Domain;

use Alcor\Payroll\Domain\Event\ManualAdjustmentAdded;
use Alcor\Payroll\Domain\Event\SystemRecalculationIgnored;
use Alcor\Payroll\Domain\Event\SystemValueCalculated;
use Alcor\Payroll\Domain\Exception\ZeroAdjustment;
use Alcor\Shared\Domain\AggregateRoot;
use Alcor\Shared\Domain\DomainEvent;

final class EarningLine extends AggregateRoot
{
    private EarningLineId $id;

    private Money $systemValue;

    private int $adjustmentCount = 0;

    public static function calculate(EarningLineId $id, Money $amount, \DateTimeImmutable $calculatedAt): self
    {
        $line = new self();
        $line->recordThat(new SystemValueCalculated($id, $amount, $calculatedAt));

        return $line;
    }

    public function recalculate(Money $amount, \DateTimeImmutable $calculatedAt): void
    {
        $this->systemValue->assertSameCurrency($amount);

        // Manual corrections take permanent precedence. The attempt is recorded rather than rejected,
        // so batch recalculation keeps working and auditors can still see that the source data moved.
        if ($this->adjustmentCount > 0) {
            $this->recordThat(new SystemRecalculationIgnored($this->id, $amount, $calculatedAt));

            return;
        }

        if ($amount->equals($this->systemValue)) {
            return;
        }

        $this->recordThat(new SystemValueCalculated($this->id, $amount, $calculatedAt));
    }

    public function addAdjustment(
        Money $amount,
        AdjustmentComment $comment,
        SpecialistId $adjustedBy,
        \DateTimeImmutable $adjustedAt,
    ): void {
        $this->systemValue->assertSameCurrency($amount);

        if ($amount->isZero()) {
            throw ZeroAdjustment::create();
        }

        $this->recordThat(new ManualAdjustmentAdded(
            $this->id,
            $this->adjustmentCount + 1,
            $amount,
            $comment,
            $adjustedBy,
            $adjustedAt,
        ));
    }

    public function aggregateId(): string
    {
        return $this->id->value;
    }

    public function currency(): string
    {
        return $this->systemValue->currency;
    }

    protected function apply(DomainEvent $event): void
    {
        match (true) {
            $event instanceof SystemValueCalculated => $this->applySystemValueCalculated($event),
            $event instanceof ManualAdjustmentAdded => $this->adjustmentCount++,
            $event instanceof SystemRecalculationIgnored => null,
            default => throw new \LogicException(sprintf('%s cannot apply %s.', self::class, $event::class)),
        };
    }

    private function applySystemValueCalculated(SystemValueCalculated $event): void
    {
        $this->id = $event->lineId;
        $this->systemValue = $event->amount;
    }
}

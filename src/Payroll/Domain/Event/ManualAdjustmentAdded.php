<?php

declare(strict_types=1);

namespace Alcor\Payroll\Domain\Event;

use Alcor\Payroll\Domain\AdjustmentComment;
use Alcor\Payroll\Domain\EarningLineId;
use Alcor\Payroll\Domain\Money;
use Alcor\Payroll\Domain\SpecialistId;
use Alcor\Shared\Domain\DomainEvent;

final readonly class ManualAdjustmentAdded implements DomainEvent
{
    public function __construct(
        public EarningLineId $lineId,
        public int $number,
        public Money $amount,
        public AdjustmentComment $comment,
        public SpecialistId $adjustedBy,
        public \DateTimeImmutable $adjustedAt,
    ) {}

    public function aggregateId(): string
    {
        return $this->lineId->value;
    }
}

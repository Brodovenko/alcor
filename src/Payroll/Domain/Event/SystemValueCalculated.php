<?php

declare(strict_types=1);

namespace Alcor\Payroll\Domain\Event;

use Alcor\Payroll\Domain\EarningLineId;
use Alcor\Payroll\Domain\Money;
use Alcor\Shared\Domain\DomainEvent;

final readonly class SystemValueCalculated implements DomainEvent
{
    public function __construct(
        public EarningLineId $lineId,
        public Money $amount,
        public \DateTimeImmutable $calculatedAt,
    ) {}

    public function aggregateId(): string
    {
        return $this->lineId->value;
    }
}

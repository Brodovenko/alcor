<?php

declare(strict_types=1);

namespace Alcor\Payroll\Domain\Event;

use Alcor\Payroll\Domain\EarningLineId;
use Alcor\Payroll\Domain\Money;
use Alcor\Shared\Domain\DomainEvent;

final readonly class SystemRecalculationIgnored implements DomainEvent
{
    public function __construct(
        public EarningLineId $lineId,
        public Money $attemptedAmount,
        public \DateTimeImmutable $attemptedAt,
    ) {}

    public function aggregateId(): string
    {
        return $this->lineId->value;
    }
}

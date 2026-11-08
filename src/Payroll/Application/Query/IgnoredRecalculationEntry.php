<?php

declare(strict_types=1);

namespace Alcor\Payroll\Application\Query;

use Alcor\Payroll\Domain\Money;

final readonly class IgnoredRecalculationEntry
{
    public function __construct(
        public Money $attemptedAmount,
        public \DateTimeImmutable $attemptedAt,
    ) {}
}

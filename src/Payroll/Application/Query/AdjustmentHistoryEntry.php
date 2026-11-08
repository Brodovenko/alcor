<?php

declare(strict_types=1);

namespace Alcor\Payroll\Application\Query;

use Alcor\Payroll\Domain\Money;

final readonly class AdjustmentHistoryEntry
{
    public function __construct(
        public int $number,
        public Money $amount,
        public string $comment,
        public string $adjustedBy,
        public \DateTimeImmutable $adjustedAt,
    ) {}
}

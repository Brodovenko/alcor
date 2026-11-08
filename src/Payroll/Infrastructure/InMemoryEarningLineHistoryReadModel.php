<?php

declare(strict_types=1);

namespace Alcor\Payroll\Infrastructure;

use Alcor\Payroll\Application\Projection\EarningLineHistoryReadModel;
use Alcor\Payroll\Application\Query\EarningLineHistory;

final class InMemoryEarningLineHistoryReadModel implements EarningLineHistoryReadModel
{
    /** @var array<string, EarningLineHistory> */
    private array $histories = [];

    public function find(string $lineId): ?EarningLineHistory
    {
        return $this->histories[$lineId] ?? null;
    }

    public function save(EarningLineHistory $history): void
    {
        $this->histories[$history->lineId] = $history;
    }
}

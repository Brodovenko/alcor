<?php

declare(strict_types=1);

namespace Alcor\Payroll\Application\Projection;

use Alcor\Payroll\Application\Query\EarningLineHistory;

interface EarningLineHistoryReadModel
{
    public function find(string $lineId): ?EarningLineHistory;

    public function save(EarningLineHistory $history): void;
}

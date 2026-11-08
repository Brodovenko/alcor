<?php

declare(strict_types=1);

namespace Alcor\Payroll\Application\Command;

use Alcor\Shared\Application\Command;

final readonly class RecalculateEarningLine implements Command
{
    public function __construct(
        public string $lineId,
        public string $amount,
    ) {}
}

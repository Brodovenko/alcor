<?php

declare(strict_types=1);

namespace Alcor\Payroll\Application\Command;

use Alcor\Payroll\Domain\EarningLineId;
use Alcor\Payroll\Domain\EarningLineRepository;
use Alcor\Payroll\Domain\Money;
use Alcor\Shared\Application\Clock;

final readonly class RecalculateEarningLineHandler
{
    public function __construct(
        private EarningLineRepository $lines,
        private Clock $clock,
    ) {}

    public function __invoke(RecalculateEarningLine $command): void
    {
        $line = $this->lines->get(new EarningLineId($command->lineId));

        $line->recalculate(Money::fromDecimal($command->amount, $line->currency()), $this->clock->now());

        $this->lines->save($line);
    }
}

<?php

declare(strict_types=1);

namespace Alcor\Payroll\Application\Command;

use Alcor\Payroll\Domain\EarningLine;
use Alcor\Payroll\Domain\EarningLineId;
use Alcor\Payroll\Domain\EarningLineRepository;
use Alcor\Payroll\Domain\Exception\EarningLineAlreadyExists;
use Alcor\Payroll\Domain\Money;
use Alcor\Shared\Application\Clock;

final readonly class CalculateEarningLineHandler
{
    public function __construct(
        private EarningLineRepository $lines,
        private Clock $clock,
    ) {}

    public function __invoke(CalculateEarningLine $command): void
    {
        $id = new EarningLineId($command->lineId);

        if ($this->lines->exists($id)) {
            throw EarningLineAlreadyExists::withId($id);
        }

        $this->lines->save(EarningLine::calculate(
            $id,
            Money::fromDecimal($command->amount, $command->currency),
            $this->clock->now(),
        ));
    }
}

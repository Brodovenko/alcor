<?php

declare(strict_types=1);

namespace Alcor\Payroll\Application\Command;

use Alcor\Payroll\Domain\AdjustmentComment;
use Alcor\Payroll\Domain\EarningLineId;
use Alcor\Payroll\Domain\EarningLineRepository;
use Alcor\Payroll\Domain\Money;
use Alcor\Payroll\Domain\SpecialistId;
use Alcor\Shared\Application\Clock;

final readonly class AddManualAdjustmentHandler
{
    public function __construct(
        private EarningLineRepository $lines,
        private Clock $clock,
    ) {}

    public function __invoke(AddManualAdjustment $command): void
    {
        $line = $this->lines->get(new EarningLineId($command->lineId));

        $line->addAdjustment(
            Money::fromDecimal($command->amount, $line->currency()),
            new AdjustmentComment($command->comment),
            new SpecialistId($command->specialistId),
            $this->clock->now(),
        );

        $this->lines->save($line);
    }
}

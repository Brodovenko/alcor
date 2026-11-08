<?php

declare(strict_types=1);

namespace Alcor\Payroll\Application\Query;

use Alcor\Payroll\Application\Projection\EarningLineHistoryReadModel;
use Alcor\Payroll\Domain\EarningLineId;
use Alcor\Payroll\Domain\Exception\EarningLineNotFound;

final readonly class GetEarningLineHistoryHandler
{
    public function __construct(private EarningLineHistoryReadModel $readModel) {}

    public function __invoke(GetEarningLineHistory $query): EarningLineHistory
    {
        $id = new EarningLineId($query->lineId);

        return $this->readModel->find($id->value) ?? throw EarningLineNotFound::withId($id);
    }
}

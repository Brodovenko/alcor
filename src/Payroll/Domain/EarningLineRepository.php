<?php

declare(strict_types=1);

namespace Alcor\Payroll\Domain;

use Alcor\Payroll\Domain\Exception\EarningLineNotFound;

interface EarningLineRepository
{
    /**
     * @throws EarningLineNotFound
     */
    public function get(EarningLineId $id): EarningLine;

    public function exists(EarningLineId $id): bool;

    public function save(EarningLine $line): void;
}

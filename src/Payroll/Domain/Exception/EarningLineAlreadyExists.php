<?php

declare(strict_types=1);

namespace Alcor\Payroll\Domain\Exception;

use Alcor\Payroll\Domain\EarningLineId;

final class EarningLineAlreadyExists extends \DomainException
{
    public static function withId(EarningLineId $id): self
    {
        return new self(sprintf('Earning line "%s" has already been calculated; use recalculation instead.', $id->value));
    }
}

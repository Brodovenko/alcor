<?php

declare(strict_types=1);

namespace Alcor\Payroll\Domain\Exception;

use Alcor\Payroll\Domain\EarningLineId;

final class EarningLineNotFound extends \DomainException
{
    public static function withId(EarningLineId $id): self
    {
        return new self(sprintf('Earning line "%s" does not exist.', $id->value));
    }
}

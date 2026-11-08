<?php

declare(strict_types=1);

namespace Alcor\Payroll\Domain\Exception;

final class InvalidAdjustmentComment extends \DomainException
{
    public static function empty(): self
    {
        return new self('A manual adjustment requires a comment explaining why it was made.');
    }
}

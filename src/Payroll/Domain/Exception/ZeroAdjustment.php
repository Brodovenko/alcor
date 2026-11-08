<?php

declare(strict_types=1);

namespace Alcor\Payroll\Domain\Exception;

final class ZeroAdjustment extends \DomainException
{
    public static function create(): self
    {
        return new self('A manual adjustment must change the line value; zero amounts are not allowed.');
    }
}

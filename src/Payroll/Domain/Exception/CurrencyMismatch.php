<?php

declare(strict_types=1);

namespace Alcor\Payroll\Domain\Exception;

use Alcor\Payroll\Domain\Money;

final class CurrencyMismatch extends \DomainException
{
    public static function between(Money $expected, Money $actual): self
    {
        return new self(sprintf('Expected an amount in %s, got %s.', $expected->currency, $actual->currency));
    }
}

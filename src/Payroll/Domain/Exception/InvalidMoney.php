<?php

declare(strict_types=1);

namespace Alcor\Payroll\Domain\Exception;

final class InvalidMoney extends \DomainException
{
    public static function amount(string $amount): self
    {
        return new self(sprintf('"%s" is not a valid amount; expected a number with at most two decimals.', $amount));
    }

    public static function outOfRange(): self
    {
        return new self('Amount is out of the supported range.');
    }

    public static function currency(string $currency): self
    {
        return new self(sprintf('"%s" is not a valid ISO 4217 currency code.', $currency));
    }
}

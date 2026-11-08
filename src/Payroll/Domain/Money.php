<?php

declare(strict_types=1);

namespace Alcor\Payroll\Domain;

use Alcor\Payroll\Domain\Exception\CurrencyMismatch;
use Alcor\Payroll\Domain\Exception\InvalidMoney;

/**
 * Stored as integer minor units: floats cannot represent amounts like 0.10 exactly.
 */
final readonly class Money
{
    // Keeps every sum of two valid amounts inside the int range, so arithmetic can never overflow into float.
    public const int MAX_CENTS = 999_999_999_999_999;

    private function __construct(
        public int $cents,
        public string $currency,
    ) {
        if ($cents > self::MAX_CENTS || $cents < -self::MAX_CENTS) {
            throw InvalidMoney::outOfRange();
        }

        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            throw InvalidMoney::currency($currency);
        }
    }

    public static function fromCents(int $cents, string $currency = 'USD'): self
    {
        return new self($cents, $currency);
    }

    public static function fromDecimal(string $amount, string $currency = 'USD'): self
    {
        $normalized = str_replace('−', '-', trim($amount));

        if (preg_match('/^([+-]?)(\d+)(?:\.(\d{1,2}))?$/', $normalized, $matches) !== 1) {
            throw InvalidMoney::amount($amount);
        }

        $whole = ltrim($matches[2], '0');

        if (strlen($whole) > 13) {
            throw InvalidMoney::outOfRange();
        }

        $cents = (int) $whole * 100 + (int) str_pad($matches[3] ?? '', 2, '0');

        return new self($matches[1] === '-' ? -$cents : $cents, $currency);
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->cents + $other->cents, $this->currency);
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function equals(self $other): bool
    {
        return $this->hasSameCurrencyAs($other) && $this->cents === $other->cents;
    }

    public function assertSameCurrency(self $other): void
    {
        if (!$this->hasSameCurrencyAs($other)) {
            throw CurrencyMismatch::between($this, $other);
        }
    }

    public function format(bool $signed = false): string
    {
        $sign = match (true) {
            $this->cents < 0 => '-',
            $signed && $this->cents > 0 => '+',
            default => '',
        };
        $absolute = abs($this->cents);
        $number = number_format(intdiv($absolute, 100)) . '.' . str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);

        return $sign . ($this->currency === 'USD' ? '$' : $this->currency . ' ') . $number;
    }

    private function hasSameCurrencyAs(self $other): bool
    {
        return $this->currency === $other->currency;
    }
}

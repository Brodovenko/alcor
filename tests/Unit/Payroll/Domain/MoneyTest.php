<?php

declare(strict_types=1);

namespace Alcor\Tests\Unit\Payroll\Domain;

use Alcor\Payroll\Domain\Exception\CurrencyMismatch;
use Alcor\Payroll\Domain\Exception\InvalidMoney;
use Alcor\Payroll\Domain\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    /**
     * @return iterable<string, array{string, int}>
     */
    public static function decimalAmounts(): iterable
    {
        yield 'whole' => ['1000', 100000];
        yield 'two decimals' => ['1050.00', 105000];
        yield 'one decimal' => ['0.5', 50];
        yield 'negative' => ['-45.55', -4555];
        yield 'unicode minus' => ['−0.10', -10];
        yield 'explicit plus' => ['+100.10', 10010];
    }

    #[DataProvider('decimalAmounts')]
    public function test_it_parses_decimal_amounts_into_cents(string $amount, int $expectedCents): void
    {
        self::assertSame($expectedCents, Money::fromDecimal($amount)->cents);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidAmounts(): iterable
    {
        yield 'empty' => [''];
        yield 'letters' => ['ten'];
        yield 'three decimals' => ['1.005'];
        yield 'double sign' => ['--1'];
        yield 'separators' => ['1,1,1'];
    }

    #[DataProvider('invalidAmounts')]
    public function test_it_rejects_malformed_amounts(string $amount): void
    {
        $this->expectException(InvalidMoney::class);

        Money::fromDecimal($amount);
    }

    public function test_the_largest_supported_amount_round_trips(): void
    {
        self::assertSame(Money::MAX_CENTS, Money::fromDecimal('9999999999999.99')->cents);
        self::assertSame(-Money::MAX_CENTS, Money::fromDecimal('-0009999999999999.99')->cents);
    }

    /**
     * @return iterable<string, array{callable(): Money}>
     */
    public static function outOfRangeAmounts(): iterable
    {
        yield 'too many digits' => [static fn(): Money => Money::fromDecimal('10000000000000.00')];
        yield 'beyond PHP_INT_MAX' => [static fn(): Money => Money::fromDecimal('99999999999999999999.99')];
        yield 'negative beyond range' => [static fn(): Money => Money::fromDecimal('-92233720368547758.08')];
        yield 'raw cents beyond range' => [static fn(): Money => Money::fromCents(PHP_INT_MIN)];
        yield 'sum beyond range' => [static fn(): Money => Money::fromCents(Money::MAX_CENTS)->add(Money::fromCents(1))];
    }

    /**
     * @param callable(): Money $create
     */
    #[DataProvider('outOfRangeAmounts')]
    public function test_amounts_outside_the_supported_range_are_rejected(callable $create): void
    {
        $this->expectException(InvalidMoney::class);

        $create();
    }

    public function test_it_rejects_invalid_currency_codes(): void
    {
        $this->expectException(InvalidMoney::class);

        Money::fromCents(100, 'usd');
    }

    public function test_addition_is_exact(): void
    {
        $result = Money::fromDecimal('1104.45')
            ->add(Money::fromDecimal('-0.10'))
            ->add(Money::fromDecimal('-0.20'))
            ->add(Money::fromDecimal('0.20'));

        self::assertTrue($result->equals(Money::fromDecimal('1104.35')));
    }

    public function test_it_refuses_to_add_different_currencies(): void
    {
        $this->expectException(CurrencyMismatch::class);

        Money::fromCents(100, 'USD')->add(Money::fromCents(100, 'EUR'));
    }

    public function test_equality_takes_currency_into_account(): void
    {
        self::assertFalse(Money::fromCents(100, 'USD')->equals(Money::fromCents(100, 'EUR')));
        self::assertTrue(Money::fromCents(100, 'USD')->equals(Money::fromCents(100, 'USD')));
    }

    public function test_zero_detection(): void
    {
        self::assertTrue(Money::fromDecimal('0.00')->isZero());
        self::assertFalse(Money::fromDecimal('0.01')->isZero());
    }

    /**
     * @return iterable<string, array{Money, bool, string}>
     */
    public static function formats(): iterable
    {
        yield 'thousands' => [Money::fromCents(110445), false, '$1,104.45'];
        yield 'negative' => [Money::fromCents(-4555), false, '-$45.55'];
        yield 'signed positive' => [Money::fromCents(10010), true, '+$100.10'];
        yield 'signed negative' => [Money::fromCents(-10), true, '-$0.10'];
        yield 'signed zero' => [Money::fromCents(0), true, '$0.00'];
        yield 'other currency' => [Money::fromCents(-500, 'EUR'), false, '-EUR 5.00'];
    }

    #[DataProvider('formats')]
    public function test_formatting(Money $money, bool $signed, string $expected): void
    {
        self::assertSame($expected, $money->format($signed));
    }
}

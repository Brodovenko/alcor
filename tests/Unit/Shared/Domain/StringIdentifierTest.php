<?php

declare(strict_types=1);

namespace Alcor\Tests\Unit\Shared\Domain;

use Alcor\Payroll\Domain\EarningLineId;
use Alcor\Payroll\Domain\SpecialistId;
use Alcor\Shared\Domain\InvalidIdentifier;
use PHPUnit\Framework\TestCase;

final class StringIdentifierTest extends TestCase
{
    public function test_it_rejects_blank_values(): void
    {
        $this->expectException(InvalidIdentifier::class);

        new EarningLineId('  ');
    }

    public function test_surrounding_whitespace_does_not_create_a_different_identifier(): void
    {
        self::assertSame('line-1', new EarningLineId(" line-1\n")->value);
        self::assertTrue(new EarningLineId(' line-1 ')->equals(new EarningLineId('line-1')));
    }

    public function test_equality_requires_same_type_and_value(): void
    {
        $id = new EarningLineId('line-1');

        self::assertTrue($id->equals(new EarningLineId('line-1')));
        self::assertFalse($id->equals(new EarningLineId('line-2')));
        self::assertFalse($id->equals(new SpecialistId('line-1')));
    }
}

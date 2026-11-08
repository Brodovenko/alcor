<?php

declare(strict_types=1);

namespace Alcor\Tests\Unit\Payroll\Domain;

use Alcor\Payroll\Domain\AdjustmentComment;
use Alcor\Payroll\Domain\Exception\InvalidAdjustmentComment;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class AdjustmentCommentTest extends TestCase
{
    public function test_it_trims_surrounding_whitespace(): void
    {
        self::assertSame('Minor rounding adjustment', new AdjustmentComment("  Minor rounding adjustment\n")->value);
    }

    #[TestWith([''])]
    #[TestWith(['   '])]
    #[TestWith(["\t\n"])]
    public function test_it_rejects_blank_comments(string $comment): void
    {
        $this->expectException(InvalidAdjustmentComment::class);

        new AdjustmentComment($comment);
    }
}

<?php

declare(strict_types=1);

namespace Alcor\Payroll\Domain;

use Alcor\Payroll\Domain\Exception\InvalidAdjustmentComment;

final readonly class AdjustmentComment
{
    public string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '') {
            throw InvalidAdjustmentComment::empty();
        }

        $this->value = $value;
    }
}

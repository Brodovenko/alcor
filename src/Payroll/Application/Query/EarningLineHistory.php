<?php

declare(strict_types=1);

namespace Alcor\Payroll\Application\Query;

use Alcor\Payroll\Domain\Money;

final readonly class EarningLineHistory
{
    /**
     * @param list<AdjustmentHistoryEntry>    $adjustments
     * @param list<IgnoredRecalculationEntry> $ignoredRecalculations
     */
    public function __construct(
        public string $lineId,
        public Money $systemValue,
        public Money $currentValue,
        public array $adjustments = [],
        public array $ignoredRecalculations = [],
    ) {}

    public static function start(string $lineId, Money $systemValue): self
    {
        return new self($lineId, $systemValue, $systemValue);
    }

    public function isSystemValueFrozen(): bool
    {
        return $this->adjustments !== [];
    }

    public function withSystemValue(Money $systemValue): self
    {
        $currentValue = array_reduce(
            $this->adjustments,
            static fn(Money $total, AdjustmentHistoryEntry $adjustment): Money => $total->add($adjustment->amount),
            $systemValue,
        );

        return new self($this->lineId, $systemValue, $currentValue, $this->adjustments, $this->ignoredRecalculations);
    }

    public function withAdjustment(AdjustmentHistoryEntry $adjustment): self
    {
        return new self(
            $this->lineId,
            $this->systemValue,
            $this->currentValue->add($adjustment->amount),
            [...$this->adjustments, $adjustment],
            $this->ignoredRecalculations,
        );
    }

    public function withIgnoredRecalculation(IgnoredRecalculationEntry $recalculation): self
    {
        return new self(
            $this->lineId,
            $this->systemValue,
            $this->currentValue,
            $this->adjustments,
            [...$this->ignoredRecalculations, $recalculation],
        );
    }
}

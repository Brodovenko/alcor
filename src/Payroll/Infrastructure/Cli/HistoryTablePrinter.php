<?php

declare(strict_types=1);

namespace Alcor\Payroll\Infrastructure\Cli;

use Alcor\Payroll\Application\Query\EarningLineHistory;

final readonly class HistoryTablePrinter
{
    public function render(EarningLineHistory $history): string
    {
        $systemLabel = $history->isSystemValueFrozen() ? 'System value (frozen)' : 'System value';
        $rows = [[$systemLabel, $history->systemValue->format(), '']];

        foreach ($history->adjustments as $adjustment) {
            $rows[] = [
                sprintf('Adjustment %d', $adjustment->number),
                $adjustment->amount->format(signed: true),
                sprintf('%s (by %s)', $adjustment->comment, $adjustment->adjustedBy),
            ];
        }

        $rows[] = ['Current value', $history->currentValue->format(), ''];

        $lines = [sprintf('Audit history for line "%s"', $history->lineId), ''];
        foreach ($rows as [$entry, $value, $comment]) {
            $lines[] = rtrim(mb_str_pad($entry, 24) . mb_str_pad($value, 12, ' ', STR_PAD_LEFT) . '   ' . $comment);
        }

        foreach ($history->ignoredRecalculations as $recalculation) {
            $lines[] = '';
            $lines[] = sprintf(
                'Ignored system recalculation to %s at %s',
                $recalculation->attemptedAmount->format(),
                $recalculation->attemptedAt->format('Y-m-d H:i:s'),
            );
        }

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }
}

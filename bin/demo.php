<?php

declare(strict_types=1);

use Alcor\Payroll\Application\Command\AddManualAdjustment;
use Alcor\Payroll\Application\Command\CalculateEarningLine;
use Alcor\Payroll\Application\Command\RecalculateEarningLine;
use Alcor\Payroll\Application\Query\GetEarningLineHistory;
use Alcor\Payroll\Infrastructure\Cli\HistoryTablePrinter;
use Alcor\Payroll\Infrastructure\PayrollModule;
use Alcor\Shared\Infrastructure\SystemClock;

require __DIR__ . '/../vendor/autoload.php';

$payroll = PayrollModule::inMemory(new SystemClock());
$line = 'line-1';
$specialist = 'specialist-1';

$steps = [
    'System calculates the line' => new CalculateEarningLine($line, '1000.00'),
    'Source data changes, system recalculates' => new RecalculateEarningLine($line, '1050.00'),
    'Specialist adds a manual correction' => new AddManualAdjustment($line, '-45.55', 'Employee declined dental benefit; reversing deduction', $specialist),
    'Source data changes again, system attempts to recalculate' => new RecalculateEarningLine($line, '1080.00'),
    'Specialist adds a second correction' => new AddManualAdjustment($line, '+100.10', 'Late correction: missed approved overtime bonus', $specialist),
    'Specialist adds a third correction' => new AddManualAdjustment($line, '-0.10', 'Minor rounding adjustment', $specialist),
    'Specialist adds a fourth correction' => new AddManualAdjustment($line, '-0.20', 'Second minor rounding adjustment', $specialist),
    'Specialist adds a compensating correction' => new AddManualAdjustment($line, '+0.20', 'Correcting mistake in adjustment #4', $specialist),
];

$number = 0;
foreach ($steps as $description => $command) {
    $payroll->commands->dispatch($command);
    $current = ($payroll->history)(new GetEarningLineHistory($line))->currentValue->format();

    printf("%d. %-60s %12s\n", ++$number, $description, $current);
}

echo PHP_EOL . new HistoryTablePrinter()->render(($payroll->history)(new GetEarningLineHistory($line)));

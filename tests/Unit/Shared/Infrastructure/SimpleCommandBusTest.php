<?php

declare(strict_types=1);

namespace Alcor\Tests\Unit\Shared\Infrastructure;

use Alcor\Payroll\Application\Command\CalculateEarningLine;
use Alcor\Payroll\Application\Command\RecalculateEarningLine;
use Alcor\Shared\Application\NoHandlerForCommand;
use Alcor\Shared\Infrastructure\SimpleCommandBus;
use PHPUnit\Framework\TestCase;

final class SimpleCommandBusTest extends TestCase
{
    public function test_it_routes_a_command_to_its_handler(): void
    {
        $received = null;
        $bus = new SimpleCommandBus();
        $bus->register(CalculateEarningLine::class, static function (CalculateEarningLine $command) use (&$received): void {
            $received = $command;
        });
        $command = new CalculateEarningLine('line-1', '1000.00');

        $bus->dispatch($command);

        self::assertSame($command, $received);
    }

    public function test_it_fails_loudly_for_unregistered_commands(): void
    {
        $this->expectException(NoHandlerForCommand::class);

        new SimpleCommandBus()->dispatch(new RecalculateEarningLine('line-1', '1000.00'));
    }
}

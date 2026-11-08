<?php

declare(strict_types=1);

namespace Alcor\Payroll\Infrastructure;

use Alcor\Payroll\Application\Command\AddManualAdjustment;
use Alcor\Payroll\Application\Command\AddManualAdjustmentHandler;
use Alcor\Payroll\Application\Command\CalculateEarningLine;
use Alcor\Payroll\Application\Command\CalculateEarningLineHandler;
use Alcor\Payroll\Application\Command\RecalculateEarningLine;
use Alcor\Payroll\Application\Command\RecalculateEarningLineHandler;
use Alcor\Payroll\Application\Projection\EarningLineHistoryProjector;
use Alcor\Payroll\Application\Query\GetEarningLineHistoryHandler;
use Alcor\Shared\Application\Clock;
use Alcor\Shared\Application\CommandBus;
use Alcor\Shared\Infrastructure\InMemoryEventStore;
use Alcor\Shared\Infrastructure\SimpleCommandBus;
use Alcor\Shared\Infrastructure\SynchronousEventPublisher;

/**
 * Composition root: the only place that knows which implementation backs each interface.
 */
final readonly class PayrollModule
{
    private function __construct(
        public CommandBus $commands,
        public GetEarningLineHistoryHandler $history,
    ) {}

    public static function inMemory(Clock $clock): self
    {
        $readModel = new InMemoryEarningLineHistoryReadModel();
        $lines = new EventSourcedEarningLineRepository(
            new InMemoryEventStore(),
            new SynchronousEventPublisher(new EarningLineHistoryProjector($readModel)),
        );

        $bus = new SimpleCommandBus();
        $bus->register(CalculateEarningLine::class, new CalculateEarningLineHandler($lines, $clock));
        $bus->register(RecalculateEarningLine::class, new RecalculateEarningLineHandler($lines, $clock));
        $bus->register(AddManualAdjustment::class, new AddManualAdjustmentHandler($lines, $clock));

        return new self($bus, new GetEarningLineHistoryHandler($readModel));
    }
}

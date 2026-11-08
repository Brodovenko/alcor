<?php

declare(strict_types=1);

namespace Alcor\Payroll\Application\Projection;

use Alcor\Payroll\Application\Query\AdjustmentHistoryEntry;
use Alcor\Payroll\Application\Query\EarningLineHistory;
use Alcor\Payroll\Application\Query\IgnoredRecalculationEntry;
use Alcor\Payroll\Domain\Event\ManualAdjustmentAdded;
use Alcor\Payroll\Domain\Event\SystemRecalculationIgnored;
use Alcor\Payroll\Domain\Event\SystemValueCalculated;
use Alcor\Shared\Application\EventSubscriber;
use Alcor\Shared\Domain\DomainEvent;

final readonly class EarningLineHistoryProjector implements EventSubscriber
{
    public function __construct(private EarningLineHistoryReadModel $readModel) {}

    public function handle(DomainEvent $event): void
    {
        match (true) {
            $event instanceof SystemValueCalculated => $this->onSystemValueCalculated($event),
            $event instanceof ManualAdjustmentAdded => $this->onManualAdjustmentAdded($event),
            $event instanceof SystemRecalculationIgnored => $this->onSystemRecalculationIgnored($event),
            default => null,
        };
    }

    private function onSystemValueCalculated(SystemValueCalculated $event): void
    {
        $history = $this->readModel->find($event->aggregateId());

        $this->readModel->save(
            $history?->withSystemValue($event->amount) ?? EarningLineHistory::start($event->aggregateId(), $event->amount),
        );
    }

    private function onManualAdjustmentAdded(ManualAdjustmentAdded $event): void
    {
        $this->readModel->save($this->existing($event)->withAdjustment(new AdjustmentHistoryEntry(
            $event->number,
            $event->amount,
            $event->comment->value,
            $event->adjustedBy->value,
            $event->adjustedAt,
        )));
    }

    private function onSystemRecalculationIgnored(SystemRecalculationIgnored $event): void
    {
        $this->readModel->save($this->existing($event)->withIgnoredRecalculation(
            new IgnoredRecalculationEntry($event->attemptedAmount, $event->attemptedAt),
        ));
    }

    private function existing(DomainEvent $event): EarningLineHistory
    {
        return $this->readModel->find($event->aggregateId())
            ?? throw new \LogicException(sprintf('No history projected yet for line "%s".', $event->aggregateId()));
    }
}

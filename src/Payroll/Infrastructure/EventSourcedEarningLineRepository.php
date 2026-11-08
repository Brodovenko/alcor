<?php

declare(strict_types=1);

namespace Alcor\Payroll\Infrastructure;

use Alcor\Payroll\Domain\EarningLine;
use Alcor\Payroll\Domain\EarningLineId;
use Alcor\Payroll\Domain\EarningLineRepository;
use Alcor\Payroll\Domain\Exception\EarningLineNotFound;
use Alcor\Shared\Application\EventPublisher;
use Alcor\Shared\Application\EventStore;

final readonly class EventSourcedEarningLineRepository implements EarningLineRepository
{
    public function __construct(
        private EventStore $eventStore,
        private EventPublisher $eventPublisher,
    ) {}

    public function get(EarningLineId $id): EarningLine
    {
        $history = $this->eventStore->load($id->value);

        if ($history === []) {
            throw EarningLineNotFound::withId($id);
        }

        return EarningLine::reconstitute($history);
    }

    public function exists(EarningLineId $id): bool
    {
        return $this->eventStore->load($id->value) !== [];
    }

    public function save(EarningLine $line): void
    {
        $events = $line->releaseEvents();

        if ($events === []) {
            return;
        }

        $this->eventStore->append($line->aggregateId(), $line->version() - count($events), $events);
        $this->eventPublisher->publish($events);
    }
}

<?php

declare(strict_types=1);

namespace Alcor\Shared\Domain;

/**
 * State is changed only by applying events, so replaying the stream always rebuilds the same aggregate.
 */
abstract class AggregateRoot
{
    private int $version = 0;

    /** @var list<DomainEvent> */
    private array $pendingEvents = [];

    final protected function __construct() {}

    /**
     * @param iterable<DomainEvent> $history
     */
    public static function reconstitute(iterable $history): static
    {
        $aggregate = new static();

        foreach ($history as $event) {
            $aggregate->apply($event);
            $aggregate->version++;
        }

        return $aggregate;
    }

    abstract public function aggregateId(): string;

    final public function version(): int
    {
        return $this->version;
    }

    /**
     * @return list<DomainEvent>
     */
    final public function releaseEvents(): array
    {
        $events = $this->pendingEvents;
        $this->pendingEvents = [];

        return $events;
    }

    final protected function recordThat(DomainEvent $event): void
    {
        $this->apply($event);
        $this->version++;
        $this->pendingEvents[] = $event;
    }

    abstract protected function apply(DomainEvent $event): void;
}

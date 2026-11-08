<?php

declare(strict_types=1);

namespace Alcor\Shared\Application;

use Alcor\Shared\Domain\DomainEvent;

interface EventStore
{
    /**
     * @return list<DomainEvent>
     */
    public function load(string $streamId): array;

    /**
     * @param list<DomainEvent> $events
     *
     * @throws ConcurrencyConflict when the stream has moved past $expectedVersion
     */
    public function append(string $streamId, int $expectedVersion, array $events): void;
}

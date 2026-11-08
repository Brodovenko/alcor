<?php

declare(strict_types=1);

namespace Alcor\Shared\Infrastructure;

use Alcor\Shared\Application\ConcurrencyConflict;
use Alcor\Shared\Application\EventStore;
use Alcor\Shared\Domain\DomainEvent;

final class InMemoryEventStore implements EventStore
{
    /** @var array<string, list<DomainEvent>> */
    private array $streams = [];

    public function load(string $streamId): array
    {
        return $this->streams[$streamId] ?? [];
    }

    public function append(string $streamId, int $expectedVersion, array $events): void
    {
        $stream = $this->streams[$streamId] ?? [];

        if (count($stream) !== $expectedVersion) {
            throw ConcurrencyConflict::forStream($streamId, $expectedVersion, count($stream));
        }

        $this->streams[$streamId] = [...$stream, ...$events];
    }
}

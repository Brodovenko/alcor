<?php

declare(strict_types=1);

namespace Alcor\Tests\Support;

use Alcor\Shared\Application\EventPublisher;
use Alcor\Shared\Domain\DomainEvent;

final class RecordingEventPublisher implements EventPublisher
{
    /** @var list<DomainEvent> */
    public private(set) array $published = [];

    public function publish(array $events): void
    {
        $this->published = [...$this->published, ...$events];
    }
}

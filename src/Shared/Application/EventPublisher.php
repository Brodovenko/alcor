<?php

declare(strict_types=1);

namespace Alcor\Shared\Application;

use Alcor\Shared\Domain\DomainEvent;

interface EventPublisher
{
    /**
     * @param list<DomainEvent> $events
     */
    public function publish(array $events): void;
}

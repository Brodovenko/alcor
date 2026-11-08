<?php

declare(strict_types=1);

namespace Alcor\Tests\Support;

use Alcor\Shared\Application\EventSubscriber;
use Alcor\Shared\Domain\DomainEvent;

final class CollectingSubscriber implements EventSubscriber
{
    /** @var list<DomainEvent> */
    public private(set) array $received = [];

    public function handle(DomainEvent $event): void
    {
        $this->received[] = $event;
    }
}

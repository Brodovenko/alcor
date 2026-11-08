<?php

declare(strict_types=1);

namespace Alcor\Shared\Infrastructure;

use Alcor\Shared\Application\EventPublisher;
use Alcor\Shared\Application\EventSubscriber;

final readonly class SynchronousEventPublisher implements EventPublisher
{
    /** @var list<EventSubscriber> */
    private array $subscribers;

    public function __construct(EventSubscriber ...$subscribers)
    {
        $this->subscribers = array_values($subscribers);
    }

    public function publish(array $events): void
    {
        foreach ($events as $event) {
            foreach ($this->subscribers as $subscriber) {
                $subscriber->handle($event);
            }
        }
    }
}

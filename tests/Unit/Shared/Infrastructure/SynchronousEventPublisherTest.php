<?php

declare(strict_types=1);

namespace Alcor\Tests\Unit\Shared\Infrastructure;

use Alcor\Shared\Infrastructure\SynchronousEventPublisher;
use Alcor\Tests\Support\CollectingSubscriber;
use Alcor\Tests\Support\Incremented;
use PHPUnit\Framework\TestCase;

final class SynchronousEventPublisherTest extends TestCase
{
    public function test_every_subscriber_receives_every_event_in_order(): void
    {
        $first = new CollectingSubscriber();
        $second = new CollectingSubscriber();
        $events = [new Incremented(), new Incremented()];

        new SynchronousEventPublisher($first, $second)->publish($events);

        self::assertSame($events, $first->received);
        self::assertSame($events, $second->received);
    }
}

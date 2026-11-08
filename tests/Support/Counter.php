<?php

declare(strict_types=1);

namespace Alcor\Tests\Support;

use Alcor\Shared\Domain\AggregateRoot;
use Alcor\Shared\Domain\DomainEvent;

final class Counter extends AggregateRoot
{
    public private(set) int $value = 0;

    public static function start(): self
    {
        $counter = new self();
        $counter->recordThat(new Incremented());

        return $counter;
    }

    public function increment(): void
    {
        $this->recordThat(new Incremented());
    }

    public function aggregateId(): string
    {
        return 'counter';
    }

    protected function apply(DomainEvent $event): void
    {
        $this->value++;
    }
}

<?php

declare(strict_types=1);

namespace Alcor\Tests\Support;

use Alcor\Shared\Domain\DomainEvent;

final readonly class Incremented implements DomainEvent
{
    public function aggregateId(): string
    {
        return 'counter';
    }
}

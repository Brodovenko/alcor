<?php

declare(strict_types=1);

namespace Alcor\Shared\Application;

use Alcor\Shared\Domain\DomainEvent;

interface EventSubscriber
{
    public function handle(DomainEvent $event): void;
}

<?php

declare(strict_types=1);

namespace Alcor\Shared\Domain;

interface DomainEvent
{
    public function aggregateId(): string;
}

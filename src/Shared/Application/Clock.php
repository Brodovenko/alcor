<?php

declare(strict_types=1);

namespace Alcor\Shared\Application;

interface Clock
{
    public function now(): \DateTimeImmutable;
}

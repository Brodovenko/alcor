<?php

declare(strict_types=1);

namespace Alcor\Shared\Application;

interface CommandBus
{
    public function dispatch(Command $command): void;
}

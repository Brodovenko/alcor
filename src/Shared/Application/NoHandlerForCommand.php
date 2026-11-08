<?php

declare(strict_types=1);

namespace Alcor\Shared\Application;

final class NoHandlerForCommand extends \LogicException
{
    public static function for(Command $command): self
    {
        return new self(sprintf('No handler is registered for %s.', $command::class));
    }
}

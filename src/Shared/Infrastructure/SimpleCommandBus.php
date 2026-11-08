<?php

declare(strict_types=1);

namespace Alcor\Shared\Infrastructure;

use Alcor\Shared\Application\Command;
use Alcor\Shared\Application\CommandBus;
use Alcor\Shared\Application\NoHandlerForCommand;

final class SimpleCommandBus implements CommandBus
{
    /** @var array<class-string<Command>, callable> */
    private array $handlers = [];

    /**
     * @template T of Command
     *
     * @param class-string<T>   $commandClass
     * @param callable(T): void $handler
     */
    public function register(string $commandClass, callable $handler): void
    {
        $this->handlers[$commandClass] = $handler;
    }

    public function dispatch(Command $command): void
    {
        $handler = $this->handlers[$command::class] ?? throw NoHandlerForCommand::for($command);

        $handler($command);
    }
}

<?php

namespace App\Infrastructure\Events;

use App\Domain\Contracts\EventBusInterface;
use App\Domain\Events\DomainEvent;

/**
 * Simple synchronous event bus.
 * Listeners are called immediately in registration order.
 */
final class SyncEventBus implements EventBusInterface
{
    /** @var array<string, callable[]> */
    private array $listeners = [];

    public function dispatch(DomainEvent $event): void
    {
        $class = $event::class;

        foreach ($this->listeners[$class] ?? [] as $listener) {
            $listener($event);
        }
    }

    public function listen(string $eventClass, callable $listener): void
    {
        $this->listeners[$eventClass][] = $listener;
    }
}

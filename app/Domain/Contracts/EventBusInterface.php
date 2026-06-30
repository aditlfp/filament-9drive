<?php

namespace App\Domain\Contracts;

use App\Domain\Events\DomainEvent;

/**
 * Simple in-process event dispatcher.
 * For cross-process events, use Laravel's queue + listeners.
 */
interface EventBusInterface
{
    /**
     * Dispatch a domain event to all registered listeners.
     */
    public function dispatch(DomainEvent $event): void;

    /**
     * Register a listener for a specific event class.
     * @param class-string<DomainEvent> $eventClass
     */
    public function listen(string $eventClass, callable $listener): void;
}

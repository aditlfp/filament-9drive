<?php

namespace App\Domain\Events;

/**
 * Base for all domain events.
 */
abstract class DomainEvent
{
    public readonly \DateTimeImmutable $occurredAt;

    public function __construct()
    {
        $this->occurredAt = new \DateTimeImmutable();
    }
}

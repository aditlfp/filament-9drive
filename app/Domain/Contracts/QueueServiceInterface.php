<?php

namespace App\Domain\Contracts;

use Illuminate\Bus\Batch;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use RuntimeException;

/**
 * Abstraction over Laravel's queue system.
 * Allows swapping queue drivers without touching services.
 */
interface QueueServiceInterface
{
    /**
     * Dispatch a job to the queue.
     */
    public function push(object $job, ?string $queue = null): mixed;

    /**
     * Dispatch multiple jobs as a batch.
     * @param  object[]  $jobs
     */
    public function batch(array $jobs, string $name): ?Batch;

    /**
     * Dispatch a job after the response is sent.
     */
    public function afterResponse(object $job): void;
}

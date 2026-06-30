<?php

namespace App\Infrastructure\Context;

use App\Models\Workspace;
use RuntimeException;

/**
 * Holds the active workspace for the current request/job.
 * Populated by WorkspaceMiddleware (HTTP) or WorkspaceAwareJob (queue).
 */
final class WorkspaceContext
{
    private static ?Workspace $current = null;

    public static function set(Workspace $workspace): void
    {
        self::$current = $workspace;
    }

    public static function get(): Workspace
    {
        return self::$current ?? throw new RuntimeException(
            'No workspace context. Ensure WorkspaceMiddleware ran or WorkspaceAwareJob::setWorkspace() was called.'
        );
    }

    public static function getOrNull(): ?Workspace
    {
        return self::$current;
    }

    public static function clear(): void
    {
        self::$current = null;
    }

    public static function has(): bool
    {
        return self::$current !== null;
    }
}

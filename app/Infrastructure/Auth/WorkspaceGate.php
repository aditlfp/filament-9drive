<?php

namespace App\Infrastructure\Auth;

use App\Domain\Enums\Permission;
use App\Domain\Enums\WorkspaceRole;
use App\Infrastructure\Context\WorkspaceContext;
use App\Models\User;
use App\Models\Workspace;
use InvalidArgumentException;
use RuntimeException;

/**
 * Central authorization gate for workspace permissions.
 */
final class WorkspaceGate
{
    public static function check(Permission $permission): bool
    {
        $workspace = WorkspaceContext::getOrNull();
        $user = auth()->user();

        if (! $workspace || ! $user) {
            return false;
        }

        return static::can($user, $workspace, $permission);
    }

    public static function can(User $user, Workspace $workspace, Permission $permission): bool
    {
        $role = static::role($user, $workspace);

        return $role->can($permission);
    }

    public static function authorize(Permission $permission): void
    {
        if (! static::check($permission)) {
            $name = $permission->value;
            abort(403, "You do not have permission to {$name}.");
        }
    }

    public static function role(User $user, Workspace $workspace): WorkspaceRole
    {
        if ($workspace->owner_id === $user->id) {
            return WorkspaceRole::Admin;
        }

        $pivot = $workspace->users()->where('user_id', $user->id)->first()?->pivot;

        if (! $pivot) {
            throw new RuntimeException('User is not a member of this workspace.');
        }

        return WorkspaceRole::from($pivot->role);
    }
}

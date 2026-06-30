<?php

namespace App\Http\Middleware;

use App\Infrastructure\Context\WorkspaceContext;
use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;

/**
 * Resolves workspace from:
 * 1. Route segment  /admin/workspaces/{workspace}/...
 * 2. X-Workspace-Id header
 * 3. workspace_id session key
 * 4. User's first owned/joined workspace (fallback)
 */
class ResolveWorkspace
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $workspace = $this->resolve($request);

        if ($workspace) {
            // Ensure user actually belongs to this workspace
            if (! $workspace->hasUser($user)) {
                abort(403, 'You do not have access to this workspace.');
            }
            WorkspaceContext::set($workspace);
            session(['workspace_id' => $workspace->id]);
        } else {
            // No workspace found → auto-create default workspace for this user
            $workspace = Workspace::create([
                'owner_id' => $user->id,
                'name' => "My Workspace",
                'description' => 'Default workspace',
            ]);
            WorkspaceContext::set($workspace);
            session(['workspace_id' => $workspace->id]);
        }

        return $next($request);
    }

    private function resolve(Request $request): ?Workspace
    {
        // 1. Explicit route binding
        if ($id = $request->route('workspace')) {
            return Workspace::find($id instanceof Workspace ? $id->id : $id);
        }

        // 2. Header (API / JS clients)
        if ($id = $request->header('X-Workspace-Id')) {
            return Workspace::find($id);
        }

        // 3. Session (Filament panel)
        if ($id = session('workspace_id')) {
            return Workspace::find($id);
        }

        // 4. Fallback: user's first workspace
        return Workspace::where('owner_id', $request->user()->id)
            ->orWhereHas('users', fn ($q) => $q->where('user_id', $request->user()->id))
            ->orderBy('id')
            ->first();
    }
}

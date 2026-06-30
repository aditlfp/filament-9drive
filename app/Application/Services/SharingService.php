<?php

namespace App\Application\Services;

use App\Infrastructure\Context\WorkspaceContext;
use App\Models\Share;
use App\Models\ShareAccess;
use App\Models\VirtualFile;
use App\Models\VirtualFolder;
use Illuminate\Support\Facades\Hash;

class SharingService
{
    public function createShare(
        VirtualFile|VirtualFolder $resource,
        string $type = 'public',
        ?string $password = null,
        ?\DateTimeInterface $expiresAt = null,
        ?int $downloadLimit = null,
        array $metadata = [],
    ): Share {
        $workspace = WorkspaceContext::get();

        return Share::create([
            'workspace_id' => $workspace->id,
            'virtual_file_id' => $resource instanceof VirtualFile ? $resource->id : null,
            'virtual_folder_id' => $resource instanceof VirtualFolder ? $resource->id : null,
            'created_by' => auth()->id(),
            'type' => $type,
            'password_hash' => $password ? Hash::make($password) : null,
            'expires_at' => $expiresAt,
            'download_limit' => $downloadLimit,
            'is_active' => true,
            'metadata' => $metadata,
        ]);
    }

    public function validateAccess(Share $share, ?string $password = null): bool
    {
        if (! $share->isAccessible()) {
            return false;
        }

        if ($share->type === 'password' && ! $password) {
            return false;
        }

        if ($share->type === 'password' && ! Hash::check($password, $share->password_hash)) {
            return false;
        }

        if ($share->type === 'workspace_only' && WorkspaceContext::getOrNull()?->id !== $share->workspace_id) {
            return false;
        }

        return true;
    }

    public function recordAccess(Share $share, string $action = 'view'): void
    {
        ShareAccess::create([
            'share_id' => $share->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'action' => $action,
            'accessed_at' => now(),
        ]);

        if ($action === 'download') {
            $share->increment('download_count');
        }

        $share->update(['last_accessed_at' => now()]);
    }

    public function revokeShare(Share $share): void
    {
        $share->update(['is_active' => false]);
    }

    public function generateQrCode(Share $share): string
    {
        // Simple SVG QR code generator (external package could be added)
        // For now: return a placeholder or use a service like api.qrserver.com
        $url = urlencode($share->getPublicUrl());
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={$url}";
    }
}

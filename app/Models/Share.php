<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Share extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'workspace_id', 'virtual_file_id', 'virtual_folder_id', 'created_by',
        'token', 'type', 'password_hash', 'expires_at', 'download_limit',
        'download_count', 'last_accessed_at', 'is_active', 'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'is_active' => 'boolean',
        'download_count' => 'integer',
        'download_limit' => 'integer',
        'metadata' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $share) {
            if (empty($share->token)) {
                $share->token = Str::random(48);
            }
            if (empty($share->created_by)) {
                $share->created_by = auth()->id();
            }
        });
    }

    public function workspace(): BelongsTo    { return $this->belongsTo(Workspace::class); }
    public function file(): BelongsTo         { return $this->belongsTo(VirtualFile::class, 'virtual_file_id'); }
    public function folder(): BelongsTo       { return $this->belongsTo(VirtualFolder::class, 'virtual_folder_id'); }
    public function creator(): BelongsTo      { return $this->belongsTo(User::class, 'created_by'); }
    public function accesses(): HasMany       { return $this->hasMany(ShareAccess::class); }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isDownloadLimitReached(): bool
    {
        return $this->download_limit && $this->download_count >= $this->download_limit;
    }

    public function isAccessible(): bool
    {
        return $this->is_active && ! $this->isExpired() && ! $this->isDownloadLimitReached();
    }

    public function getPublicUrl(): string
    {
        return route('share.show', $this->token);
    }

    public function resourceName(): string
    {
        return $this->file?->name ?? $this->folder?->name ?? 'Unknown';
    }
}

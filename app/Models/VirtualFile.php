<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VirtualFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'virtual_folder_id',
        'connected_account_id',
        'provider_file_id',
        'name',
        'size',
        'mime_type',
        'extension',
        'is_favorite',
        'is_starred',
        'last_accessed_at',
        'metadata',
    ];

    protected $casts = [
        'size' => 'integer',
        'is_favorite' => 'boolean',
        'is_starred' => 'boolean',
        'last_accessed_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (VirtualFile $file) {
            if (empty($file->extension)) {
                $file->extension = pathinfo($file->name, PATHINFO_EXTENSION);
            }
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(VirtualFolder::class, 'virtual_folder_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ConnectedAccount::class, 'connected_account_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'virtual_file_tag');
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($index = 0; $bytes >= 1024 && $index < count($units) - 1; $index++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$index];
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    public function isAudio(): bool
    {
        return str_starts_with($this->mime_type, 'audio/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function icon(): string
    {
        return match (true) {
            $this->isImage() => 'heroicon-o-photo',
            $this->isPdf() => 'heroicon-o-document-text',
            $this->isVideo() => 'heroicon-o-video-camera',
            $this->isAudio() => 'heroicon-o-musical-note',
            str_contains($this->mime_type, 'zip') => 'heroicon-o-archive-box',
            default => 'heroicon-o-document',
        };
    }

    public function markAccessed(): void
    {
        $this->update(['last_accessed_at' => now()]);
    }
}

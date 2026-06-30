<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'folder_id',
        'storage_account_id',
        'provider_file_id',
        'name',
        'size',
        'mime_type',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Folder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * @return BelongsTo<ConnectedAccount, $this>
     */
    public function storageAccount(): BelongsTo
    {
        return $this->belongsTo(ConnectedAccount::class, 'storage_account_id');
    }

    public function scopeOwnedBy(Builder $query, User|int|null $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->whereHas('folder', fn(Builder $query) => $query->where('user_id', $userId));
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
        return in_array($this->mime_type, [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp',
            'image/gif',
            'image/svg+xml',
        ]);
    }

    public function previewUrl(): string
    {
        return route('file.preview.image', $this);
    }

    public function downloadUrl(): string
    {
        return route('file.download', $this);
    }

    // Ikon berdasarkan mime type — untuk tampilan grid/list
    public function icon(): string
    {
        return match (true) {
            $this->isImage()                          => 'heroicon-o-photo',
            str_contains($this->mime_type, 'pdf')     => 'heroicon-o-document-text',
            str_contains($this->mime_type, 'video')   => 'heroicon-o-video-camera',
            str_contains($this->mime_type, 'audio')   => 'heroicon-o-musical-note',
            str_contains($this->mime_type, 'zip')     => 'heroicon-o-archive-box',
            default                                   => 'heroicon-o-document',
        };
    }
}

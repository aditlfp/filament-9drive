<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UploadPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'name',
        'strategy',
        'rules',
        'weights',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'rules' => 'array',
        'weights' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}

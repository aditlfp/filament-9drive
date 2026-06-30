<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShareAccess extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'share_id', 'ip_address', 'user_agent', 'country_code', 'action', 'accessed_at',
    ];

    protected $casts = [
        'accessed_at' => 'datetime',
    ];

    public function share(): BelongsTo { return $this->belongsTo(Share::class); }
}

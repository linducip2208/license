<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activation extends Model
{
    protected $fillable = ['license_id', 'hwid', 'label', 'last_seen_at', 'revoked'];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'revoked' => 'boolean',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}

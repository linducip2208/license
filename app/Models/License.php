<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class License extends Model
{
    protected $fillable = [
        'code', 'customer_name', 'customer_email', 'product_id', 'user_id',
        'status', 'max_activations', 'expires_on', 'notes',
    ];

    protected $casts = [
        'expires_on' => 'date',
    ];

    public static function generateCode(): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        do {
            $raw = '';
            for ($i = 0; $i < 16; $i++) {
                $raw .= $alphabet[random_int(0, 35)];
            }
            $code = implode('-', str_split($raw, 4));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function activations(): HasMany
    {
        return $this->hasMany(Activation::class);
    }

    public function activeActivations(): HasMany
    {
        return $this->hasMany(Activation::class)->where('revoked', false);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_on !== null && $this->expires_on->isPast();
    }

    public function getEffectiveStatusAttribute(): string
    {
        if ($this->status !== 'active') return $this->status;
        if ($this->is_expired) return 'expired';
        return 'active';
    }

    /** Check a HWID against this license (used by the API). */
    public function check(string $hwid): array
    {
        $hwid = strtolower(trim($hwid));
        if ($this->status === 'suspended') {
            return [false, 'License is suspended.'];
        }
        if ($this->is_expired) {
            return [false, 'License has expired on ' . $this->expires_on->toDateString() . '.'];
        }
        if ($this->status !== 'active') {
            return [false, 'License is not active.'];
        }
        $used = $this->activeActivations()->count();
        $exists = $this->activeActivations()->where('hwid', $hwid)->exists();
        if (!$exists && $used >= $this->max_activations) {
            return [false, 'Activation limit reached (' . $used . '/' . $this->max_activations . ').'];
        }
        return [true, 'License is valid.'];
    }

    public function touchActivation(string $hwid, ?string $label = null): Activation
    {
        $hwid = strtolower(trim($hwid));
        $act = $this->activations()->firstOrNew(['hwid' => $hwid]);
        $act->revoked = false;
        if ($label !== null) $act->label = $label;
        $act->last_seen_at = Carbon::now();
        $act->save();
        return $act;
    }
}

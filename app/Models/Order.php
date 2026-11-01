<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'merchant_ref', 'product_id', 'customer_name', 'customer_email',
        'customer_phone', 'amount', 'status', 'tripay_reference',
        'payment_method', 'payment_name', 'pay_code', 'pay_url',
        'checkout_url', 'license_id', 'paid_at', 'payload',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'payload' => 'array',
    ];

    public static function newRef(): string
    {
        do {
            $ref = 'WS' . now()->format('ymdHis') . strtoupper(Str::random(4));
        } while (static::where('merchant_ref', $ref)->exists());

        return $ref;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'PAID';
    }

    /**
     * Terbitkan lisensi 1 tahun untuk order ini. Idempotent:
     * kalau lisensi sudah ada, kembalikan yang lama.
     */
    public function issueLicense(): License
    {
        if ($this->license_id && $this->license) {
            return $this->license;
        }

        $catalog = config('tripay.products.' . $this->product->slug, []);
        $days = (int) ($catalog['duration_days'] ?? 365);
        $max = (int) ($catalog['max_activations'] ?? 1);

        $license = License::create([
            'code' => License::generateCode(),
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'product_id' => $this->product_id,
            'user_id' => null, // diterbitkan sistem (otomatis)
            'status' => 'active',
            'max_activations' => $max,
            'expires_on' => Carbon::now()->addDays($days)->toDateString(),
            'notes' => 'Auto-issue dari order ' . $this->merchant_ref,
        ]);

        $this->update(['license_id' => $license->id]);

        ActivityLog::create([
            'user_id' => null,
            'license_id' => $license->id,
            'action' => 'auto_issued',
            'detail' => 'Order ' . $this->merchant_ref . ' lunas, lisensi ' . $license->code . ' terbit otomatis.',
            'ip' => request()->ip(),
        ]);

        return $license;
    }
}

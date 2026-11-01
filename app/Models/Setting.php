<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/** Key-value settings. Nilai sensitif (password) disimpan terenkripsi. */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        try {
            $row = static::where('key', $key)->first();
        } catch (\Throwable) {
            return $default; // tabel belum ada (fresh install)
        }
        return $row?->value ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        try {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        } catch (\Throwable) {
            // abaikan bila tabel belum termigrasi
        }
    }

    public static function setEncrypted(string $key, string $value): void
    {
        static::set($key, Crypt::encryptString($value));
    }

    public static function getDecrypted(string $key): ?string
    {
        $raw = static::get($key);
        if (!$raw) return null;
        try {
            return Crypt::decryptString($raw);
        } catch (\Throwable) {
            return null; // APP_KEY berubah / data korup → anggap kosong
        }
    }

    public static function has(string $key): bool
    {
        return (bool) static::get($key);
    }
}

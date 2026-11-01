<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

/**
 * Terapkan konfigurasi SMTP dari menu Settings (DB) menimpa .env.
 * Password tersimpan terenkripsi dan tidak pernah ditampilkan di UI.
 */
class MailConfig
{
    public static function applyFromSettings(): bool
    {
        try {
            if (!Schema::hasTable('settings')) {
                return false;
            }
            $host = Setting::get('mail_host');
            $username = Setting::get('mail_username');
            if (!$host || !$username) {
                return false; // belum dikonfigurasi → pakai .env apa adanya
            }

            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => $host,
                'mail.mailers.smtp.port' => (int) (Setting::get('mail_port') ?: 587),
                'mail.mailers.smtp.username' => $username,
                'mail.mailers.smtp.password' => Setting::getDecrypted('mail_password'),
                'mail.mailers.smtp.encryption' => Setting::get('mail_encryption') ?: 'tls',
                'mail.from.address' => Setting::get('mail_from_address') ?: config('mail.from.address'),
                'mail.from.name' => Setting::get('mail_from_name') ?: config('mail.from.name'),
            ]);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}

<?php

namespace App\Services;

use App\Mail\LicenseIssued;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Menandai order lunas + menerbitkan lisensi 1 tahun + kirim email.
 * Idempotent: aman dipanggil berulang (callback Tripay bisa double-hit).
 */
class OrderFulfiller
{
    /**
     * @param array $tripayData payload callback / detail Tripay
     * @return bool true bila order (kini) berstatus PAID
     */
    public function fulfill(Order $order, array $tripayData): bool
    {
        if ($order->isPaid()) {
            return true;
        }

        // Validasi nominal: gross yang dibayar customer harus >= harga order.
        // (amount_received lebih kecil karena dipotong fee merchant — jangan pakai itu.)
        $paidGross = (int) ($tripayData['amount'] ?? 0);
        if ($paidGross < $order->amount) {
            return false;
        }

        DB::transaction(function () use ($order, $tripayData) {
            $order->update([
                'status' => 'PAID',
                'paid_at' => now(),
                'payload' => array_merge($order->payload ?? [], ['fulfilled' => $tripayData]),
            ]);
            $order->issueLicense();
        });

        $this->sendMail($order->fresh('license'));

        return true;
    }

    private function sendMail(Order $order): void
    {
        if (!$order->customer_email || !$order->license) {
            return;
        }
        MailConfig::applyFromSettings(); // SMTP dari menu Settings (DB) bila ada
        try {
            Mail::to($order->customer_email)->send(new LicenseIssued($order));
        } catch (\Throwable $e) {
            report($e); // lisensi tetap terbit; email bisa dikirim ulang manual
        }
    }

    /**
     * Pelunasan manual (QRIS statis milik sendiri): admin sudah verifikasi
     * mutasi, lalu sistem terbitkan lisensi + kirim email otomatis.
     */
    public function fulfillManually(Order $order, ?string $by = null): bool
    {
        if ($order->isPaid()) {
            return true;
        }

        DB::transaction(function () use ($order, $by) {
            $order->update([
                'status' => 'PAID',
                'paid_at' => now(),
                'payload' => array_merge($order->payload ?? [], [
                    'manual_verified_by' => $by ?? auth()->user()?->email ?? 'admin',
                ]),
            ]);
            $order->issueLicense();
        });

        $this->sendMail($order->fresh('license'));

        return true;
    }
}

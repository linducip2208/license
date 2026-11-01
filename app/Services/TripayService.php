<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Wrapper API Tripay (Closed Payment).
 * @see https://tripay.co.id/developer
 */
class TripayService
{
    public function base(): string
    {
        return config('tripay.mode') === 'production'
            ? config('tripay.production_url')
            : config('tripay.sandbox_url');
    }

    public static function makeSignature(string $merchantCode, string $merchantRef, int $amount, string $privateKey): string
    {
        return hash_hmac('sha256', $merchantCode . $merchantRef . $amount, $privateKey);
    }

    public function verifyCallbackSignature(string $rawBody, ?string $headerSignature): bool
    {
        if (!$headerSignature) return false;
        $expected = hash_hmac('sha256', $rawBody, (string) config('tripay.private_key'));
        return hash_equals($expected, $headerSignature);
    }

    /**
     * Daftar channel aktif. Di-cache 10 menit; fallback ke daftar
     * kurasi bila API Tripay tidak bisa dihubungi.
     */
    public function channels(): array
    {
        return Cache::remember('tripay.channels', 600, function () {
            try {
                $res = Http::timeout(10)
                    ->withToken((string) config('tripay.api_key'))
                    ->get($this->base() . '/merchant/payment-channel');
                if ($res->successful() && ($res->json('success') ?? false)) {
                    $list = collect($res->json('data', []))
                        ->where('active', true)
                        ->values()
                        ->all();
                    if (count($list) > 0) return $list;
                }
            } catch (\Throwable $e) {
                report($e);
            }
            return $this->fallbackChannels();
        });
    }

    private function fallbackChannels(): array
    {
        foreach (
            [
                ['code' => 'QRIS2', 'name' => 'QRIS', 'group' => 'E-Wallet'],
                ['code' => 'BRIVA', 'name' => 'BRI Virtual Account', 'group' => 'Virtual Account'],
                ['code' => 'BCAVA', 'name' => 'BCA Virtual Account', 'group' => 'Virtual Account'],
                ['code' => 'MANDIRIVA', 'name' => 'Mandiri Virtual Account', 'group' => 'Virtual Account'],
                ['code' => 'BNIVA', 'name' => 'BNI Virtual Account', 'group' => 'Virtual Account'],
                ['code' => 'ALFAMART', 'name' => 'Alfamart', 'group' => 'Convenience Store'],
                ['code' => 'INDOMARET', 'name' => 'Indomaret', 'group' => 'Convenience Store'],
            ] as $c
        ) {
            $out[] = $c + ['type' => 'direct', 'active' => true, 'icon_url' => null];
        }
        return $out ?? [];
    }

    /**
     * Buat transaksi Tripay untuk sebuah order. Melempar RuntimeException
     * bila Tripay menolak, dengan pesan error aslinya.
     */
    public function createTransaction(Order $order, string $method, array $catalog): array
    {
        $merchantCode = (string) config('tripay.merchant_code');
        $signature = static::makeSignature(
            $merchantCode,
            $order->merchant_ref,
            $order->amount,
            (string) config('tripay.private_key')
        );

        $res = Http::timeout(20)
            ->withToken((string) config('tripay.api_key'))
            ->asForm()
            ->post($this->base() . '/transaction/create', [
                'method' => $method,
                'merchant_ref' => $order->merchant_ref,
                'amount' => $order->amount,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'customer_phone' => $order->customer_phone,
                'order_items' => [
                    [
                        'sku' => $order->product->slug,
                        'name' => $catalog['name'] . ' (12 bulan)',
                        'price' => $order->amount,
                        'quantity' => 1,
                    ],
                ],
                'callback_url' => route('tripay.callback'),
                'return_url' => $this->returnUrl($order),
                'expired_time' => now()->addDay()->timestamp,
                'signature' => $signature,
            ]);

        $json = $res->json();
        if (!$res->successful() || !($json['success'] ?? false)) {
            throw new \RuntimeException($json['message'] ?? 'Tripay menolak transaksi (HTTP ' . $res->status() . ').');
        }

        return $json['data'];
    }

    /**
     * Ke mana pembeli dikembalikan setelah bayar. Default: halaman sukses
     * di frontend statis (lisensi dikirim via email).
     */
    public function returnUrl(Order $order): string
    {
        $base = (string) config('tripay.return_base');
        if ($base !== '') {
            return rtrim($base, '?&') . '?ref=' . urlencode($order->merchant_ref);
        }
        return route('checkout.order', $order->merchant_ref);
    }

    /**
     * Cek status langsung ke Tripay (dipakai callback + tombol refresh).
     * Mengembalikan status string (PAID/UNPAID/...) atau null bila gagal.
     */
    public function fetchStatus(string $tripayReference): ?array
    {
        try {
            $res = Http::timeout(10)
                ->withToken((string) config('tripay.api_key'))
                ->get($this->base() . '/transaction/detail', ['reference' => $tripayReference]);
            if ($res->successful() && ($res->json('success') ?? false)) {
                return $res->json('data');
            }
        } catch (\Throwable $e) {
            report($e);
        }
        return null;
    }
}

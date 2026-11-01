<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\OrderFulfiller;
use App\Services\TripayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    private function catalog(string $slug): array
    {
        $product = Product::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $catalog = config('tripay.products.' . $slug);
        abort_unless($catalog, 404, 'Paket belum dikonfigurasi.');

        return [$product, $catalog];
    }

    /** Form checkout publik. GET /buy/{slug} */
    public function show(string $slug, TripayService $tripay): View
    {
        [$product, $catalog] = $this->catalog($slug);
        $channels = $tripay->channels();
        if (config('tripay.manual.enabled')) {
            $channels[] = [
                'code' => 'MANUAL_QRIS', 'name' => 'QRIS — scan & konfirmasi admin',
                'group' => 'QRIS', 'type' => 'direct', 'active' => true, 'icon_url' => null,
            ];
        }

        return view('checkout.show', compact('product', 'catalog', 'channels', 'slug'));
    }

    /** Buat order + transaksi Tripay, lalu arahkan ke halaman status. POST /buy/{slug} */
    public function store(Request $request, string $slug, TripayService $tripay): RedirectResponse
    {
        [$product, $catalog] = $this->catalog($slug);

        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:30',
            'method' => 'required|string|max:30',
        ]);

        $order = DB::transaction(function () use ($data, $product, $catalog) {
            $amount = $catalog['price'];
            $payload = [];
            if ($data['method'] === 'MANUAL_QRIS' && config('tripay.manual.enabled')) {
                $code = $this->uniqueCode($catalog['price']);
                $amount = $catalog['price'] + $code;
                $payload = ['manual' => true, 'unique_code' => $code, 'base_price' => $catalog['price']];
            }
            return Order::create([
                'merchant_ref' => Order::newRef(),
                'product_id' => $product->id,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'],
                'amount' => $amount,
                'status' => 'UNPAID',
                'payment_method' => $data['method'],
                'payload' => $payload,
            ]);
        });

        // QRIS manual: tanpa gateway — tampilkan QR + nominal unik.
        if ($order->payment_method === 'MANUAL_QRIS') {
            $order->update(['payment_name' => 'QRIS Manual']);
            return redirect()->route('checkout.order', $order->merchant_ref);
        }

        try {
            $trx = $tripay->createTransaction($order->fresh('product'), $data['method'], $catalog);
        } catch (\Throwable $e) {
            $order->update(['status' => 'FAILED', 'payload' => ['error' => $e->getMessage()]]);
            return back()->withInput()->withErrors(['method' => 'Gagal membuat pembayaran: ' . $e->getMessage()]);
        }

        $order->update([
            'tripay_reference' => $trx['reference'] ?? null,
            'payment_method' => $trx['payment_method'] ?? $data['method'],
            'payment_name' => $trx['payment_name'] ?? null,
            'pay_code' => isset($trx['pay_code']) ? (string) $trx['pay_code'] : null,
            'pay_url' => $trx['pay_url'] ?? null,
            'checkout_url' => $trx['checkout_url'] ?? null,
            'payload' => $trx,
        ]);

        return redirect()->route('checkout.order', $order->merchant_ref);
    }

    /** Halaman status/instruksi bayar + kode lisensi. GET /order/{ref} */
    public function order(string $ref): View
    {
        $order = Order::with(['product', 'license'])->where('merchant_ref', $ref)->firstOrFail();

        return view('checkout.status', compact('order'));
    }

    /** JSON polling untuk halaman status (dipakai juga oleh success.html di frontend statis). GET /order/{ref}/status */
    public function status(string $ref): JsonResponse
    {
        $order = Order::with('license')->where('merchant_ref', $ref)->firstOrFail();

        return response()->json([
            'status' => $order->status,
            'license_code' => $order->license?->code,
            'expires_on' => $order->license?->expires_on?->toDateString(),
        ])->header('Access-Control-Allow-Origin', (string) config('tripay.frontend_origin'));
    }

    /**
     * Kode unik 100–999 agar nominal tiap order manual berbeda sehingga
     * mutasi bank mudah dicocokkan. Dijamin unik di antara order UNPAID.
     */
    private function uniqueCode(int $basePrice): int
    {
        do {
            $code = random_int(100, 999);
        } while (Order::where('status', 'UNPAID')->where('amount', $basePrice + $code)->exists());

        return $code;
    }

    /**
     * Sinkronisasi manual bila callback terlambat (tombol "Saya sudah bayar").
     * POST /order/{ref}/refresh
     */
    public function refresh(string $ref, TripayService $tripay, OrderFulfiller $fulfiller): JsonResponse
    {
        $order = Order::with('license')->where('merchant_ref', $ref)->firstOrFail();

        if (!$order->isPaid() && $order->tripay_reference) {
            $detail = $tripay->fetchStatus($order->tripay_reference);
            if ($detail && strtoupper($detail['status'] ?? '') === 'PAID') {
                $fulfiller->fulfill($order, $detail);
            } elseif ($detail && in_array(strtoupper($detail['status'] ?? ''), ['EXPIRED', 'FAILED'])) {
                $order->update(['status' => strtoupper($detail['status'])]);
            }
        }

        return $this->status($ref);
    }
}

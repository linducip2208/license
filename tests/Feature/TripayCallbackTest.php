<?php

namespace Tests\Feature;

use App\Mail\LicenseIssued;
use App\Models\License;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TripayCallbackTest extends TestCase
{
    use RefreshDatabase;

    private string $privateKey = 'test-private-key-123';

    protected function setUp(): void
    {
        parent::setUp();
        config(['tripay.private_key' => $this->privateKey]);
        Mail::fake();
    }

    private function seedOrder(): Order
    {
        config(['tripay.products.personal' => [
            'name' => 'WA Sender Personal', 'price' => 199000,
            'max_activations' => 1, 'duration_days' => 365,
        ]]);
        $product = Product::create([
            'name' => 'WA Sender Personal', 'slug' => 'personal', 'is_active' => true,
        ]);
        return Order::create([
            'merchant_ref' => Order::newRef(),
            'product_id' => $product->id,
            'customer_name' => 'Budi',
            'customer_email' => 'budi@example.com',
            'customer_phone' => '0812000000',
            'amount' => 199000,
            'status' => 'UNPAID',
            'tripay_reference' => 'T000100000000000001',
        ]);
    }

    private function tripayPayload(Order $order, array $over = []): array
    {
        return array_merge([
            'reference' => 'T000100000000000001',
            'merchant_ref' => $order->merchant_ref,
            'payment_method' => 'QRIS2',
            'payment_name' => 'QRIS',
            'customer_name' => 'Budi',
            'customer_email' => 'budi@example.com',
            'amount' => 199000,
            'fee_merchant' => 2000,
            'amount_received' => 197000,
            'status' => 'PAID',
            'paid_at' => time(),
        ], $over);
    }

    private function postCallback(array $payload, ?string $sig = null)
    {
        $body = json_encode($payload);
        $sig ??= hash_hmac('sha256', $body, $this->privateKey);
        return $this->call('POST', '/api/tripay/callback', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CALLBACK_SIGNATURE' => $sig,
            'HTTP_X_CALLBACK_EVENT' => 'payment_status',
        ], $body);
    }

    public function test_valid_callback_issues_license_for_one_year(): void
    {
        $order = $this->seedOrder();

        $res = $this->postCallback($this->tripayPayload($order));
        $res->assertOk()->assertJson(['success' => true]);

        $order->refresh();
        $this->assertSame('PAID', $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertNotNull($order->license_id);

        $lic = License::find($order->license_id);
        $this->assertNotNull($lic);
        $this->assertSame('Budi', $lic->customer_name);
        $this->assertSame(1, $lic->max_activations);
        $this->assertSame('active', $lic->status);
        $this->assertSame(now()->addDays(365)->toDateString(), $lic->expires_on->toDateString());

        // Lisensi wajib terkirim ke email pembeli.
        Mail::assertSent(LicenseIssued::class, fn ($m) => $m->hasTo('budi@example.com'));
    }

    public function test_callback_is_idempotent(): void
    {
        $order = $this->seedOrder();
        $payload = $this->tripayPayload($order);

        $this->postCallback($payload)->assertOk();
        $this->postCallback($payload)->assertOk();

        $this->assertSame(1, License::count());
    }

    public function test_callback_rejects_bad_signature(): void
    {
        $order = $this->seedOrder();
        $this->postCallback($this->tripayPayload($order), 'salah')->assertForbidden();
        $this->assertSame('UNPAID', $order->fresh()->status);
        $this->assertSame(0, License::count());
    }

    public function test_callback_rejects_underpaid_amount(): void
    {
        $order = $this->seedOrder();
        $res = $this->postCallback($this->tripayPayload($order, ['amount' => 1000]));
        $res->assertOk(); // signature valid, tapi nominal kurang → tidak diterbitkan
        $this->assertSame('UNPAID', $order->fresh()->status);
        $this->assertSame(0, License::count());
    }

    public function test_callback_unknown_order_returns_404(): void
    {
        $this->seedOrder();
        $order = Order::first();
        $this->postCallback($this->tripayPayload($order, ['merchant_ref' => 'WS-TIDAK-ADA']))->assertNotFound();
    }

    public function test_expired_callback_marks_order(): void
    {
        $order = $this->seedOrder();
        $this->postCallback($this->tripayPayload($order, ['status' => 'EXPIRED']))->assertOk();
        $this->assertSame('EXPIRED', $order->fresh()->status);
        $this->assertSame(0, License::count());
    }
}

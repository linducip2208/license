<?php

namespace Tests\Feature;

use App\Mail\LicenseIssued;
use App\Models\License;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ManualQrisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['tripay.manual.enabled' => true]);
        config(['tripay.products.personal' => [
            'name' => 'WA Sender Personal', 'price' => 199000,
            'max_activations' => 1, 'duration_days' => 365,
        ]]);
        Product::create(['name' => 'WA Sender Personal', 'slug' => 'personal', 'is_active' => true]);
        Http::fake(); // pastikan tidak ada panggilan HTTP (tanpa gateway)
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_manual_qris_creates_order_with_unique_amount(): void
    {
        $this->post('/buy/personal', [
            'customer_name' => 'Andi',
            'customer_email' => 'andi@example.com',
            'customer_phone' => '0814000000',
            'method' => 'MANUAL_QRIS',
            '_token' => csrf_token(),
        ])->assertRedirect();

        $order = Order::first();
        $this->assertSame('MANUAL_QRIS', $order->payment_method);
        $this->assertSame('UNPAID', $order->status);
        $this->assertTrue($order->amount >= 199100 && $order->amount <= 199999);
        $this->assertTrue($order->payload['manual'] ?? false);

        Http::assertNothingSent(); // tidak menyentuh Tripay sama sekali
    }

    public function test_admin_fulfill_issues_license_and_email(): void
    {
        $this->post('/buy/personal', [
            'customer_name' => 'Andi',
            'customer_email' => 'andi@example.com',
            'customer_phone' => '0814000000',
            'method' => 'MANUAL_QRIS',
            '_token' => csrf_token(),
        ]);

        $order = Order::first();
        $this->actingAs($this->admin())
            ->post(route('orders.fulfill', $order))
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('PAID', $order->status);
        $this->assertNotNull($order->license_id);
        $this->assertSame(1, License::count());
        Mail::assertSent(LicenseIssued::class, fn ($m) => $m->hasTo('andi@example.com'));
    }

    public function test_non_admin_cannot_fulfill(): void
    {
        $reseller = User::factory()->create(['role' => 'reseller']);
        $order = Order::create([
            'merchant_ref' => Order::newRef(), 'product_id' => Product::first()->id,
            'customer_name' => 'X', 'customer_email' => 'x@example.com',
            'customer_phone' => '0814', 'amount' => 199123, 'status' => 'UNPAID',
            'payment_method' => 'MANUAL_QRIS',
        ]);

        $this->actingAs($reseller)->post(route('orders.fulfill', $order))->assertForbidden();
        $this->assertSame('UNPAID', $order->fresh()->status);
    }
}

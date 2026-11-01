<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['tripay.products.personal' => [
            'name' => 'WA Sender Personal', 'price' => 199000,
            'max_activations' => 1, 'duration_days' => 365,
        ]]);
        Product::create(['name' => 'WA Sender Personal', 'slug' => 'personal', 'is_active' => true]);

        Http::fake([
            '*/merchant/payment-channel' => Http::response([
                'success' => true,
                'data' => [
                    ['code' => 'QRIS2', 'name' => 'QRIS', 'group' => 'E-Wallet', 'type' => 'direct', 'active' => true, 'icon_url' => null],
                    ['code' => 'BRIVA', 'name' => 'BRI Virtual Account', 'group' => 'Virtual Account', 'type' => 'direct', 'active' => true, 'icon_url' => null],
                ],
            ]),
            '*/transaction/create' => Http::response([
                'success' => true,
                'data' => [
                    'reference' => 'T000100000000000001',
                    'merchant_ref' => 'WSFAKE',
                    'payment_method' => 'QRIS2',
                    'payment_name' => 'QRIS',
                    'pay_code' => 'QR123456',
                    'checkout_url' => 'https://tripay.co.id/checkout/T000100000000000001',
                    'status' => 'UNPAID',
                ],
            ]),
        ]);
    }

    public function test_checkout_page_renders_with_channels(): void
    {
        $this->get('/buy/personal')
            ->assertOk()
            ->assertSee('199.000')
            ->assertSee('QRIS2', false)
            ->assertSee('BRIVA', false);
    }

    public function test_checkout_creates_order_and_redirects_to_status(): void
    {
        $res = $this->post('/buy/personal', [
            'customer_name' => 'Siti',
            'customer_email' => 'siti@example.com',
            'customer_phone' => '0813000000',
            'method' => 'QRIS2',
            '_token' => csrf_token(),
        ]);

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertSame('UNPAID', $order->status);
        $this->assertSame(199000, $order->amount);
        $this->assertSame('T000100000000000001', $order->tripay_reference);
        $res->assertRedirect(route('checkout.order', $order->merchant_ref));

        $this->get(route('checkout.order', $order->merchant_ref))->assertOk()->assertSee('QR123456');
        $this->getJson(route('checkout.status', $order->merchant_ref))
            ->assertOk()->assertJson(['status' => 'UNPAID']);
    }

    public function test_checkout_unknown_product_404(): void
    {
        $this->get('/buy/tidak-ada')->assertNotFound();
    }

    public function test_checkout_validates_input(): void
    {
        $this->post('/buy/personal', ['_token' => csrf_token()])
            ->assertSessionHasErrors(['customer_name', 'customer_email', 'customer_phone', 'method']);
        $this->assertSame(0, Order::count());
    }
}

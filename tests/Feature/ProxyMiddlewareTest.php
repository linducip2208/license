<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProxyMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['tripay.proxy_secret' => 'kunci-rahasia-uji']);
        config(['tripay.products.personal' => [
            'name' => 'WA Sender Personal', 'price' => 199000,
            'max_activations' => 1, 'duration_days' => 365,
        ]]);
        Product::create(['name' => 'WA Sender Personal', 'slug' => 'personal', 'is_active' => true]);
        Http::fake([
            '*/merchant/payment-channel' => Http::response(['success' => true, 'data' => []]),
        ]);
    }

    public function test_checkout_hidden_without_proxy_secret(): void
    {
        $this->get('/buy/personal')->assertNotFound();
    }

    public function test_checkout_open_with_valid_proxy_secret(): void
    {
        $this->get('/buy/personal', ['X-Proxy-Secret' => 'kunci-rahasia-uji'])->assertOk();
    }

    public function test_checkout_rejects_wrong_proxy_secret(): void
    {
        $this->get('/buy/personal', ['X-Proxy-Secret' => 'salah'])->assertNotFound();
    }
}

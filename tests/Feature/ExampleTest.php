<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_verify_api_menolak_kode_tak_dikenal(): void
    {
        $this->postJson('/api/v1/licenses/verify', [
            'code' => 'XXXX-XXXX-XXXX-XXXX',
            'hwid' => 'TESTHWID',
        ])->assertOk()->assertJson(['valid' => false]);
    }

    public function test_verify_api_menerima_lisensi_valid(): void
    {
        $user = User::factory()->create();
        $product = \App\Models\Product::create(['name' => 'WA Sender', 'slug' => 'wa-sender']);
        $license = \App\Models\License::create([
            'code' => \App\Models\License::generateCode(),
            'customer_name' => 'Tes',
            'product_id' => $product->id,
            'user_id' => $user->id,
            'status' => 'active',
            'max_activations' => 1,
        ]);
        $this->postJson('/api/v1/licenses/verify', [
            'code' => $license->code,
            'hwid' => 'PC-001',
        ])->assertOk()->assertJson(['valid' => true]);
    }
}

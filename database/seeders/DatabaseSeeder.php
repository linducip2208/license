<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@wasender.local'],
            ['name' => 'Administrator', 'password' => Hash::make('admin123'), 'role' => 'admin']
        );
        User::firstOrCreate(
            ['email' => 'lindu@act.co.id'],
            ['name' => 'Lindu Cipta Pranayama', 'password' => Hash::make('reseller123'), 'role' => 'reseller']
        );
        Product::firstOrCreate(
            ['slug' => 'wa-sender'],
            ['name' => 'WA Sender', 'description' => 'WhatsApp marketing & automation suite', 'is_active' => true]
        );
    }
}

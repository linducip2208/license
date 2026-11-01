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
            ['slug' => 'personal'],
            ['name' => 'WA Sender Personal', 'description' => 'Lisensi personal 12 bulan, 1 perangkat', 'is_active' => true]
        );
        Product::firstOrCreate(
            ['slug' => 'reseller'],
            ['name' => 'WA Sender Reseller', 'description' => 'Lisensi reseller 12 bulan, 3 perangkat', 'is_active' => true]
        );
        Product::firstOrCreate(
            ['slug' => 'distributor'],
            ['name' => 'WA Sender Distributor', 'description' => 'Lisensi distributor 12 bulan, 10 perangkat', 'is_active' => true]
        );
    }
}

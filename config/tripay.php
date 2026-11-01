<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tripay credentials — isi dari dashboard Tripay.
    | Sandbox : Member > API & Integrasi > Simulator > Merchant > Detail
    | Produksi: Merchant > Opsi > Edit (merchant harus sudah dibuat)
    |--------------------------------------------------------------------------
    */
    'mode' => env('TRIPAY_MODE', 'sandbox'), // sandbox | production
    'api_key' => env('TRIPAY_API_KEY', ''),
    'private_key' => env('TRIPAY_PRIVATE_KEY', ''),
    'merchant_code' => env('TRIPAY_MERCHANT_CODE', ''),

    'sandbox_url' => 'https://tripay.co.id/api-sandbox',
    'production_url' => 'https://tripay.co.id/api',

    /*
    | Halaman sukses di frontend statis. Pembeli dikembalikan ke sini
    | setelah bayar (return_url), lisensinya dikirim via email.
    | Kosongkan (= null) untuk memakai halaman order bawaan panel.
    */
    'return_base' => env('TRIPAY_RETURN_BASE', 'https://wasender.pandusolusi.com/success.html'),
    'frontend_origin' => env('FRONTEND_ORIGIN', 'https://wasender.pandusolusi.com'),

    /*
    | Kunci proxy: panel HANYA melayani halaman checkout bila request
    | membawa header X-Proxy-Secret ini (dikirim oleh Pages Functions
    | di wasender.pandusolusi.com). Tanpa header yang cocok → 404.
    | Kosongkan hanya untuk dev lokal; di produksi wajib diisi.
    */
    'proxy_secret' => env('PROXY_SECRET', ''),

    /*
    | QRIS manual (milik sendiri, tanpa gateway). Pembeli scan QR statis
    | dengan nominal UNIK per order (harga + kode 100–999) agar mutasi
    | mudah dicocokkan, lalu admin klik "Tandai Lunas" → lisensi + email
    | otomatis terkirim. Gambar QRIS statis milik sendiri (public/qris.jpg).
    */
    'manual' => [
        'enabled' => env('MANUAL_QRIS_ENABLED', true),
        'image' => env('MANUAL_QRIS_IMAGE', '/qris.jpg'),
        'whatsapp' => env('ADMIN_WHATSAPP', '6281296052010'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Katalog paket jualan. Harga IDR / 12 bulan.
    | slug harus cocok dengan kolom `slug` di tabel products.
    |--------------------------------------------------------------------------
    */
    'products' => [
        'personal' => [
            'name' => 'WA Sender Personal',
            'price' => (int) env('PRICE_PERSONAL', 199000),
            'max_activations' => (int) env('ACTIVATIONS_PERSONAL', 1),
            'duration_days' => 365,
        ],
        'reseller' => [
            'name' => 'WA Sender Reseller',
            'price' => (int) env('PRICE_RESELLER', 499000),
            'max_activations' => (int) env('ACTIVATIONS_RESELLER', 3),
            'duration_days' => 365,
        ],
        'distributor' => [
            'name' => 'WA Sender Distributor',
            'price' => (int) env('PRICE_DISTRIBUTOR', 999000),
            'max_activations' => (int) env('ACTIVATIONS_DISTRIBUTOR', 10),
            'duration_days' => 365,
        ],
    ],
];

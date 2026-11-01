# License Panel — Deploy ke aaPanel

Panel manajemen lisensi (Laravel 12 + MySQL), gaya control panel referensi:
dashboard, licenses (CRUD, renew, suspend, regenerate, extend, bulk, CSV/Excel),
API verifikasi HWID untuk aplikasi desktop, API tokens (Sanctum), logs, restore, profile.

## 1. Persiapan di aaPanel

1. Buat Website → PHP 8.2+ (disarankan 8.3), root ke folder project.
2. Buat Database MySQL (mis. `license_panel`), catat user + password.
3. (Opsional tapi disarankan) aktifkan SSL (Let's Encrypt) — panel + API harus HTTPS.

## 2. Upload & install

```bash
cd /www/wwwroot/license-panel   # sesuaikan
# upload semua file KECUALI: node_modules, .git, tests (boleh ikut, tidak dipakai)
composer install --no-dev --optimize-autoloader
cp .env.example .env   # lalu isi (lihat bawah)
php artisan key:generate --force
php artisan migrate --force --seed
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Isi `.env` penting:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://lisensi.domain-anda.id
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=license_panel
DB_USERNAME=xxx
DB_PASSWORD=xxx
SESSION_DRIVER=database   # (default repo ini)
```

> Jangan pernah commit/upload `private_key` apa pun ke repo — tidak ada secret
> yang di-hardcode di project ini.

## 3. Akun awal (dari seeder)

| Role     | Email                | Password    |
|----------|----------------------|-------------|
| admin    | admin@wasender.local | admin123    |
| reseller | lindu@act.co.id      | reseller123 |

**Wajib ganti password setelah login pertama** (menu Profile).
Reseller hanya melihat/mengelola lisensi miliknya sendiri; admin melihat semua.

## 4. API untuk aplikasi WASender

- `POST /api/v1/licenses/verify` (public, rate-limit 60/menit)
  Body: `{ "code": "XXXX-XXXX-XXXX-XXXX", "hwid": "...", "label": "PC-1", "app_version": "1.0" }`
  → `{ valid, message, status, product, customer, expires_on, activations: "1/2" }`
- `POST /api/v1/licenses/release` (Bearer Sanctum token, menu API/MCP)
  Body: `{ "code": "...", "hwid": "..." }` → cabut aktivasi HWID (pindah PC).

Aturan: 1 HWID = 1 slot aktivasi; HWID baru melebihi `max_activations` ditolak;
lisensi suspended/expired ditolak; setiap verify sukses update `last_seen_at`.

## 5. Operasional harian

- **Buat lisensi:** Licenses → Create New License (kode otomatis format XXXX-XXXX-XXXX-XXXX).
- **Perpanjang:** tombol Renew di tabel / detail (tambah N hari, otomatis aktif lagi).
- **Blokir:** Suspend; **cabut & ganti kode:** Regenerate (aktivasi lama ikut dicabut).
- **Pindah PC customer:** Revoke aktivasi HWID di halaman detail, atau customer panggil API release.
- **Cari lisensi hilang:** menu Restore license (by kode / email).
- **Ekspor:** tombol CSV / Excel mengikuti filter yang aktif.
- **Audit:** menu Logs mencatat siapa melakukan apa (create/update/suspend/delete/extend/regenerate/revoke).

## 6. Catatan

- Status `expired` dihitung otomatis dari `expires_on` (tidak butuh cron).
- Kode lisensi unik, dibuat acak aman (`random_int`, alfabet A–Z0–9).
- Bulk action: pilih via checkbox → Actions → suspend/activate/delete.

# Jualan Otomatis — Tripay + Auto-Issue Lisensi 1 Tahun

Alur: pembeli klik paket di website statis → form checkout di panel ini
(`/buy/{personal,reseller,distributor}`) → bayar via Tripay → callback
Tripay → lisensi 12 bulan terbit otomatis + tampil di halaman + terkirim via email.
Tanpa kerja manual.

## 1. Ambil kredensial Tripay (2 menit)

| Mode | Lokasi di dashboard Tripay |
|---|---|
| Sandbox (testing) | Member → API & Integrasi → Simulator → Merchant → Detail |
| Produksi | Merchant → Opsi → Edit (buat merchant dulu bila belum ada) |

Salin 3 nilai: **Merchant Code, API Key, Private Key**.
Aktifkan channel yang dipakai (QRIS, VA bank, retail):
sandbox di Simulator → Merchant → Channel Pembayaran; produksi di
API & Integrasi → Merchant → Opsi → Atur Channel Pembayaran.

Callback URL **tidak wajib** diisi di dashboard — setiap transaksi sudah
mengirim `callback_url=https://license.pandusolusi.com/api/tripay/callback`
otomatis. (Boleh juga diisi manual dengan URL yang sama sebagai cadangan.)

## 2. Isi `.env` di server (license.pandusolusi.com)

```
APP_URL=https://license.pandusolusi.com   # wajib, untuk callback/return URL
TRIPAY_MODE=sandbox        # ganti production saat go-live
TRIPAY_API_KEY=...
TRIPAY_PRIVATE_KEY=...
TRIPAY_MERCHANT_CODE=...
```

Harga & aktivasi default (bisa override via `PRICE_*` / `ACTIVATIONS_*`):
Personal Rp199rb/1 PC · Reseller Rp499rb/3 PC · Distributor Rp999rb/10 PC.

## 3. Deploy / update

```bash
php artisan migrate --force
php artisan db:seed --force        # menambah produk personal/reseller/distributor
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

## 4. Uji sandbox

1. Buka `https://license.pandusolusi.com/buy/personal`, isi form, pilih QRIS.
2. Bayar via **Simulator Tripay** (saldo sandbox) — atau tunggu expired untuk uji gagal.
3. Callback masuk → halaman order menampilkan **kode lisensi** otomatis.
4. Cek di panel: Licenses → kode baru atas nama pembeli, expires +365 hari.
5. Uji tombol "Saya sudah bayar" (pull status langsung, jaga-jaga callback lambat).
6. Kalau lolos, ganti `TRIPAY_MODE=production` + kredensial produksi.

## 5. Email lisensi (diisi via menu Settings, bukan .env)

Tanpa SMTP, kode lisensi tetap tampil di halaman sukses — tapi agar
terkirim via email, login sebagai admin → menu **Settings** → isi SMTP
(host, port, username, **password**, from) → Simpan → **Kirim Tes** ke
email sendiri. Contoh Gmail: host `smtp.gmail.com`, port 587, TLS,
password = App Password (16 huruf dari Google Account → Security).

Keamanan: password disimpan **terenkripsi** (`Crypt`, kunci `APP_KEY`)
dan **tidak pernah ditampilkan balik** di form (kolom selalu kosong;
tanda TERSIMPAN AMAN hanya penanda). Pengiriman lisensi otomatis memakai
pengaturan ini; `.env` `MAIL_*` hanya jadi cadangan bila Settings kosong.

## 6. Sembunyikan panel dari publik (wajib)

Domain panel **tidak boleh diketahui umum**. Mekanismenya berlapis:

1. **Frontend tidak memuat domain panel sama sekali** — tombol beli ke
   `/buy/...` (same-origin), diproxy oleh Pages Functions
   (`wasenderme/functions/`) ke panel. Verifikasi: tidak ada string
   `license.pandusolusi.com` di file browser (`index.html`, `success.html`, dsb).
2. **Panel hanya melayani request ber-stempel** — halaman checkout/order
   butuh header `X-Proxy-Secret: <PROXY_SECRET>` (middleware `proxied`),
   selain itu → 404. Isi `PROXY_SECRET` acak panjang di `.env` panel **dan**
   di Cloudflare Pages → Settings → Variables (nama `PROXY_SECRET`, nilai sama).
3. **Callback Tripay tetap langsung ke panel** (`/api/tripay/callback`) —
   ini server-to-server (Tripay wajib tahu URL-nya, pengunjung tidak).
   Diamankan via signature HMAC, bukan kerahasiaan URL.
4. **Anti-indeks**: `public/robots.txt` = Disallow all + header
   `X-Robots-Tag: noindex` di semua response.
5. **Di Cloudflare** (domain panel): Proxy ON + WAF rule opsional —
   blokir path `/login`, `/licenses/*`, `/api-tokens` dari negara/IP
   yang tidak kamu pakai; biarkan `/api/*` terbuka (dipakai aplikasi
   desktop + Tripay, sudah ada auth/rate-limit/signature).

Yang tetap publik dan memang harus publik: `wasender.pandusolusi.com`
dan API verify yang dipakai aplikasi desktop (rate-limit 60/menit).

- Secret Tripay hanya di server (`.env`), tidak pernah ke browser.
- Callback diverifikasi HMAC-SHA256 + nominal dicek ulang (>= harga order).
- Callback idempotent: double-hit tidak menerbitkan 2 lisensi.
- Jangan pasang WAF challenge / Bot Fight Mode Cloudflare di path `/api/*`
  karena server Tripay tidak bisa menyelesaikan challenge.

## 8. Whitelist IP origin (VPS panel hanya boleh dihubungi Cloudflare)

Karena DNS panel di-proxy (orange cloud), **semua** trafik — termasuk
callback Tripay — tiba di origin dari IP Cloudflare. Jadi firewall origin
cukup mengizinkan daftar resmi Cloudflare (terverifikasi 2026-11-01 dari
https://www.cloudflare.com/ips-v4 dan /ips-v6):

```
# IPv4
173.245.48.0/20  103.21.244.0/22  103.22.200.0/22  103.31.4.0/22
141.101.64.0/18  108.162.192.0/18 190.93.240.0/20  188.114.96.0/20
197.234.240.0/22 198.41.128.0/17  162.158.0.0/15   104.16.0.0/13
104.24.0.0/14    172.64.0.0/13     131.0.72.0/22
# IPv6
2400:cb00::/32 2606:4700::/32 2803:f800::/32 2405:b500::/32
2405:8100::/32 2a06:98c0::/29 2c0f:f248::/32
```

Opsi A — UFW (SSH, hati-hati jangan sampai terkunci; pastikan ada akses
konsol/VNC sebelum apply):

```bash
for net in 173.245.48.0/20 103.21.244.0/22 103.22.200.0/22 103.31.4.0/22 \
  141.101.64.0/18 108.162.192.0/18 190.93.240.0/20 188.114.96.0/20 \
  197.234.240.0/22 198.41.128.0/17 162.158.0.0/15 104.16.0.0/13 \
  104.24.0.0/14 172.64.0.0/13 131.0.72.0/22; do
  ufw allow from $net to any port 80,443 proto tcp
done
ufw allow from IP-KANTORMU to any port 22 proto tcp   # SSH hanya dari IP kamu
ufw --force enable
```

Opsi B — aaPanel: Security → Firewall → tambah rule Accept untuk tiap
range di atas (port 80,443), lalu pastikan rule default selain itu Drop.
Jangan tutup port SSH untuk IP kamu sendiri.

Catatan:
- Tripay **tidak perlu** di-whitelist — callback-nya lewat edge Cloudflare
  (publik), keamanannya via signature HMAC (sudah dites).
- Setelah proxy ON, app sudah `trustProxies(at: '*')` agar IP asli
  pengunjung tercatat benar di throttle + activity log.
- Penguatan opsional: Authenticated Origin Pulls — lihat file
  `nginx-origin-pull.conf` (3 baris config + langkah aaPanel). Disarankan
  dipasang setelah firewall §8 stabil.

## 7. Sambungan ke website statis (Cloudflare)

Tombol pricing di site statis mengarah ke path same-origin (diproxy,
domain panel tidak terlihat pengunjung):

- `https://wasender.pandusolusi.com/buy/personal`
- `https://wasender.pandusolusi.com/buy/reseller`
- `https://wasender.pandusolusi.com/buy/distributor`

Setelah bayar, pembeli kembali ke `/success.html?ref=...` dan lisensi
dikirim ke email.

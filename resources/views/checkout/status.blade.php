<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order {{ $order->merchant_ref }}</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen">
<div class="max-w-2xl mx-auto px-4 py-10">
  <div class="bg-white rounded-2xl shadow p-6 md:p-8 text-center" id="box">
    <p class="text-xs text-gray-400">Order {{ $order->merchant_ref }} · {{ $order->product->name }}</p>

    <div id="unpaid" class="{{ $order->isPaid() ? 'hidden' : '' }}">
      <span class="inline-block mt-3 text-xs font-bold px-3 py-1 rounded-full bg-yellow-100 text-yellow-800" id="badge">MENUNGGU PEMBAYARAN</span>
      @if ($order->payment_method === 'MANUAL_QRIS')
        <p class="mt-4 text-sm text-gray-500">Scan QRIS berikut dengan nominal <b>tepat</b> (sudah termasuk kode unik):</p>
        <img src="{{ config('tripay.manual.image') }}" alt="QRIS pembayaran" class="mx-auto mt-2 w-56 h-56 object-contain border rounded-lg bg-white p-2">
        <div class="mt-2 font-mono text-3xl font-bold tracking-widest bg-gray-100 rounded-lg py-4 select-all">Rp {{ number_format($order->amount, 0, ',', '.') }}</div>
        @if (config('tripay.manual.whatsapp'))
          <a class="mt-3 inline-block bg-green-600 text-white font-bold rounded-lg px-5 py-3" target="_blank"
             href="https://wa.me/{{ config('tripay.manual.whatsapp') }}?text={{ urlencode('Halo, saya sudah bayar order ' . $order->merchant_ref . ' sebesar Rp ' . number_format($order->amount, 0, ',', '.') . '. Mohon verifikasi.') }}">Saya sudah bayar — konfirmasi via WhatsApp</a>
        @endif
        <p class="mt-3 text-xs text-gray-400">Lisensi + email terkirim otomatis setelah admin memverifikasi mutasi.</p>
      @elseif ($order->pay_code)
        <p class="mt-4 text-sm text-gray-500">{{ $order->payment_name }} — bayar ke nomor berikut:</p>
        <div class="mt-2 font-mono text-3xl font-bold tracking-widest bg-gray-100 rounded-lg py-4 select-all">{{ $order->pay_code }}</div>
        <p class="mt-2 text-sm">Nominal: <b>Rp {{ number_format($order->amount, 0, ',', '.') }}</b></p>
      @endif
      <div class="mt-4 flex flex-col gap-2">
        @if ($order->checkout_url)
          <a href="{{ $order->checkout_url }}" class="bg-black text-white font-bold rounded-lg py-3">Buka Halaman Pembayaran</a>
        @endif
        <button onclick="refreshNow()" class="border font-semibold rounded-lg py-2">Saya sudah bayar — cek status</button>
      </div>
      <p class="mt-3 text-xs text-gray-400" id="hint">Halaman ini mengecek otomatis setiap 5 detik. Lisensi terbit sendiri setelah pembayaran terkonfirmasi.</p>
    </div>

    <div id="paid" class="{{ $order->isPaid() ? '' : 'hidden' }}">
      <span class="inline-block mt-3 text-xs font-bold px-3 py-1 rounded-full bg-green-100 text-green-800">LUNAS — LISENSI TERBIT</span>
      <div class="mt-3 font-mono text-2xl md:text-3xl font-bold tracking-widest bg-green-50 border border-green-200 rounded-lg py-4 select-all" id="lic">{{ $order->license?->code ?? '' }}</div>
      <button onclick="navigator.clipboard.writeText(document.getElementById('lic').innerText);this.innerText='Tersalin!'" class="mt-2 text-sm border rounded-lg px-4 py-2">Salin kode</button>
      <div class="mt-4 text-left text-sm bg-gray-50 rounded-lg p-4">
        <b>Cara aktivasi:</b>
        <ol class="list-decimal ml-5 mt-1 space-y-1 text-gray-600">
          <li>Buka aplikasi WASender di PC Anda</li>
          <li>Masukkan kode lisensi di atas</li>
          <li>Klik aktivasi — berlaku sampai <b id="exp">{{ $order->license?->expires_on?->toDateString() }}</b></li>
        </ol>
        <p class="mt-2 text-gray-500">Kode juga dikirim ke email {{ $order->customer_email }}. Simpan baik-baik.</p>
      </div>
    </div>
  </div>
</div>
<script>
const ref = @json($order->merchant_ref);
async function poll() {
  try {
    const r = await fetch(`/order/${ref}/status`);
    const j = await r.json();
    if (j.status === 'PAID' && j.license_code) {
      document.getElementById('unpaid').classList.add('hidden');
      document.getElementById('paid').classList.remove('hidden');
      document.getElementById('lic').innerText = j.license_code;
      document.getElementById('exp').innerText = j.expires_on || '';
      clearInterval(timer);
    } else if (j.status === 'EXPIRED' || j.status === 'FAILED') {
      document.getElementById('hint').innerText = 'Transaksi ' + j.status + '. Silakan buat order baru.';
      clearInterval(timer);
    }
  } catch (e) {}
}
async function refreshNow() {
  await fetch(`/order/${ref}/refresh`, {method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}});
  poll();
}
const timer = setInterval(poll, 5000);
</script>
</body>
</html>

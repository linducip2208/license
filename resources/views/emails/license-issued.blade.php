<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Lisensi Anda Terbit</title></head>
<body style="font-family:Arial,sans-serif;line-height:1.6;color:#222;max-width:600px;margin:0 auto;padding:24px;">
  <h2>Terima kasih, {{ $order->customer_name }}!</h2>
  <p>Pembayaran order <b>{{ $order->merchant_ref }}</b> sudah kami terima. Berikut lisensi Anda:</p>
  <p style="background:#f4f4f4;border:1px dashed #999;padding:16px;font-size:22px;letter-spacing:2px;text-align:center;font-family:monospace;">
    <b>{{ $order->license->code }}</b>
  </p>
  <ul>
    <li>Produk: {{ $order->product->name }}</li>
    <li>Maksimal aktivasi: {{ $order->license->max_activations }} perangkat</li>
    <li>Berlaku sampai: {{ $order->license->expires_on->toDateString() }}</li>
  </ul>
  <p>Cara pakai: buka aplikasi WASender → masukkan kode lisensi di atas → aplikasi otomatis teraktivasi.</p>
  <p>Kode hilang? Balas email ini atau hubungi admin dengan menyebutkan order {{ $order->merchant_ref }}.</p>
</body>
</html>

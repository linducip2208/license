<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout — {{ $catalog['name'] }}</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen">
<div class="max-w-2xl mx-auto px-4 py-10">
  <div class="bg-white rounded-2xl shadow p-6 md:p-8">
    <h1 class="text-2xl font-bold">{{ $catalog['name'] }}</h1>
    <p class="text-gray-500 text-sm mt-1">Lisensi 12 bulan · aktivasi hingga {{ $catalog['max_activations'] }} perangkat · berlaku s.d. <span id="expPrev">+365 hari</span> setelah pembayaran</p>
    <div class="mt-3 text-3xl font-extrabold">Rp {{ number_format($catalog['price'], 0, ',', '.') }}</div>

    @if ($errors->any())
      <div class="mt-4 bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 text-sm">
        {{ implode(' ', $errors->all()) }}
      </div>
    @endif

    <form method="POST" action="{{ route('checkout.store', $slug) }}" class="mt-6 space-y-4">
      @csrf
      <div>
        <label class="text-sm font-semibold">Nama lengkap</label>
        <input name="customer_name" required value="{{ old('customer_name') }}" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="Nama Anda">
      </div>
      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-semibold">Email (lisensi dikirim ke sini)</label>
          <input name="customer_email" type="email" required value="{{ old('customer_email') }}" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="nama@email.com">
        </div>
        <div>
          <label class="text-sm font-semibold">No. HP / WhatsApp</label>
          <input name="customer_phone" required value="{{ old('customer_phone') }}" class="mt-1 w-full border rounded-lg px-3 py-2" placeholder="0812xxxxxxx">
        </div>
      </div>

      <div>
        <label class="text-sm font-semibold">Metode pembayaran</label>
        <div class="mt-2 grid gap-2">
          @foreach ($channels as $ch)
            <label class="flex items-center gap-3 border rounded-lg px-3 py-2 cursor-pointer hover:border-black">
              <input type="radio" name="method" value="{{ $ch['code'] }}" required class="accent-black">
              @if (!empty($ch['icon_url']))<img src="{{ $ch['icon_url'] }}" alt="" class="w-8 h-8 object-contain">@endif
              <span class="font-medium">{{ $ch['name'] }}</span>
              <span class="ml-auto text-xs text-gray-400">{{ $ch['group'] ?? '' }}</span>
            </label>
          @endforeach
        </div>
      </div>

      <button class="w-full bg-black text-white font-bold rounded-lg py-3 hover:bg-gray-800">
        Bayar Rp {{ number_format($catalog['price'], 0, ',', '.') }}
      </button>
      <p class="text-xs text-gray-400 text-center">Pembayaran aman via Tripay · lisensi terbit otomatis setelah lunas</p>
    </form>
  </div>
</div>
</body>
</html>

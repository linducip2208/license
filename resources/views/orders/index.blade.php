@extends('layouts.app')
@section('title', 'Orders')
@section('header', 'Orders')
@section('content')
<div class="flex items-center justify-between mb-4">
  <h2 class="text-xl font-bold">Orders Masuk</h2>
  <span class="text-sm text-gray-500">Verifikasi mutasi → Tandai Lunas → lisensi + email otomatis</span>
</div>

@if (session('ok'))
  <div class="mb-3 bg-green-50 border border-green-200 text-green-800 rounded-lg p-3 text-sm">{{ session('ok') }}</div>
@endif

<div class="bg-white border rounded-xl overflow-x-auto">
<table class="w-full text-sm">
  <thead><tr class="text-left text-gray-500 border-b">
    <th class="p-3">Ref</th><th class="p-3">Pembeli</th><th class="p-3">Produk</th>
    <th class="p-3">Nominal</th><th class="p-3">Metode</th><th class="p-3">Status</th>
    <th class="p-3">Lisensi</th><th class="p-3"></th>
  </tr></thead>
  <tbody>
  @forelse ($orders as $o)
    <tr class="border-b last:border-0">
      <td class="p-3 font-mono text-xs">{{ $o->merchant_ref }}</td>
      <td class="p-3">{{ $o->customer_name }}<div class="text-xs text-gray-400">{{ $o->customer_email }} · {{ $o->customer_phone }}</div></td>
      <td class="p-3">{{ $o->product->name }}</td>
      <td class="p-3 font-semibold">Rp {{ number_format($o->amount, 0, ',', '.') }}</td>
      <td class="p-3 text-xs">{{ $o->payment_name ?? $o->payment_method }}</td>
      <td class="p-3">
        @if ($o->isPaid())
          <span class="text-xs font-bold px-2 py-1 rounded-full bg-green-100 text-green-800">PAID</span>
        @else
          <span class="text-xs font-bold px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">{{ $o->status }}</span>
        @endif
      </td>
      <td class="p-3 font-mono text-xs">{{ $o->license?->code ?? '—' }}</td>
      <td class="p-3">
        @unless ($o->isPaid())
          <form method="POST" action="{{ route('orders.fulfill', $o) }}" onsubmit="return confirm('Mutasi Rp {{ number_format($o->amount, 0, ',', '.') }} sudah masuk?')">
            @csrf
            <button class="bg-neutral-900 text-white text-xs font-semibold rounded-lg px-3 py-2">Tandai Lunas</button>
          </form>
        @endunless
      </td>
    </tr>
  @empty
    <tr><td colspan="8" class="p-6 text-center text-gray-400">Belum ada order.</td></tr>
  @endforelse
  </tbody>
</table>
</div>
<div class="mt-3">{{ $orders->links() }}</div>
@endsection

@extends('layouts.app')
@section('title', $license->code)
@section('header', 'Licenses')
@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
  <h2 class="text-xl font-bold mono-chip">{{ $license->code }}</h2>
  <div class="flex gap-2 text-sm">
    <a href="{{ route('licenses.edit', $license) }}" class="border border-gray-300 rounded-lg px-3 py-1.5"><i class="fa-solid fa-pen mr-1"></i>Edit</a>
    @if($license->status==='active')
    <form method="POST" action="{{ route('licenses.suspend', $license) }}">@csrf<button class="border border-gray-300 rounded-lg px-3 py-1.5"><i class="fa-solid fa-pause mr-1"></i>Suspend</button></form>
    @else
    <form method="POST" action="{{ route('licenses.activate', $license) }}">@csrf<button class="border border-gray-300 rounded-lg px-3 py-1.5"><i class="fa-solid fa-play mr-1"></i>Activate</button></form>
    @endif
    <form method="POST" action="{{ route('licenses.regenerate', $license) }}" onsubmit="return confirm('Buat kode baru? Aktivasi lama dicabut.')">@csrf<button class="border border-gray-300 rounded-lg px-3 py-1.5"><i class="fa-solid fa-rotate mr-1"></i>Regenerate</button></form>
    <form method="POST" action="{{ route('licenses.extend', $license) }}" onsubmit="const d=prompt('Perpanjang berapa hari?','30'); if(!d) return false; this.days.value=d;"><input type="hidden" name="days" value="30">@csrf<button class="border border-gray-300 rounded-lg px-3 py-1.5"><i class="fa-solid fa-calendar-plus mr-1"></i>Renew</button></form>
  </div>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div class="border border-gray-200 rounded-xl p-5 text-sm space-y-2">
    <div><span class="text-gray-500">Customer:</span> <b>{{ $license->customer_name }}</b> <span class="text-gray-500">{{ $license->customer_email }}</span></div>
    <div><span class="text-gray-500">Product:</span> {{ $license->product->name ?? '-' }}</div>
    <div><span class="text-gray-500">Status:</span> {{ $license->effective_status }} ({{ $license->status }})</div>
    <div><span class="text-gray-500">Activations:</span> {{ $license->activations->where('revoked', false)->count() }} / {{ $license->max_activations }}</div>
    <div><span class="text-gray-500">Expires:</span> {{ $license->expires_on?->format('d/m/Y') ?? 'Lifetime' }}</div>
    <div><span class="text-gray-500">Owner:</span> {{ $license->owner->name ?? '-' }}</div>
    @if($license->notes)<div><span class="text-gray-500">Notes:</span> {{ $license->notes }}</div>@endif
  </div>
  <div class="border border-gray-200 rounded-xl p-5">
    <h3 class="font-semibold mb-2 text-sm">Activations (HWID)</h3>
    @forelse($license->activations as $a)
    <div class="flex items-center justify-between text-sm border-b border-gray-100 py-1.5">
      <div><code class="mono-chip text-xs">{{ Str::limit($a->hwid, 28) }}</code><div class="text-xs text-gray-500">{{ $a->label ?? '-' }} · {{ $a->last_seen_at?->diffForHumans() ?? 'never' }} {{ $a->revoked ? '· revoked' : '' }}</div></div>
      @if(!$a->revoked)
      <form method="POST" action="{{ route('licenses.activations.revoke', [$license, $a->id]) }}">@csrf<button class="text-xs border border-gray-200 rounded-lg px-2 py-1">Cabut</button></form>
      @endif
    </div>
    @empty
    <p class="text-sm text-gray-400">Belum ada aktivasi.</p>
    @endforelse
  </div>
</div>
@endsection

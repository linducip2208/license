@extends('layouts.app')
@section('title', 'Dashboard')
@section('header', 'Dashboard')
@section('content')
<h2 class="text-xl font-bold mb-4">Dashboard</h2>
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
  <div class="border border-gray-200 rounded-xl p-4 text-center"><div class="text-3xl font-bold">{{ $stats['total'] }}</div><div class="text-sm text-gray-500">Total Licenses</div></div>
  <div class="border border-gray-200 rounded-xl p-4 text-center"><div class="text-3xl font-bold text-green-600">{{ $stats['active'] }}</div><div class="text-sm text-gray-500">Active</div></div>
  <div class="border border-gray-200 rounded-xl p-4 text-center"><div class="text-3xl font-bold text-amber-600">{{ $stats['expiring'] }}</div><div class="text-sm text-gray-500">Expiring ≤ 30d</div></div>
  <div class="border border-gray-200 rounded-xl p-4 text-center"><div class="text-3xl font-bold text-gray-400">{{ $stats['suspended'] }}</div><div class="text-sm text-gray-500">Suspended</div></div>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div class="border border-gray-200 rounded-xl p-4">
    <h3 class="font-semibold text-sm mb-2">Lisensi Terbaru</h3>
    @forelse($recentLicenses as $l)
    <div class="flex justify-between text-sm border-b border-gray-100 py-1.5">
      <a href="{{ route('licenses.show', $l) }}" class="mono-chip text-xs">{{ $l->code }}</a>
      <span class="text-gray-500">{{ Str::limit($l->customer_name, 18) }}</span>
    </div>
    @empty<p class="text-sm text-gray-400">Kosong.</p>@endforelse
  </div>
  <div class="border border-gray-200 rounded-xl p-4">
    <h3 class="font-semibold text-sm mb-2">Aktivitas Terakhir</h3>
    @forelse($recentLogs as $g)
    <div class="text-sm border-b border-gray-100 py-1.5"><b>{{ $g->action }}</b> <span class="text-gray-500">{{ $g->license->code ?? '-' }} · {{ $g->user->name ?? 'system' }} · {{ $g->created_at->diffForHumans() }}</span></div>
    @empty<p class="text-sm text-gray-400">Kosong.</p>@endforelse
  </div>
</div>
@endsection

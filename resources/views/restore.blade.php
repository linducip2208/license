@extends('layouts.app')
@section('title', 'Restore license')
@section('header', 'Restore license')
@section('content')
<h2 class="text-xl font-bold mb-4">Restore license</h2>
<div class="max-w-xl border border-gray-200 rounded-xl p-5 mb-4">
<form method="GET" action="{{ route('restore') }}">
  <label class="text-sm font-medium">Cari by kode / email customer</label>
  <div class="flex gap-2 mt-1"><input type="text" name="q" value="{{ request('q') }}" class="flex-1 rounded-lg border border-gray-300 px-3 py-2"><button class="bg-neutral-900 text-white rounded-lg px-4 py-2 text-sm font-semibold">Cari</button></div>
</form>
</div>
@if(isset($found) && $found)
<div class="max-w-xl border border-gray-200 rounded-xl p-5 text-sm space-y-1">
  <div class="mono-chip font-bold">{{ $found->code }}</div>
  <div>{{ $found->customer_name }} · {{ $found->customer_email }}</div>
  <div>Status: {{ $found->effective_status }} · {{ $found->activations->where('revoked', false)->count() }}/{{ $found->max_activations }} · Exp: {{ $found->expires_on?->format('d/m/Y') ?? 'Lifetime' }}</div>
  <a href="{{ route('licenses.show', $found) }}" class="inline-block mt-2 text-sm border border-gray-300 rounded-lg px-3 py-1.5">Buka detail</a>
</div>
@endif
@endsection

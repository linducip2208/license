@extends('layouts.app')
@section('title', 'Registered Licenses')
@section('header', 'Licenses')
@section('content')
<div class="flex items-center justify-between mb-4">
  <h2 class="text-xl font-bold">Registered Licenses</h2>
  <a href="{{ route('licenses.create') }}" class="bg-neutral-900 text-white text-sm font-semibold rounded-lg px-4 py-2"><i class="fa-solid fa-plus mr-1"></i> Create New License</a>
</div>

<form method="GET" class="flex flex-wrap gap-2 mb-3">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by name, key or email..." class="flex-1 min-w-[220px] rounded-lg border border-gray-300 px-3 py-2 text-sm">
  <select name="product" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
    <option value="">All Products</option>
    @foreach($products as $p)<option value="{{ $p->id }}" @selected(request('product')==$p->id)>{{ $p->name }}</option>@endforeach
  </select>
  <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
  <input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
  <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
    <option value="all">All statuses</option>
    @foreach(['active','suspended','expired'] as $st)<option value="{{ $st }}" @selected(request('status')==$st)>{{ ucfirst($st) }}</option>@endforeach
  </select>
  <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm"><i class="fa-solid fa-magnifying-glass mr-1"></i> Search</button>
</form>

<div class="flex items-center gap-2 mb-3 text-sm">
  <button type="button" onclick="document.getElementById('bulkForm').requestSubmit()" class="rounded-lg border border-gray-300 px-3 py-1.5 text-gray-400"><i class="fa-solid fa-eye-slash"></i></button>
  <a href="{{ route('licenses.csv', request()->query()) }}" class="rounded-lg border border-gray-300 px-3 py-1.5"><i class="fa-solid fa-file-csv mr-1"></i> CSV</a>
  <a href="{{ route('licenses.excel', request()->query()) }}" class="rounded-lg border border-gray-300 px-3 py-1.5"><i class="fa-solid fa-file-excel mr-1"></i> Excel</a>
  <div class="relative">
    <button type="button" onclick="document.getElementById('bulkMenu').classList.toggle('hidden')" class="rounded-lg border border-gray-300 px-3 py-1.5"><i class="fa-solid fa-bolt mr-1"></i> Actions <i class="fa-solid fa-chevron-down text-xs"></i></button>
    <div id="bulkMenu" class="hidden absolute z-10 bg-white border border-gray-200 rounded-lg shadow mt-1 py-1 w-40">
      <button type="submit" form="bulkForm" name="action" value="suspend" class="block w-full text-left px-4 py-1.5 hover:bg-gray-100">Suspend selected</button>
      <button type="submit" form="bulkForm" name="action" value="activate" class="block w-full text-left px-4 py-1.5 hover:bg-gray-100">Activate selected</button>
      <button type="submit" form="bulkForm" name="action" value="delete" class="block w-full text-left px-4 py-1.5 hover:bg-gray-100 text-red-600" onclick="return confirm('Hapus yang dipilih?')">Delete selected</button>
    </div>
  </div>
</div>

<p class="text-sm text-gray-500 mb-2"><span class="inline-block bg-gray-100 rounded-full px-2 py-0.5 font-semibold text-gray-700">{{ $total }}</span> licenses in total</p>

<form id="bulkForm" method="POST" action="{{ route('licenses.bulk') }}">@csrf
<div class="border border-gray-200 rounded-xl overflow-hidden">
<table class="w-full text-sm">
  <thead><tr class="text-left text-xs text-gray-500 border-b border-gray-200">
    <th class="p-3"><input type="checkbox" onclick="document.querySelectorAll('.rowcheck').forEach(c=>c.checked=this.checked)"></th>
    <th class="p-3">CODE</th><th class="p-3">CUSTOMER</th><th class="p-3">PRODUCT</th><th class="p-3">STATUS</th><th class="p-3">ACTIVATIONS</th><th class="p-3">EXPIRES ON</th><th class="p-3">ACTIONS</th>
  </tr></thead>
  <tbody>
  @forelse($licenses as $l)
  <tr class="border-b border-gray-100 hover:bg-gray-50">
    <td class="p-3"><input type="checkbox" name="ids[]" value="{{ $l->id }}" class="rowcheck"></td>
    <td class="p-3"><a href="{{ route('licenses.show', $l) }}" class="mono-chip bg-gray-100 rounded px-2 py-0.5 text-xs">{{ $l->code }}</a></td>
    <td class="p-3"><div class="font-medium">{{ Str::limit($l->customer_name, 22) }}</div><div class="text-xs text-gray-500">{{ Str::limit($l->customer_email ?? '-', 24) }}</div></td>
    <td class="p-3">{{ $l->product->name ?? '-' }}</td>
    <td class="p-3"><span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs {{ $l->effective_status==='active' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}"><span class="w-1.5 h-1.5 rounded-full {{ $l->effective_status==='active' ? 'bg-green-500' : 'bg-gray-400' }}"></span> {{ $l->effective_status }}</span></td>
    <td class="p-3">{{ $l->active_count }} / {{ $l->max_activations }}</td>
    <td class="p-3">{{ $l->expires_on?->format('d/m/Y') ?? '-' }}</td>
    <td class="p-3 whitespace-nowrap">
      <form method="POST" action="{{ route('licenses.extend', $l) }}" class="inline" onsubmit="const d=prompt('Perpanjang berapa hari?','30'); if(!d) return false; this.days.value=d;"><input type="hidden" name="days" value="30">@csrf<button title="Renew" class="border border-gray-200 rounded-lg px-2 py-1 text-xs mr-1"><i class="fa-solid fa-rotate mr-1"></i>Renew</button></form>
      <a href="{{ route('licenses.edit', $l) }}" title="Edit" class="border border-gray-200 rounded-lg px-2 py-1 text-xs mr-1"><i class="fa-solid fa-pen"></i></a>
      @if($l->status==='active')
      <form method="POST" action="{{ route('licenses.suspend', $l) }}" class="inline">@csrf<button title="Suspend" class="border border-gray-200 rounded-lg px-2 py-1 text-xs mr-1"><i class="fa-solid fa-pause"></i></button></form>
      @else
      <form method="POST" action="{{ route('licenses.activate', $l) }}" class="inline">@csrf<button title="Activate" class="border border-gray-200 rounded-lg px-2 py-1 text-xs mr-1"><i class="fa-solid fa-play"></i></button></form>
      @endif
      <form method="POST" action="{{ route('licenses.regenerate', $l) }}" class="inline" onsubmit="return confirm('Buat kode baru? Aktivasi lama dicabut.')">@csrf<button title="Regenerate" class="border border-gray-200 rounded-lg px-2 py-1 text-xs mr-1"><i class="fa-solid fa-rotate"></i></button></form>
      <form method="POST" action="{{ route('licenses.destroy', $l) }}" class="inline" onsubmit="return confirm('Hapus lisensi ini?')">@csrf @method('DELETE')<button title="Delete" class="border border-gray-200 rounded-lg px-2 py-1 text-xs"><i class="fa-solid fa-trash"></i></button></form>
    </td>
  </tr>
  @empty
  <tr><td colspan="8" class="p-6 text-center text-gray-400">Belum ada lisensi.</td></tr>
  @endforelse
  </tbody>
</table>
</div>
</form>

<div class="mt-3 text-sm text-gray-500 flex items-center gap-2 flex-wrap">
  <span>Page {{ $licenses->currentPage() }} of {{ $licenses->lastPage() }} ({{ $total }} licenses in total)</span>
  <span>Go to page:</span>
  {{ $licenses->links() }}
</div>
@endsection

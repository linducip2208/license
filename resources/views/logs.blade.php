@extends('layouts.app')
@section('title', 'Logs')
@section('header', 'Logs')
@section('content')
<h2 class="text-xl font-bold mb-4">Logs</h2>
<div class="border border-gray-200 rounded-xl overflow-hidden">
<table class="w-full text-sm"><thead><tr class="text-left text-xs text-gray-500 border-b border-gray-200"><th class="p-3">WAKTU</th><th class="p-3">USER</th><th class="p-3">AKSI</th><th class="p-3">LICENSE</th><th class="p-3">DETAIL</th><th class="p-3">IP</th></tr></thead>
<tbody>
@forelse($logs as $g)
<tr class="border-b border-gray-100"><td class="p-3 whitespace-nowrap">{{ $g->created_at->format('d/m/Y H:i') }}</td><td class="p-3">{{ $g->user->name ?? 'system' }}</td><td class="p-3 font-medium">{{ $g->action }}</td><td class="p-3 mono-chip text-xs">{{ $g->license->code ?? '-' }}</td><td class="p-3 text-gray-500">{{ Str::limit($g->detail ?? '-', 60) }}</td><td class="p-3 text-gray-400">{{ $g->ip }}</td></tr>
@empty<tr><td colspan="6" class="p-6 text-center text-gray-400">Kosong.</td></tr>@endforelse
</tbody></table>
</div>
<div class="mt-3">{{ $logs->links() }}</div>
@endsection

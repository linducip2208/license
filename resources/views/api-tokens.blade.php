@extends('layouts.app')
@section('title', 'API / MCP Tokens')
@section('header', 'API / MCP')
@section('content')
<h2 class="text-xl font-bold mb-1">API / MCP Tokens</h2>
<p class="text-sm text-gray-500 mb-4">Token untuk integrasi aplikasi &amp; endpoint verifikasi <code class="mono-chip bg-gray-100 rounded px-1">POST /api/v1/licenses/verify</code> { code, hwid, label?, app_version? } dan <code class="mono-chip bg-gray-100 rounded px-1">POST /api/v1/licenses/release</code> (Bearer token).</p>
<div class="max-w-xl border border-gray-200 rounded-xl p-5 mb-4">
<form method="POST" action="{{ route('tokens.store') }}">@csrf
  <label class="text-sm font-medium">Nama token</label>
  <div class="flex gap-2 mt-1"><input type="text" name="name" required placeholder="mis. WASender-App" class="flex-1 rounded-lg border border-gray-300 px-3 py-2"><button class="bg-neutral-900 text-white rounded-lg px-4 py-2 text-sm font-semibold">Buat</button></div>
</form>
</div>
<div class="max-w-xl border border-gray-200 rounded-xl overflow-hidden">
<table class="w-full text-sm"><tbody>
@forelse($tokens as $t)
<tr class="border-b border-gray-100"><td class="p-3">{{ $t->name }}<div class="text-xs text-gray-400">dipakai terakhir: {{ $t->last_used_at?->diffForHumans() ?? 'belum' }}</div></td>
<td class="p-3 text-right"><form method="POST" action="{{ route('tokens.destroy', $t->id) }}">@csrf @method('DELETE')<button class="text-xs border border-gray-200 rounded-lg px-2 py-1">Hapus</button></form></td></tr>
@empty<tr><td class="p-4 text-gray-400 text-sm">Belum ada token.</td></tr>@endforelse
</tbody></table>
</div>
@endsection

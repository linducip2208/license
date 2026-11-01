@extends('layouts.app')
@section('title', 'Settings')
@section('header', 'Settings')
@section('content')
<h2 class="text-xl font-bold mb-1">Pengaturan Email (SMTP)</h2>
<p class="text-sm text-gray-500 mb-4">Menggantikan <span class="font-mono">MAIL_*</span> di .env. Password disimpan terenkripsi &amp; tidak pernah ditampilkan.</p>

@if (session('ok'))
  <div class="mb-3 bg-green-50 border border-green-200 text-green-800 rounded-lg p-3 text-sm">{{ session('ok') }}</div>
@endif
@if ($errors->any())
  <div class="mb-3 bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 text-sm">{{ implode(' ', $errors->all()) }}</div>
@endif

<form method="POST" action="{{ route('settings.update') }}" class="bg-white border rounded-xl p-5 grid md:grid-cols-2 gap-4 max-w-3xl">
  @csrf
  <div>
    <label class="text-sm font-semibold">SMTP Host</label>
    <input name="mail_host" required value="{{ old('mail_host', $mail_host) }}" placeholder="smtp.gmail.com" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
  </div>
  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="text-sm font-semibold">Port</label>
      <input name="mail_port" required type="number" value="{{ old('mail_port', $mail_port) }}" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
    </div>
    <div>
      <label class="text-sm font-semibold">Enkripsi</label>
      <select name="mail_encryption" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
        <option value="tls" {{ $mail_encryption === 'tls' ? 'selected' : '' }}>TLS</option>
        <option value="ssl" {{ $mail_encryption === 'ssl' ? 'selected' : '' }}>SSL</option>
        <option value="none" {{ $mail_encryption === 'none' ? 'selected' : '' }}>Tanpa</option>
      </select>
    </div>
  </div>
  <div>
    <label class="text-sm font-semibold">Username</label>
    <input name="mail_username" required value="{{ old('mail_username', $mail_username) }}" placeholder="akun.kamu@gmail.com" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
  </div>
  <div>
    <label class="text-sm font-semibold">Password / App Password
      @if ($has_password)<span class="ml-1 text-xs font-bold px-2 py-0.5 rounded-full bg-green-100 text-green-800">TERSIMPAN AMAN</span>@endif
    </label>
    <input name="mail_password" type="password" autocomplete="new-password" placeholder="•••••••• — kosongkan bila tidak diubah" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
  </div>
  <div>
    <label class="text-sm font-semibold">From Address</label>
    <input name="mail_from_address" required type="email" value="{{ old('mail_from_address', $mail_from_address) }}" placeholder="no-reply@pandusolusi.com" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
  </div>
  <div>
    <label class="text-sm font-semibold">From Name</label>
    <input name="mail_from_name" value="{{ old('mail_from_name', $mail_from_name) }}" placeholder="PanduSolusi" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
  </div>
  <div class="md:col-span-2">
    <button class="bg-neutral-900 text-white text-sm font-semibold rounded-lg px-5 py-2">Simpan</button>
  </div>
</form>

<form method="POST" action="{{ route('settings.test') }}" class="bg-white border rounded-xl p-5 mt-4 max-w-3xl flex flex-wrap items-end gap-3">
  @csrf
  <div class="flex-1 min-w-[220px]">
    <label class="text-sm font-semibold">Kirim email tes ke</label>
    <input name="email" required type="email" placeholder="kamu@email.com" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
  </div>
  <button class="border text-sm font-semibold rounded-lg px-5 py-2">Kirim Tes</button>
</form>
@endsection

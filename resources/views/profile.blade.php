@extends('layouts.app')
@section('title', 'Profile')
@section('header', 'Profile')
@section('content')
<h2 class="text-xl font-bold mb-4">Profile</h2>
<div class="max-w-xl border border-gray-200 rounded-xl p-5">
<form method="POST" action="{{ route('profile.update') }}">@csrf
  <label class="text-sm font-medium">Nama</label>
  <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required class="mt-1 mb-3 w-full rounded-lg border border-gray-300 px-3 py-2">
  <label class="text-sm font-medium">Email</label>
  <input type="email" value="{{ auth()->user()->email }}" disabled class="mt-1 mb-3 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-gray-500">
  <label class="text-sm font-medium">Password baru (kosongkan bila tidak ganti)</label>
  <input type="password" name="password" class="mt-1 mb-1 w-full rounded-lg border border-gray-300 px-3 py-2">
  <input type="password" name="password_confirmation" placeholder="Konfirmasi password" class="mb-4 w-full rounded-lg border border-gray-300 px-3 py-2">
  <button class="bg-neutral-900 text-white rounded-lg px-5 py-2 font-semibold text-sm">Simpan</button>
</form>
</div>
@endsection

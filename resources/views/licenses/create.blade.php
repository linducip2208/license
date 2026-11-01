@extends('layouts.app')
@section('title', 'Create New License')
@section('header', 'Licenses')
@section('content')
<h2 class="text-xl font-bold mb-4">Create New License</h2>
<div class="max-w-xl bg-white border border-gray-200 rounded-xl p-6">
<form method="POST" action="{{ route('licenses.store') }}">@csrf
  <label class="text-sm font-medium">Customer name</label>
  <input type="text" name="customer_name" value="{{ old('customer_name') }}" required class="mt-1 mb-3 w-full rounded-lg border border-gray-300 px-3 py-2">
  <label class="text-sm font-medium">Customer email</label>
  <input type="email" name="customer_email" value="{{ old('customer_email') }}" class="mt-1 mb-3 w-full rounded-lg border border-gray-300 px-3 py-2">
  <div class="grid grid-cols-2 gap-3">
    <div><label class="text-sm font-medium">Product</label>
      <select name="product_id" class="mt-1 mb-3 w-full rounded-lg border border-gray-300 px-3 py-2">
        @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
      </select></div>
    <div><label class="text-sm font-medium">Max activations</label>
      <input type="number" name="max_activations" value="{{ old('max_activations', 1) }}" min="1" max="100" class="mt-1 mb-3 w-full rounded-lg border border-gray-300 px-3 py-2"></div>
  </div>
  <label class="text-sm font-medium">Expires on (kosongkan = lifetime)</label>
  <input type="date" name="expires_on" value="{{ old('expires_on') }}" class="mt-1 mb-3 w-full rounded-lg border border-gray-300 px-3 py-2">
  <label class="text-sm font-medium">Notes</label>
  <textarea name="notes" rows="3" class="mt-1 mb-4 w-full rounded-lg border border-gray-300 px-3 py-2">{{ old('notes') }}</textarea>
  <button class="bg-neutral-900 text-white rounded-lg px-5 py-2 font-semibold">Generate License</button>
  <a href="{{ route('licenses.index') }}" class="ml-2 text-sm text-gray-500">Batal</a>
</form>
</div>
@endsection

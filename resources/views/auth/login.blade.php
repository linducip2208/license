<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Control Panel</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
<div class="w-full max-w-sm bg-white rounded-2xl shadow p-8">
  <div class="flex items-center gap-2 mb-6">
    <span class="w-9 h-9 rounded-lg bg-black text-white flex items-center justify-center"><i class="fa-solid fa-key"></i></span>
    <span class="font-bold text-lg">Control Panel</span>
  </div>
  @if($errors->any())<div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-2 text-sm">{{ $errors->first() }}</div>@endif
  <form method="POST" action="{{ route('login') }}">@csrf
    <label class="text-sm font-medium">Email</label>
    <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 mb-3 w-full rounded-lg border border-gray-300 px-3 py-2">
    <label class="text-sm font-medium">Password</label>
    <input type="password" name="password" required class="mt-1 mb-4 w-full rounded-lg border border-gray-300 px-3 py-2">
    <button class="w-full bg-black text-white rounded-lg py-2 font-semibold">Login</button>
  </form>
</div>
</body>
</html>

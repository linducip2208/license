<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Licenses') — Control Panel</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
  body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
  .mono-chip { font-family: ui-monospace, monospace; }
  .sidebar-link.active { background: #f3f4f6; font-weight: 600; }
</style>
</head>
<body class="bg-white text-gray-800">
<div class="flex min-h-screen">
  <!-- Sidebar -->
  <aside class="w-56 shrink-0 border-r border-gray-200 flex flex-col">
    <div class="flex items-center gap-2 px-4 py-4">
      <span class="w-8 h-8 rounded-lg bg-black text-white flex items-center justify-center"><i class="fa-solid fa-key"></i></span>
      <span class="font-bold">Control Panel</span>
    </div>
    <nav class="px-3 space-y-1 text-sm">
      <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }} flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100"><i class="fa-solid fa-chart-line w-4"></i> Dashboard</a>
      <a href="{{ route('licenses.index') }}" class="sidebar-link {{ request()->routeIs('licenses.*') ? 'active' : '' }} flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100"><i class="fa-solid fa-key w-4"></i> Licenses</a>
      @if (auth()->user()?->isAdmin())
      <a href="{{ route('orders.index') }}" class="sidebar-link {{ request()->routeIs('orders.*') ? 'active' : '' }} flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100"><i class="fa-solid fa-cart-shopping w-4"></i> Orders</a>
      <a href="{{ route('settings.index') }}" class="sidebar-link {{ request()->routeIs('settings.*') ? 'active' : '' }} flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100"><i class="fa-solid fa-gear w-4"></i> Settings</a>
      @endif
      <div class="pt-3 px-3 text-[11px] tracking-wider text-gray-400 font-semibold">SYSTEM</div>
      <a href="{{ route('tokens.index') }}" class="sidebar-link {{ request()->routeIs('tokens.*') ? 'active' : '' }} flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100"><i class="fa-solid fa-lock w-4"></i> API / MCP</a>
      <a href="{{ route('restore') }}" class="sidebar-link {{ request()->routeIs('restore') ? 'active' : '' }} flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100"><i class="fa-solid fa-rotate-left w-4"></i> Restore license</a>
      <a href="{{ route('downloads') }}" class="sidebar-link {{ request()->routeIs('downloads') ? 'active' : '' }} flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100"><i class="fa-solid fa-download w-4"></i> Downloads</a>
      <a href="{{ route('logs') }}" class="sidebar-link {{ request()->routeIs('logs') ? 'active' : '' }} flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100"><i class="fa-solid fa-file-lines w-4"></i> Logs</a>
      <a href="{{ route('profile') }}" class="sidebar-link {{ request()->routeIs('profile*') ? 'active' : '' }} flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100"><i class="fa-solid fa-user w-4"></i> Profile</a>
      <a href="{{ route('support') }}" class="sidebar-link {{ request()->routeIs('support') ? 'active' : '' }} flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100"><i class="fa-solid fa-headset w-4"></i> Support</a>
    </nav>
    <div class="mt-auto p-4 border-t border-gray-200 flex items-center gap-2 text-sm">
      <span class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center"><i class="fa-solid fa-user text-gray-500"></i></span>
      <div><div class="font-semibold">{{ auth()->user()->name }}</div><div class="text-xs text-gray-500 capitalize">{{ auth()->user()->role }}</div></div>
    </div>
  </aside>

  <!-- Main -->
  <div class="flex-1 min-w-0">
    <header class="flex items-center justify-between border-b border-gray-200 px-6 py-3">
      <div class="flex items-center gap-3">
        <button class="w-8 h-8 rounded-lg border border-gray-200 flex items-center justify-center text-gray-500"><i class="fa-solid fa-angles-left text-xs"></i></button>
        <h1 class="font-semibold text-lg">@yield('header', 'Licenses')</h1>
      </div>
      <div class="flex items-center gap-2">
        <button class="w-9 h-9 rounded-lg border border-gray-200 text-sm">🇺🇸</button>
        <button class="w-9 h-9 rounded-lg border border-gray-200 text-gray-500" onclick="document.documentElement.classList.toggle('dark')"><i class="fa-solid fa-moon"></i></button>
        <form method="POST" action="{{ route('logout') }}">@csrf
          <button class="h-9 px-3 rounded-lg border border-gray-200 text-red-500 text-sm"><i class="fa-solid fa-arrow-right-from-bracket mr-1"></i> Logout</button>
        </form>
      </div>
    </header>
    <main class="p-6">
      @if(session('ok'))<div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 px-4 py-2 text-sm">{{ session('ok') }}</div>@endif
      @if(session('err'))<div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-2 text-sm">{{ session('err') }}</div>@endif
      @if($errors->any())<div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-2 text-sm">{{ $errors->first() }}</div>@endif
      @if(session('plainToken'))<div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-4 py-2 text-sm">Token: <code class="font-mono">{{ session('plainToken') }}</code></div>@endif
      @yield('content')
    </main>
    <footer class="text-center text-xs text-gray-400 py-4">© {{ date('Y') }} Control Panel - License Management System</footer>
  </div>
</div>
@stack('scripts')
</body>
</html>

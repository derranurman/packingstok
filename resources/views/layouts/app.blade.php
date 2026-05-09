<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Packing Stok')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen">
    @auth
    <nav class="bg-white border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap items-center gap-4">
            <a href="{{ url('/') }}" class="font-bold text-lg text-emerald-700">Packing Stok</a>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="text-sm hover:text-emerald-700 {{ request()->routeIs('admin.dashboard') ? 'text-emerald-700 font-semibold' : '' }}">Dashboard</a>
                <a href="{{ route('admin.products.index') }}" class="text-sm hover:text-emerald-700 {{ request()->routeIs('admin.products.*') ? 'text-emerald-700 font-semibold' : '' }}">Produk</a>
                <a href="{{ route('admin.orders.index') }}" class="text-sm hover:text-emerald-700 {{ request()->routeIs('admin.orders.index') || request()->routeIs('admin.orders.show') ? 'text-emerald-700 font-semibold' : '' }}">Order</a>
                <a href="{{ route('admin.orders.import.form') }}" class="text-sm hover:text-emerald-700 {{ request()->routeIs('admin.orders.import*') ? 'text-emerald-700 font-semibold' : '' }}">Import TikTok</a>
                <a href="{{ route('admin.watchfolder.index') }}" class="text-sm hover:text-emerald-700 {{ request()->routeIs('admin.watchfolder.*') ? 'text-emerald-700 font-semibold' : '' }}">Watch Folder</a>
                <a href="{{ route('admin.mappings.index') }}" class="text-sm hover:text-emerald-700 {{ request()->routeIs('admin.mappings.*') ? 'text-emerald-700 font-semibold' : '' }}">Mapping SKU</a>
                <a href="{{ route('admin.users.index') }}" class="text-sm hover:text-emerald-700 {{ request()->routeIs('admin.users.*') ? 'text-emerald-700 font-semibold' : '' }}">User</a>
            @endif
            <a href="{{ route('packing.index') }}" class="text-sm hover:text-emerald-700 {{ request()->routeIs('packing.*') ? 'text-emerald-700 font-semibold' : '' }}">Scan Packing</a>
            <div class="ml-auto flex items-center gap-3 text-sm">
                <span class="text-slate-600">{{ auth()->user()->name }} <span class="text-xs px-2 py-0.5 rounded bg-slate-100">{{ auth()->user()->role }}</span></span>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="text-red-600 hover:underline">Logout</button>
                </form>
            </div>
        </div>
    </nav>
    @endauth

    <main class="max-w-7xl mx-auto px-4 py-6">
        @if(session('success'))
            <div class="mb-4 rounded bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-2">{{ session('success') }}</div>
        @endif
        @if($errors->any() && !isset($hideGlobalErrors))
            <div class="mb-4 rounded bg-red-50 border border-red-200 text-red-800 px-4 py-2">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>

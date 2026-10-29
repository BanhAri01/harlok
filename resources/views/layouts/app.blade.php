<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Harlok: peternak dan petani melapor stok serta harga lewat WhatsApp, pengepul menawar lewat web, dan harga pasar terbuka untuk semua.">
    <title>{{ isset($title) ? $title.' — ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%23163326'/%3E%3Cpath d='M9 8h9l7 7-9 9-7-7z' fill='%23F5B700'/%3E%3Ccircle cx='13' cy='12' r='1.8' fill='%23163326'/%3E%3C/svg%3E">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <a href="#isi" class="sr-only focus:not-sr-only focus:absolute focus:left-3 focus:top-3 focus:z-[2000] focus:rounded focus:bg-kuning focus:px-3 focus:py-2">Lewati ke isi</a>

    <header class="papan-tulis border-b border-white/10">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <svg width="30" height="30" viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="8" fill="#1F4433"/><path d="M9 8h9l7 7-9 9-7-7z" fill="#F5B700"/><circle cx="13" cy="12" r="1.8" fill="#163326"/></svg>
                <span class="font-display text-xl font-extrabold tracking-tight text-kapur">Harlok</span>
            </a>

            <nav class="ml-auto flex flex-wrap items-center gap-1 text-sm font-medium text-kapur/80" aria-label="Menu utama">
                <a href="{{ route('home') }}" @class(['rounded-md px-3 py-1.5 hover:bg-white/10 hover:text-kapur', 'bg-white/10 text-kapur' => request()->routeIs('home')])>Papan Harga</a>
                @if (config('lapor.simulator.enabled'))
                    <a href="{{ route('simulator.show') }}" @class(['rounded-md px-3 py-1.5 hover:bg-white/10 hover:text-kapur', 'bg-white/10 text-kapur' => request()->routeIs('simulator.*')])>Coba Chat</a>
                @endif
                <a href="{{ route('api.docs') }}" @class(['rounded-md px-3 py-1.5 hover:bg-white/10 hover:text-kapur', 'bg-white/10 text-kapur' => request()->routeIs('api.docs')])>Data & API</a>
                @auth
                    <a href="{{ route('buyer.dashboard') }}" @class(['rounded-md px-3 py-1.5 hover:bg-white/10 hover:text-kapur', 'bg-white/10 text-kapur' => request()->routeIs('buyer.dashboard')])>Cari Stok</a>
                    <a href="{{ route('buyer.offers') }}" @class(['rounded-md px-3 py-1.5 hover:bg-white/10 hover:text-kapur', 'bg-white/10 text-kapur' => request()->routeIs('buyer.offers')])>Tawaran Saya</a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" @class(['rounded-md px-3 py-1.5 hover:bg-white/10 hover:text-kapur', 'bg-white/10 text-kapur' => request()->routeIs('admin.*')])>Koordinator</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-md px-3 py-1.5 hover:bg-white/10 hover:text-kapur">Keluar</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="ml-1 rounded-md bg-kuning px-3 py-1.5 font-semibold text-tinta hover:bg-[#ffc81f]">Masuk Pengepul</a>
                @endauth
            </nav>
        </div>
    </header>

    @if (session('status'))
        <div class="border-b border-naik/20 bg-[#E3F3E8] text-naik" role="status">
            <div class="mx-auto max-w-7xl px-4 py-2.5 text-sm font-semibold">{{ session('status') }}</div>
        </div>
    @endif

    <main id="isi">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <footer class="mt-16 border-t border-garis">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-6 text-sm text-tinta/60">
            <p>Harlok — harga pasar yang adil, cukup lewat WhatsApp.</p>
            <p>Data harga berasal dari laporan peternak terverifikasi.</p>
        </div>
    </footer>
</body>
</html>

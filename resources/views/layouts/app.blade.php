<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Harlok: peternak dan petani melapor stok serta harga lewat WhatsApp, pengepul menawar lewat web, dan harga pasar terbuka untuk semua.">
    <title>{{ isset($title) ? $title.' | ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%231D4A34'/%3E%3Cpath d='M15 15h28l9 13-9 13H27l-10 9v-9h-2a3 3 0 0 1-3-3V18a3 3 0 0 1 3-3z' fill='%23F5B700'/%3E%3Ccircle cx='43' cy='28' r='3' fill='%231D4A34'/%3E%3C/svg%3E">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    <a href="#isi" class="sr-only focus:not-sr-only focus:absolute focus:left-3 focus:top-3 focus:z-[2000] focus:rounded focus:bg-kuning focus:px-3 focus:py-2">Lewati ke isi</a>

    <header class="border-b border-garis bg-kertas">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <svg width="32" height="32" viewBox="0 0 64 64" aria-hidden="true"><rect width="64" height="64" rx="14" fill="#1D4A34"/><path d="M15 15h28l9 13-9 13H27l-10 9v-9h-2a3 3 0 0 1-3-3V18a3 3 0 0 1 3-3z" fill="#F5B700"/><circle cx="43" cy="28" r="3" fill="#1D4A34"/><path d="M20 24h14M20 31h9" stroke="#1D4A34" stroke-width="3.2" stroke-linecap="round"/></svg>
                <span class="font-display text-[1.7rem] font-extrabold leading-none text-papan">Harlok</span>
            </a>

            <nav class="ml-auto flex flex-wrap items-center gap-1 text-[0.95rem]" aria-label="Menu utama">
                <a href="{{ route('home') }}" @class(['rounded-full px-3.5 py-1.5 text-tinta/75 hover:bg-daun hover:text-tinta', 'bg-daun font-bold text-papan' => request()->routeIs('home')])>Harga hari ini</a>
                @if (config('lapor.simulator.enabled'))
                    <a href="{{ route('simulator.show') }}" @class(['rounded-full px-3.5 py-1.5 text-tinta/75 hover:bg-daun hover:text-tinta', 'bg-daun font-bold text-papan' => request()->routeIs('simulator.*')])>Coba chat</a>
                @endif
                <a href="{{ route('api.docs') }}" @class(['rounded-full px-3.5 py-1.5 text-tinta/75 hover:bg-daun hover:text-tinta', 'bg-daun font-bold text-papan' => request()->routeIs('api.docs')])>Data terbuka</a>
                @auth
                    <a href="{{ route('buyer.dashboard') }}" @class(['rounded-full px-3.5 py-1.5 text-tinta/75 hover:bg-daun hover:text-tinta', 'bg-daun font-bold text-papan' => request()->routeIs('buyer.dashboard')])>Cari stok</a>
                    <a href="{{ route('buyer.offers') }}" @class(['rounded-full px-3.5 py-1.5 text-tinta/75 hover:bg-daun hover:text-tinta', 'bg-daun font-bold text-papan' => request()->routeIs('buyer.offers')])>Tawaran saya</a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" @class(['rounded-full px-3.5 py-1.5 text-tinta/75 hover:bg-daun hover:text-tinta', 'bg-daun font-bold text-papan' => request()->routeIs('admin.*')])>Koordinator</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-full px-3.5 py-1.5 text-tinta/75 hover:bg-daun hover:text-tinta">Keluar</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn-utama ml-2 px-4 py-1.5">Masuk</a>
                @endauth
            </nav>
        </div>
    </header>

    @if (session('status'))
        <div class="border-b border-naik/20 bg-[#E3F3E8] text-naik" role="status">
            <div class="mx-auto max-w-6xl px-4 py-2.5 text-[0.95rem] font-bold">{{ session('status') }}</div>
        </div>
    @endif

    <main id="isi" class="flex-1">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <footer @class(['bg-papan text-kapur/75', 'mt-20' => ! View::hasSection('tanpa_jarak_kaki')])>
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-8 gap-y-3 px-4 py-8 text-[0.95rem]">
            <p class="flex items-center gap-3">
                <svg width="30" height="30" viewBox="0 0 64 64" aria-hidden="true"><rect width="64" height="64" rx="14" fill="#F5B700"/><path d="M15 15h28l9 13-9 13H27l-10 9v-9h-2a3 3 0 0 1-3-3V18a3 3 0 0 1 3-3z" fill="#1D4A34"/><circle cx="43" cy="28" r="3" fill="#F5B700"/></svg>
                <span><span class="font-display text-xl font-extrabold text-white">Harlok</span>, harga lokal dari laporan peternak dan petani.</span>
            </p>
            <p>Dibuat di Bali.</p>
        </div>
    </footer>
</body>
</html>

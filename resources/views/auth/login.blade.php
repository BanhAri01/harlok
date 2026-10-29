@extends('layouts.app', ['title' => 'Masuk'])

@section('content')
<div class="mx-auto grid max-w-5xl items-center gap-x-16 gap-y-10 px-4 py-14 md:grid-cols-2">
    <div class="order-2 md:order-1">
        <div class="papan-pasar px-6 py-6">
            <p class="font-display text-2xl font-extrabold text-white">Untuk pengepul dan koordinator</p>
            <ul class="mt-4 space-y-3 text-kapur/80">
                <li class="flex gap-3"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-kapur"><x-ikon-komoditas slug="telur" size="24" /></span>Pengepul mencari stok terdekat di peta dan menawar langsung ke WhatsApp peternak.</li>
                <li class="flex gap-3"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-kapur"><x-ikon-komoditas slug="cabai-rawit" size="24" /></span>Koordinator memverifikasi pelapor dan memeriksa harga yang janggal.</li>
                <li class="flex gap-3"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-kapur"><x-ikon-komoditas slug="beras" size="24" /></span>Peternak tidak perlu akun. Cukup chat lewat WhatsApp.</li>
            </ul>
        </div>
    </div>

    <div class="order-1 md:order-2">
        <h1 class="judul-halaman">Masuk</h1>
        <p class="mt-3 text-tinta/70">Pakai email dan kata sandi yang diberikan koordinator.</p>

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="email" class="label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="input">
                @error('email')<p class="mt-1 text-sm text-turun">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="label">Kata sandi</label>
                <input id="password" name="password" type="password" required autocomplete="current-password" class="input">
            </div>
            <button class="btn-gelap w-full py-3">Masuk</button>
        </form>
    </div>
</div>
@endsection

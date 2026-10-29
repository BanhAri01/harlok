@extends('layouts.app', ['title' => 'Masuk'])

@section('content')
<div class="mx-auto max-w-md px-4 py-16">
    <h1 class="text-3xl font-bold text-papan">Masuk</h1>
    <p class="mt-2 text-tinta/70">Halaman ini untuk pengepul dan koordinator. Peternak tidak perlu akun, cukup chat lewat WhatsApp.</p>

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
@endsection

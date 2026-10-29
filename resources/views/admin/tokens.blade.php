@extends('layouts.app', ['title' => 'Token API'])

@section('content')
<div class="mx-auto max-w-4xl px-4 py-8">
    @include('admin._nav', ['heading' => 'Token API untuk '.$partner->displayName(), 'sub' => 'Token hanya bisa membaca data harga gabungan, berlaku 6 bulan, dibatasi 60 permintaan per menit.'])

    @if (session('token'))
        <div class="mt-6 rounded-xl border border-kuning bg-kuning-muda/60 p-4">
            <p class="font-semibold">Salin token ini sekarang. Token tidak akan ditampilkan lagi.</p>
            <code class="mt-2 block break-all rounded bg-white p-3 text-sm">{{ session('token') }}</code>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.tokens.store', $partner) }}" class="panel mt-6 flex flex-wrap items-end gap-3 p-5">
        @csrf
        <div class="flex-1"><label for="label" class="label">Nama token</label><input id="label" name="label" required maxlength="60" placeholder="contoh: Dashboard Dinas Pertanian" class="input"></div>
        <button class="btn-gelap">Buat token</button>
    </form>

    <div class="panel mt-4 divide-y divide-garis">
        @forelse ($tokens as $token)
            <div class="flex items-center gap-3 px-5 py-3">
                <div class="flex-1">
                    <p class="font-semibold">{{ $token->name }}</p>
                    <p class="text-sm text-tinta/60">Dibuat {{ $token->created_at->translatedFormat('j M Y') }}, terakhir dipakai {{ $token->last_used_at?->diffForHumans() ?? 'belum pernah' }}, berlaku sampai {{ $token->expires_at?->translatedFormat('j M Y') }}</p>
                </div>
                <form method="POST" action="{{ route('admin.tokens.destroy', $token) }}">@csrf @method('DELETE')<button class="btn-garis">Cabut</button></form>
            </div>
        @empty
            <p class="px-5 py-8 text-center text-tinta/60">Belum ada token.</p>
        @endforelse
    </div>
</div>
@endsection

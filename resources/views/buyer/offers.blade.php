@extends('layouts.app', ['title' => 'Tawaran Saya'])

@php
    $labels = [
        'sent' => ['Menunggu jawaban', 'bg-kuning-muda text-tinta'],
        'countered' => ['Ditawar balik', 'bg-[#FFE1D6] text-turun'],
        'accepted' => ['Sepakat', 'bg-[#E3F3E8] text-naik'],
        'rejected' => ['Ditolak', 'bg-kertas text-tinta/60'],
        'cancelled' => ['Dibatalkan', 'bg-kertas text-tinta/60'],
    ];
@endphp

@section('content')
<div class="mx-auto max-w-5xl px-4 py-8">
    <h1 class="font-display text-3xl font-extrabold tracking-tight">Tawaran saya</h1>
    <p class="mt-1 text-tinta/70">Jawaban peternak dari WhatsApp muncul di sini. Kontak peternak terbuka setelah sepakat.</p>

    @error('offer')<p class="mt-4 rounded-lg bg-[#FFE1D6] px-4 py-2 text-sm text-turun">{{ $message }}</p>@enderror

    <div class="panel mt-6 divide-y divide-garis">
        @forelse ($offers as $offer)
            @php [$label, $class] = $labels[$offer->status] ?? [$offer->status, 'bg-kertas']; @endphp
            <div class="flex flex-wrap items-center gap-4 px-5 py-4">
                <div class="min-w-0 flex-1">
                    <p class="font-semibold">{{ $offer->report->commodity->name }} · {{ $offer->report->region->name }}</p>
                    <p class="text-sm text-tinta/60">
                        {{ \App\Support\Rupiah::kg($offer->quantity_kg) }} · tawaran {{ \App\Support\Rupiah::format($offer->price_per_kg) }}/kg
                        @if ($offer->market_avg) · rata-rata {{ \App\Support\Rupiah::format($offer->market_avg) }} @endif
                        · {{ $offer->created_at->diffForHumans() }}
                    </p>
                    @if ($offer->status === 'accepted')
                        <p class="mt-1 text-sm">Peternak: <strong>{{ $offer->report->farmer->name }}</strong> · {{ \App\Support\Phone::display($offer->report->farmer->phone) }}</p>
                    @endif
                    @if ($offer->status === 'countered')
                        <p class="mt-1 text-sm">Peternak meminta <strong class="angka">{{ \App\Support\Rupiah::format($offer->counter_price) }}/kg</strong></p>
                    @endif
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $class }}">{{ $label }}</span>
                <div class="flex gap-2">
                    @if ($offer->status === 'countered')
                        <form method="POST" action="{{ route('buyer.offers.accept', $offer) }}">
                            @csrf
                            <button class="btn-gelap">Setujui {{ \App\Support\Rupiah::format($offer->counter_price) }}</button>
                        </form>
                    @endif
                    @if (in_array($offer->status, ['sent', 'countered'], true))
                        <form method="POST" action="{{ route('buyer.offers.cancel', $offer) }}">
                            @csrf
                            <button class="btn-garis">Batalkan</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="px-5 py-12 text-center">
                <p class="text-tinta/70">Belum ada tawaran.</p>
                <a href="{{ route('buyer.dashboard') }}" class="btn-gelap mt-4">Cari stok</a>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $offers->links() }}</div>
</div>
@endsection

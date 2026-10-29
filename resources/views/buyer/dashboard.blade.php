@extends('layouts.app', ['title' => 'Cari Stok'])

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8"
    x-data="papan({ url: @js(route('buyer.data')), commodity: @js($commodity->slug), buyer: true, offerUrlTemplate: @js(route('buyer.offer', ['report' => '__ID__'])), focus: @js($focus) })">

    <div class="flex flex-wrap items-end gap-4">
        <div>
            <h1 class="font-display text-3xl font-extrabold tracking-tight">Cari stok</h1>
            <p class="mt-1 text-tinta/70">Klik titik di peta atau baris di daftar untuk menawar. Tawaran langsung dikirim ke WhatsApp peternak.</p>
        </div>
        <div class="ml-auto flex flex-wrap gap-1" role="tablist" aria-label="Komoditas">
            @foreach ($commodities as $item)
                <button type="button" role="tab" @click="switchCommodity(@js($item->slug))" :aria-selected="commodity === @js($item->slug)"
                    :class="commodity === @js($item->slug) ? 'bg-papan text-kapur' : 'bg-white text-tinta/70 border border-garis hover:text-tinta'"
                    class="rounded-full px-3 py-1.5 text-sm font-semibold">{{ Str::before($item->name, ' (') }}</button>
            @endforeach
        </div>
    </div>

    @if (auth()->user()->is_demo)
        <p class="mt-4 rounded-lg border border-kuning/50 bg-kuning-muda/60 px-4 py-2 text-sm">Akun demo: tawaran hanya bisa dikirim ke stok contoh atau stok dari halaman <a href="{{ route('simulator.show') }}" class="font-semibold underline">Coba Chat</a>, supaya peternak sungguhan tidak menerima tawaran percobaan.</p>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        <div class="overflow-hidden rounded-2xl border border-garis bg-white lg:sticky lg:top-4 lg:self-start">
            <div class="flex gap-1 border-b border-garis p-2 text-sm font-semibold">
                <button type="button" @click="goTo('bali')" :class="area === 'bali' ? 'bg-papan text-kapur' : 'text-tinta/70'" class="rounded-md px-3 py-1">Bali</button>
                <button type="button" @click="goTo('diy')" :class="area === 'diy' ? 'bg-papan text-kapur' : 'text-tinta/70'" class="rounded-md px-3 py-1">DI Yogyakarta</button>
                <span class="ml-auto self-center pr-2 font-normal text-tinta/55" x-text="(data?.points.length ?? 0) + ' stok tersedia'"></span>
            </div>
            <div x-ref="map" class="h-[34rem] w-full" role="region" aria-label="Peta stok"></div>
        </div>

        <div class="space-y-4">
            <div class="panel p-5" x-show="selected" x-cloak>
                <template x-if="selected">
                    <div>
                        <div class="flex items-start gap-3">
                            <div>
                                <p class="text-sm text-tinta/60" x-text="selected.region + (selected.farmer ? ' · ' + selected.farmer : '')"></p>
                                <p class="angka text-3xl font-extrabold" x-text="rupiah(selected.price) + '/kg'"></p>
                                <p class="text-tinta/70" x-text="kg(selected.quantity_kg) + ' tersedia · ' + selected.ago"></p>
                            </div>
                            <button type="button" @click="selected = null" class="ml-auto rounded-md px-2 py-1 text-sm text-tinta/60 hover:bg-kertas" aria-label="Tutup">✕</button>
                        </div>
                        <p class="mt-2 text-sm" x-show="averages[selected.region]">
                            Rata-rata <span x-text="selected.region"></span>: <strong class="angka" x-text="rupiah(averages[selected.region] ?? 0)"></strong>
                        </p>

                        <template x-if="selected.my_offer">
                            <p class="mt-4 rounded-lg bg-kertas px-3 py-2 text-sm">Anda sudah mengirim tawaran untuk stok ini. Lihat di <a href="{{ route('buyer.offers') }}" class="font-semibold underline">Tawaran Saya</a>.</p>
                        </template>

                        <form x-show="!selected.my_offer" @submit.prevent="sendOffer()" class="mt-4 grid grid-cols-2 gap-3">
                            <div>
                                <label for="offer-price" class="label">Harga/kg</label>
                                <input id="offer-price" type="number" min="1" step="1" inputmode="numeric" x-model.number="offer.price" class="input angka" required>
                            </div>
                            <div>
                                <label for="offer-qty" class="label">Jumlah (kg)</label>
                                <input id="offer-qty" type="number" min="1" step="any" :max="selected.quantity_kg" x-model.number="offer.qty" class="input angka" required>
                            </div>
                            <p class="col-span-2 text-sm" x-show="offerPercent !== null"
                                :class="offerPercent <= -10 ? 'text-turun font-semibold' : 'text-tinta/70'"
                                x-text="offerPercent <= -10
                                    ? 'Tawaran ' + Math.abs(offerPercent).toFixed(1).replace('.', ',') + '% di bawah rata-rata. Peternak akan menerima peringatan.'
                                    : (offerPercent >= 0 ? 'Setara atau di atas rata-rata wilayah.' : 'Sedikit di bawah rata-rata wilayah.')"></p>
                            <p class="col-span-2 text-sm text-turun" x-show="offerError" x-text="offerError"></p>
                            <p class="col-span-2 text-sm font-semibold text-naik" x-show="notice" x-text="notice"></p>
                            <button class="btn-gelap col-span-2 py-3" :disabled="sending" x-text="sending ? 'Mengirim…' : 'Kirim tawaran ke WhatsApp peternak'"></button>
                        </form>
                    </div>
                </template>
            </div>

            <div class="panel">
                <h2 class="border-b border-garis px-5 py-3 font-display text-lg font-bold">Stok terbaru</h2>
                <ul class="max-h-[28rem] divide-y divide-garis overflow-y-auto">
                    <template x-for="point in data?.points ?? []" :key="point.id">
                        <li>
                            <button type="button" @click="select(point, true)" class="flex w-full items-center gap-3 px-5 py-3 text-left hover:bg-kertas" :class="selected?.id === point.id && 'bg-kertas'">
                                <span class="titik-stok inline-block shrink-0" :style="'background:' + colorFor(point)"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block font-semibold" x-text="point.region + (point.farmer ? ' · ' + point.farmer : '')"></span>
                                    <span class="block text-sm text-tinta/60" x-text="kg(point.quantity_kg) + ' · ' + point.ago"></span>
                                </span>
                                <span class="angka font-bold" x-text="rupiah(point.price)"></span>
                            </button>
                        </li>
                    </template>
                    <li class="px-5 py-8 text-center text-tinta/60" x-show="data && data.points.length === 0">Belum ada stok untuk komoditas ini.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

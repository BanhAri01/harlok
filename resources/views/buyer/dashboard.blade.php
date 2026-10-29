@extends('layouts.app', ['title' => 'Cari Stok'])

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8"
    x-data="papan({ url: @js(route('buyer.data')), commodity: @js($commodity->slug), buyer: true, offerUrlTemplate: @js(route('buyer.offer', ['report' => '__ID__'])), focus: @js($focus) })">

    <div class="flex flex-wrap items-end gap-x-4 gap-y-5">
        <div>
            <h1 class="judul-halaman">Cari stok</h1>
            <p class="mt-3 max-w-2xl text-tinta/70">Klik titik di peta atau baris di daftar untuk menawar. Tawaran langsung dikirim ke WhatsApp peternak.</p>
        </div>
        <div class="flex w-full gap-2 overflow-x-auto pb-1" role="tablist" aria-label="Komoditas">
            @foreach ($commodities as $item)
                <button type="button" role="tab" @click="switchCommodity(@js($item->slug))" :aria-selected="commodity === @js($item->slug)"
                    :class="commodity === @js($item->slug) ? 'border-papan bg-papan text-white shadow-[0_3px_0_#102A1C]' : 'border-tinta/15 bg-white text-tinta/80 hover:border-papan/50'"
                    class="flex shrink-0 items-center gap-2 rounded-full border-2 py-1 pl-1 pr-4 text-[0.95rem] font-bold">
                    <span class="grid size-8 place-items-center rounded-full bg-kapur"><x-ikon-komoditas :slug="$item->slug" size="24" /></span>
                    {{ Str::before($item->name, ' (') }}
                </button>
            @endforeach
        </div>
    </div>

    @if (auth()->user()->is_demo)
        <p class="mt-4 rounded-2xl bg-kuning-muda px-5 py-3 text-sm">Akun demo: tawaran hanya bisa dikirim ke stok contoh atau stok dari halaman <a href="{{ route('simulator.show') }}" class="font-semibold underline">Coba Chat</a>, supaya peternak sungguhan tidak menerima tawaran percobaan.</p>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        <div class="overflow-hidden rounded-2xl border-2 border-garis bg-white lg:sticky lg:top-4 lg:self-start">
            <div class="flex gap-1 border-b border-garis p-2 text-sm font-semibold">
                <button type="button" @click="goTo('bali')" :class="area === 'bali' ? 'bg-papan text-white' : 'text-tinta/70 hover:text-tinta'" class="rounded-full px-4 py-1">Bali</button>
                <button type="button" @click="goTo('diy')" :class="area === 'diy' ? 'bg-papan text-white' : 'text-tinta/70 hover:text-tinta'" class="rounded-full px-4 py-1">DI Yogyakarta</button>
                <span class="ml-auto self-center pr-2 font-normal text-tinta/55" x-text="(data?.points.length ?? 0) + ' stok tersedia'"></span>
            </div>
            <div x-ref="map" class="h-[34rem] w-full" role="region" aria-label="Peta stok"></div>
        </div>

        <div class="space-y-4">
            <div class="papan-pasar p-5" x-show="selected" x-cloak>
                <template x-if="selected">
                    <div>
                        <div class="flex items-start gap-3">
                            <div>
                                <p class="text-sm text-kapur/65" x-text="selected.region + (selected.farmer ? ', ' + selected.farmer : '')"></p>
                                <p class="harga-papan mt-1 text-[2.6rem]" x-text="rupiah(selected.price) + '/kg'"></p>
                                <p class="mt-1 text-kapur/75" x-text="kg(selected.quantity_kg) + ' tersedia, ' + selected.ago"></p>
                            </div>
                            <button type="button" @click="selected = null" class="ml-auto grid size-8 place-items-center rounded-full text-kapur/70 hover:bg-white/10" aria-label="Tutup">✕</button>
                        </div>
                        <p class="mt-2 text-sm text-kapur/80" x-show="averages[selected.region]">
                            Rata-rata <span x-text="selected.region"></span>: <strong class="angka text-white" x-text="rupiah(averages[selected.region] ?? 0)"></strong>
                        </p>

                        <template x-if="selected.my_offer">
                            <p class="mt-4 rounded-xl bg-white/10 px-3 py-2 text-sm">Anda sudah mengirim tawaran untuk stok ini. Lihat di <a href="{{ route('buyer.offers') }}" class="font-bold text-kuning underline">Tawaran Saya</a>.</p>
                        </template>

                        <form x-show="!selected.my_offer" @submit.prevent="sendOffer()" class="mt-4 grid grid-cols-2 gap-3">
                            <div>
                                <label for="offer-price" class="label text-kapur/80">Harga per kg</label>
                                <input id="offer-price" type="number" min="1" step="1" inputmode="numeric" x-model.number="offer.price" class="input angka" required>
                            </div>
                            <div>
                                <label for="offer-qty" class="label text-kapur/80">Jumlah (kg)</label>
                                <input id="offer-qty" type="number" min="1" step="any" :max="selected.quantity_kg" x-model.number="offer.qty" class="input angka" required>
                            </div>
                            <p class="col-span-2 text-sm" x-show="offerPercent !== null"
                                :class="offerPercent <= -10 ? 'text-[#FFB4A6] font-bold' : 'text-kapur/75'"
                                x-text="offerPercent <= -10
                                    ? 'Tawaran ' + Math.abs(offerPercent).toFixed(1).replace('.', ',') + '% di bawah rata-rata. Peternak akan menerima peringatan.'
                                    : (offerPercent >= 0 ? 'Setara atau di atas rata-rata wilayah.' : 'Sedikit di bawah rata-rata wilayah.')"></p>
                            <p class="col-span-2 text-sm text-[#FFB4A6]" x-show="offerError" x-text="offerError"></p>
                            <p class="col-span-2 text-sm font-bold text-[#7FD49B]" x-show="notice" x-text="notice"></p>
                            <button class="btn-kuning col-span-2 py-3" :disabled="sending" x-text="sending ? 'Mengirim…' : 'Kirim tawaran ke WhatsApp peternak'"></button>
                        </form>
                    </div>
                </template>
            </div>

            <div class="panel">
                <h2 class="judul-bagian border-b-2 border-garis px-5 py-3">Stok terbaru</h2>
                <ul class="max-h-[28rem] divide-y divide-garis overflow-y-auto">
                    <template x-for="point in data?.points ?? []" :key="point.id">
                        <li>
                            <button type="button" @click="select(point, true)" class="flex w-full items-center gap-3 px-5 py-3 text-left hover:bg-daun" :class="selected?.id === point.id && 'bg-daun'">
                                <span class="titik-stok inline-block shrink-0" :style="'background:' + colorFor(point)"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block font-bold" x-text="point.region + (point.farmer ? ', ' + point.farmer : '')"></span>
                                    <span class="block text-sm text-tinta/60" x-text="kg(point.quantity_kg) + ', ' + point.ago"></span>
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

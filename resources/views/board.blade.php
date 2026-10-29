@extends('layouts.app')

@section('content')
@php
    $waDigits = $waNumber ? preg_replace('/\D/', '', $waNumber) : null;
    $waDisplay = $waNumber ? \App\Support\Phone::display($waNumber) : null;
@endphp
<div x-data="papan({ url: @js(route('board.data')), commodity: @js($commodity->slug) })">
    <section class="mx-auto max-w-6xl px-4 pt-8 sm:pt-12">
        <div class="flex flex-wrap items-end justify-between gap-x-10 gap-y-4">
            <div>
                <p class="text-tinta/60">{{ now()->translatedFormat('l, j F Y') }}</p>
                <h1 class="mt-1 text-[2rem] font-bold leading-tight text-papan sm:text-[2.6rem]"
                    x-text="'Harga ' + (data?.commodity.name ?? @js($commodity->name)).replace(/ \(.*\)$/, '').toLowerCase() + ' hari ini'">Harga {{ Str::lower(Str::before($commodity->name, ' (')) }} hari ini</h1>
                <p class="mt-1 text-tinta/70">Rata-rata per kilogram dari laporan peternak dan petani yang sudah diverifikasi.</p>
            </div>
            <div class="flex flex-wrap gap-x-1 gap-y-2" role="tablist" aria-label="Pilih komoditas">
                @foreach ($commodities as $item)
                    <button type="button" role="tab" @click="switchCommodity(@js($item->slug))"
                        :aria-selected="commodity === @js($item->slug)"
                        :class="commodity === @js($item->slug) ? 'border-papan bg-papan text-white' : 'border-tinta/20 text-tinta/75 hover:border-tinta/50'"
                        class="rounded-md border px-3 py-1.5 text-[0.95rem] font-bold">{{ Str::before($item->name, ' (') }}</button>
                @endforeach
            </div>
        </div>

        <div class="mt-8 grid gap-x-14 gap-y-10 lg:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)]">
            <div>
                <template x-if="data && data.regions.length === 0">
                    <p class="border-y border-garis py-10 text-tinta/70">Belum ada laporan minggu ini untuk komoditas ini. Laporan pertama yang masuk akan langsung tampil di sini.</p>
                </template>
                <p x-show="!data && !error" class="py-10 text-tinta/60">Memuat harga…</p>

                <ol class="border-t border-garis" x-show="data && data.regions.length" aria-label="Harga rata-rata per wilayah">
                    <template x-for="row in data?.regions ?? []" :key="row.region + row.avg">
                        <li class="harga-berubah flex items-baseline border-b border-garis py-3">
                            <div class="min-w-0">
                                <p class="text-lg font-bold" x-text="row.region"></p>
                                <p class="text-sm text-tinta/60" x-text="row.count + ' laporan, ' + new Date(row.date + 'T00:00:00').toLocaleDateString('id-ID', { day: 'numeric', month: 'long' })"></p>
                            </div>
                            <span class="titik-penghubung" aria-hidden="true"></span>
                            <div class="text-right">
                                <p class="angka text-2xl font-bold sm:text-[1.7rem]" x-text="rupiah(row.avg)"></p>
                                <p class="angka text-sm font-bold"
                                    :class="row.change > 0 ? 'text-naik' : (row.change < 0 ? 'text-turun' : 'text-tinta/50')"
                                    x-text="row.change === null ? 'laporan pertama' : (row.change === 0 ? 'tetap' : (row.change > 0 ? 'naik ' : 'turun ') + rupiah(Math.abs(row.change)))"></p>
                            </div>
                        </li>
                    </template>
                </ol>

                <p class="mt-4 text-tinta/70">
                    <span x-text="(data?.stats.farmers ?? {{ $stats['farmers'] }}).toLocaleString('id-ID')">{{ number_format($stats['farmers'], 0, ',', '.') }}</span> peternak dan petani sudah melapor.
                    Hari ini masuk <span x-text="data?.stats.reports_today ?? {{ $stats['reports_today'] }}">{{ $stats['reports_today'] }}</span> laporan, dan
                    <span x-text="data?.stats.deals ?? {{ $stats['deals'] }}">{{ $stats['deals'] }}</span> tawaran sudah sepakat.
                </p>
                <p class="mt-1 text-sm text-turun" x-show="error" x-text="error"></p>
            </div>

            <div>
                <template x-if="data?.brief">
                    <div class="border-l-4 border-kuning pl-4">
                        <p class="text-lg leading-relaxed" x-text="data.brief.text"></p>
                        <p class="mt-2 text-sm text-tinta/60" x-text="data.brief.source === 'ai' ? 'Ringkasan ini ditulis AI dari angka di samping. Cek angkanya sebelum memutuskan.' : 'Ringkasan otomatis dari angka di samping.'"></p>
                    </div>
                </template>

                <div class="mt-8">
                    <p class="font-bold">Rata-rata 14 hari terakhir</p>
                    <div class="mt-2 h-44"><canvas x-ref="chart" aria-label="Grafik harga rata-rata 14 hari terakhir" role="img"></canvas></div>
                </div>

                <div class="mt-8 rounded-lg bg-daun p-5">
                    <p class="text-lg font-bold">Punya stok untuk dijual?</p>
                    @if ($waDigits)
                        <p class="mt-1 text-tinta/80">Kirim <span class="stabilo font-bold">HARLOK</span> lewat WhatsApp ke <span class="angka font-bold">{{ $waDisplay }}</span>. Setelah daftar, cukup tulis stok dan harganya seperti chat biasa.</p>
                        <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-3">
                            <a href="https://wa.me/{{ $waDigits }}?text={{ rawurlencode('HARLOK') }}" class="btn-utama" target="_blank" rel="noopener">Chat Harlok di WhatsApp</a>
                            @if (config('lapor.simulator.enabled'))
                                <a href="{{ route('simulator.show') }}" class="tautan">Coba dulu di sini</a>
                            @endif
                        </div>
                    @elseif (config('lapor.simulator.enabled'))
                        <p class="mt-1 text-tinta/80">Coba chat Harlok langsung dari browser, tanpa WhatsApp.</p>
                        <a href="{{ route('simulator.show') }}" class="btn-utama mt-4">Coba chat</a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if ($stats['has_samples'])
        <p class="mx-auto mt-10 max-w-6xl px-4 text-sm text-tinta/65">
            Catatan: sebagian harga dan titik di halaman ini adalah data contoh untuk demonstrasi. Laporan asli dari WhatsApp tampil bersamanya.
        </p>
    @endif

    <section class="mx-auto mt-16 max-w-6xl px-4">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-papan">Stok yang sedang dijual</h2>
                <p class="mt-1 max-w-2xl text-tinta/70">Setiap titik adalah stok yang dilaporkan lewat chat. Pengepul yang sudah masuk bisa langsung menawar dari peta.</p>
            </div>
            <div class="flex gap-1 text-[0.95rem] font-bold">
                <button type="button" @click="goTo('bali')" :class="area === 'bali' ? 'border-papan bg-papan text-white' : 'border-tinta/20 text-tinta/75 hover:border-tinta/50'" class="rounded-md border px-3 py-1.5">Bali</button>
                <button type="button" @click="goTo('diy')" :class="area === 'diy' ? 'border-papan bg-papan text-white' : 'border-tinta/20 text-tinta/75 hover:border-tinta/50'" class="rounded-md border px-3 py-1.5">DI Yogyakarta</button>
            </div>
        </div>

        <div class="mt-5 overflow-hidden rounded-lg border border-garis">
            <div x-ref="map" class="h-[26rem] w-full sm:h-[30rem]" role="region" aria-label="Peta stok"></div>
        </div>
        <div class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-sm text-tinta/75">
            <span class="flex items-center gap-2"><span class="titik-stok inline-block" style="background:#1E6B40"></span>Dekat rata-rata</span>
            <span class="flex items-center gap-2"><span class="titik-stok inline-block" style="background:#A8321F"></span>Lebih murah dari rata-rata</span>
            <span class="flex items-center gap-2"><span class="titik-stok inline-block" style="background:#C98A00"></span>Lebih mahal dari rata-rata</span>
            <span class="flex items-center gap-2"><span class="titik-stok inline-block" style="background:#8A968D"></span>Pelapor belum diverifikasi</span>
        </div>
    </section>

    <section class="mx-auto mt-20 grid max-w-6xl gap-x-14 gap-y-8 px-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,26rem)]">
        <div class="max-w-xl">
            <h2 class="text-2xl font-bold text-papan">Semuanya lewat chat</h2>
            <p class="mt-3 text-tinta/80">Peternak tidak perlu memasang aplikasi atau mengingat format. Tulis saja seperti biasa, termasuk singkatan dan bahasa Bali. Harlok membaca maksudnya, menanyakan ulang supaya tidak salah catat, lalu membalas dengan harga rata-rata di wilayahnya.</p>
            <p class="mt-3 text-tinta/80">Pengepul melihat stok di peta dan mengirim tawaran dari web. Tawarannya sampai ke WhatsApp peternak bersama harga pasar, jadi peternak tahu kalau ditawar terlalu murah. Nomor kedua pihak baru dibuka setelah sepakat.</p>
            <p class="mt-3 text-tinta/80">Koordinator kelompok ternak memverifikasi pelapor dan memeriksa harga yang janggal, supaya harga acuan tetap jujur.</p>
        </div>

        <figure class="rounded-lg bg-wa-latar p-4 text-[0.95rem]" aria-label="Contoh percakapan">
            <div class="space-y-2">
                <p class="ml-auto w-fit max-w-[85%] rounded-lg rounded-tr-none bg-wa px-3 py-2">taluh 2 ton 26rb, siap kirim besok</p>
                <p class="w-fit max-w-[90%] rounded-lg rounded-tl-none bg-white px-3 py-2">Saya catat begini: Telur Ayam Ras 2.000 kg, Rp26.000/kg, Tabanan. Kalau sudah benar balas <strong>YA</strong>.</p>
                <p class="ml-auto w-fit rounded-lg rounded-tr-none bg-wa px-3 py-2">ya</p>
                <p class="w-fit max-w-[90%] rounded-lg rounded-tl-none bg-white px-3 py-2">Tersimpan, stok Anda sekarang terlihat oleh pengepul di Tabanan. Rata-rata di sana Rp25.800/kg, harga Anda sedikit di atasnya.</p>
                <p class="w-fit max-w-[90%] rounded-lg rounded-tl-none bg-white px-3 py-2">UD Sari Tani menawar 1.000 kg seharga Rp25.500/kg. Balas 1 untuk terima, 2 untuk tolak, atau 3 untuk tawar balik.</p>
                <p class="ml-auto w-fit rounded-lg rounded-tr-none bg-wa px-3 py-2">26 lah</p>
                <p class="w-fit max-w-[90%] rounded-lg rounded-tl-none bg-white px-3 py-2">Tawaran balik Rp26.000/kg sudah dikirim ke pembeli.</p>
            </div>
            <figcaption class="mt-3 text-sm text-tinta/60">Contoh percakapan peternak dengan Harlok.</figcaption>
        </figure>
    </section>

    <section class="mx-auto mt-20 grid max-w-6xl gap-x-14 gap-y-6 border-t border-garis px-4 pt-12 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
        <div class="max-w-xl">
            <h2 class="text-2xl font-bold text-papan">Harga ini boleh dipakai siapa saja</h2>
            <p class="mt-3 text-tinta/80">Dinas, koperasi, dan situs desa bisa memasang widget harga, mengunduh CSV, atau mengambil data lewat API. Yang dibagikan hanya angka gabungan per wilayah. Nama dan nomor peternak tidak pernah ikut keluar.</p>
            <a href="{{ route('api.docs') }}" class="btn-garis mt-5">Lihat cara memakai data</a>
        </div>
        <pre class="kode overflow-x-auto p-4 text-sm leading-relaxed"><code>GET /api/v1/harga?komoditas=telur&amp;wilayah=tabanan
Authorization: Bearer &lt;token&gt;

{
  "komoditas": "telur",
  "satuan": "rupiah/kg",
  "data": [{
    "nama_wilayah": "Tabanan",
    "rata_rata": 26400,
    "terendah": 25500,
    "tertinggi": 27200,
    "jumlah_laporan": 18
  }]
}</code></pre>
    </section>
</div>
@endsection

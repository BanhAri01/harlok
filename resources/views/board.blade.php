@extends('layouts.app')

@section('content')
@php
    $waDigits = $waNumber ? preg_replace('/\D/', '', $waNumber) : null;
    $waDisplay = $waNumber ? \App\Support\Phone::display($waNumber) : null;
@endphp
<div x-data="papan({ url: @js(route('board.data')), commodity: @js($commodity->slug) })">
    <section class="mx-auto max-w-6xl px-4 pt-8 sm:pt-10">
        <div class="flex items-end justify-between gap-x-10">
            <div class="max-w-3xl">
                <h1 class="font-display text-[2.6rem] font-extrabold leading-[0.95] text-papan sm:text-[3.8rem]">Tahu harga pasar dulu, baru jual.</h1>
                <p class="mt-3 max-w-2xl text-lg text-tinta/75">Harga di papan ini dikumpulkan dari chat WhatsApp peternak dan petani, lalu dihitung rata-ratanya per wilayah setiap hari.</p>
            </div>
            <x-ilustrasi-chat class="-mb-6 hidden w-64 shrink-0 md:block" />
        </div>

        <div class="mt-7 flex gap-2 overflow-x-auto pb-1" role="tablist" aria-label="Pilih komoditas">
            @foreach ($commodities as $item)
                <button type="button" role="tab" @click="switchCommodity(@js($item->slug))"
                    :aria-selected="commodity === @js($item->slug)"
                    :class="commodity === @js($item->slug) ? 'border-papan bg-papan text-white shadow-[0_3px_0_#102A1C]' : 'border-tinta/15 bg-white text-tinta/80 hover:border-papan/50'"
                    class="flex shrink-0 items-center gap-2 rounded-full border-2 py-1.5 pl-1.5 pr-4 text-[0.95rem] font-bold">
                    <span class="grid size-9 place-items-center rounded-full bg-kapur"><x-ikon-komoditas :slug="$item->slug" size="28" /></span>
                    {{ Str::before($item->name, ' (') }}
                </button>
            @endforeach
        </div>

        <div class="papan-pasar mt-5 px-5 pb-5 pt-5 sm:px-8 sm:pb-7 sm:pt-6">
            <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2 border-b-2 border-dashed border-kapur/15 pb-4">
                <div class="flex items-center gap-3">
                    <span class="grid size-12 place-items-center rounded-full bg-kapur">
                        @foreach ($commodities as $item)
                            <x-ikon-komoditas :slug="$item->slug" size="38" x-show="commodity === '{{ $item->slug }}'" />
                        @endforeach
                    </span>
                    <div>
                        <p class="font-display text-[1.7rem] font-extrabold leading-none text-white sm:text-[2rem]" x-text="(data?.commodity.name ?? @js($commodity->name)).replace(/ \(.*\)$/, '')">{{ Str::before($commodity->name, ' (') }}</p>
                        <p class="mt-1 text-sm text-kapur/65">Rupiah per kilogram, {{ now()->translatedFormat('l j F Y') }}</p>
                    </div>
                </div>
                <p class="flex items-center gap-2 text-sm text-kapur/70"><span class="size-2.5 animate-pulse rounded-full bg-[#7FD49B] motion-reduce:animate-none"></span>Diperbarui otomatis</p>
            </div>

            <template x-if="data && data.regions.length === 0">
                <p class="py-12 text-center text-lg text-kapur/75">Minggu ini belum ada laporan. Laporan pertama yang masuk langsung tampil di papan ini.</p>
            </template>
            <p x-show="!data && !error" class="py-12 text-center text-kapur/60">Memuat harga…</p>
            <p x-show="error" class="py-4 text-center text-[#FFB4A6]" x-text="error"></p>

            <div class="grid gap-x-12 lg:grid-cols-2" x-show="data && data.regions.length">
                <template x-for="group in provinces" :key="group.name">
                    <div class="mt-4">
                        <p class="font-display text-xl font-bold text-kuning/80" x-text="group.name"></p>
                        <ol class="mt-1">
                            <template x-for="row in group.rows" :key="row.region + row.avg">
                                <li class="harga-berubah flex items-baseline border-b border-kapur/10 py-2.5 last:border-0">
                                    <div class="min-w-0">
                                        <p class="text-lg font-bold text-white" x-text="row.region"></p>
                                        <p class="text-xs text-kapur/55" x-text="row.count + ' laporan, ' + new Date(row.date + 'T00:00:00').toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })"></p>
                                    </div>
                                    <span class="titik-papan" aria-hidden="true"></span>
                                    <p class="harga-papan text-[2.1rem] sm:text-[2.5rem]" x-text="row.avg.toLocaleString('id-ID')"></p>
                                    <p class="angka ml-3 w-16 shrink-0 text-right text-sm font-bold"
                                        :class="row.change > 0 ? 'text-[#7FD49B]' : (row.change < 0 ? 'text-[#FF9C8F]' : 'text-kapur/45')"
                                        x-text="row.change === null ? 'baru' : (row.change === 0 ? 'tetap' : (row.change > 0 ? '▲ ' : '▼ ') + Math.abs(row.change).toLocaleString('id-ID'))"></p>
                                </li>
                            </template>
                        </ol>
                    </div>
                </template>
            </div>

            <p class="mt-5 border-t-2 border-dashed border-kapur/15 pt-4 text-kapur/75">
                <span class="font-bold text-white" x-text="(data?.stats.farmers ?? {{ $stats['farmers'] }}).toLocaleString('id-ID')">{{ number_format($stats['farmers'], 0, ',', '.') }}</span> peternak dan petani sudah melapor.
                Hari ini masuk <span class="font-bold text-white" x-text="data?.stats.reports_today ?? {{ $stats['reports_today'] }}">{{ $stats['reports_today'] }}</span> laporan.
            </p>
        </div>

        @if ($stats['has_samples'])
            <p class="mt-3 text-sm text-tinta/60">Sebagian harga dan titik di halaman ini adalah data contoh untuk demonstrasi. Laporan asli dari WhatsApp tampil bersamanya.</p>
        @endif

        <div class="mt-8 grid gap-6 lg:grid-cols-3">
            <div class="rounded-2xl bg-kuning-muda p-6">
                <p class="font-display text-2xl font-bold text-papan">Kata Harlok hari ini</p>
                <template x-if="data?.brief">
                    <div>
                        <p class="mt-3 text-lg leading-relaxed" x-text="data.brief.text"></p>
                        <p class="mt-3 text-sm text-tinta/60" x-text="data.brief.source === 'ai' ? 'Ditulis AI dari angka di papan. Cek angkanya sebelum memutuskan.' : 'Dirangkum otomatis dari angka di papan.'"></p>
                    </div>
                </template>
                <p x-show="!data?.brief" class="mt-3 text-tinta/60">Ringkasan muncul setelah ada laporan.</p>
            </div>

            <div class="rounded-2xl border-2 border-garis bg-white p-6">
                <p class="font-display text-2xl font-bold text-papan">Rata-rata 14 hari</p>
                <div class="mt-3 h-44"><canvas x-ref="chart" aria-label="Grafik harga rata-rata 14 hari terakhir" role="img"></canvas></div>
            </div>

            <div class="relative overflow-hidden rounded-2xl bg-papan p-6 text-kapur">
                <p class="font-display text-2xl font-bold text-white">Punya stok untuk dijual?</p>
                @if ($waDigits)
                    <p class="mt-2 text-kapur/80">Kirim <span class="font-bold text-kuning">HARLOK</span> ke <span class="angka font-bold text-white">{{ $waDisplay }}</span>, lalu tulis stok dan harganya seperti chat biasa.</p>
                    <a href="https://wa.me/{{ $waDigits }}?text={{ rawurlencode('HARLOK') }}" class="btn mt-5 bg-kuning text-tinta hover:bg-mentega" target="_blank" rel="noopener">Chat Harlok di WhatsApp</a>
                    @if (config('lapor.simulator.enabled'))
                        <a href="{{ route('simulator.show') }}" class="mt-3 block text-sm font-bold text-kapur underline decoration-kapur/40 underline-offset-4">Atau coba dulu di browser</a>
                    @endif
                @elseif (config('lapor.simulator.enabled'))
                    <p class="mt-2 text-kapur/80">Coba chat dengan Harlok langsung dari browser. Botnya sama dengan yang ada di WhatsApp.</p>
                    <a href="{{ route('simulator.show') }}" class="btn mt-5 bg-kuning text-tinta hover:bg-mentega">Coba chat sekarang</a>
                @endif
            </div>
        </div>
    </section>

    <section class="mx-auto mt-16 max-w-6xl px-4">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="font-display text-[2.2rem] font-extrabold leading-none text-papan">Stok yang sedang dijual</h2>
                <p class="mt-2 max-w-2xl text-tinta/70">Setiap titik adalah stok yang dilaporkan lewat chat. Pengepul yang sudah masuk bisa langsung menawar dari peta.</p>
            </div>
            <div class="flex gap-1 rounded-full border-2 border-tinta/15 bg-white p-1 text-[0.95rem] font-bold">
                <button type="button" @click="goTo('bali')" :class="area === 'bali' ? 'bg-papan text-white' : 'text-tinta/70 hover:text-tinta'" class="rounded-full px-4 py-1.5">Bali</button>
                <button type="button" @click="goTo('diy')" :class="area === 'diy' ? 'bg-papan text-white' : 'text-tinta/70 hover:text-tinta'" class="rounded-full px-4 py-1.5">DI Yogyakarta</button>
            </div>
        </div>

        <div class="mt-5 overflow-hidden rounded-2xl border-2 border-garis">
            <div x-ref="map" class="h-[26rem] w-full sm:h-[30rem]" role="region" aria-label="Peta stok"></div>
        </div>
        <div class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-sm text-tinta/75">
            <span class="flex items-center gap-2"><span class="titik-stok inline-block" style="background:#1E6B40"></span>Dekat rata-rata</span>
            <span class="flex items-center gap-2"><span class="titik-stok inline-block" style="background:#A8321F"></span>Lebih murah dari rata-rata</span>
            <span class="flex items-center gap-2"><span class="titik-stok inline-block" style="background:#C98A00"></span>Lebih mahal dari rata-rata</span>
            <span class="flex items-center gap-2"><span class="titik-stok inline-block" style="background:#8A968D"></span>Pelapor belum diverifikasi</span>
        </div>
    </section>

    <section class="mt-20 bg-daun">
        <div class="mx-auto grid max-w-6xl items-center gap-x-14 gap-y-10 px-4 py-16 lg:grid-cols-[minmax(0,1fr)_minmax(0,25rem)]">
            <div>
                <h2 class="font-display text-[2.4rem] font-extrabold leading-none text-papan">Semuanya lewat chat</h2>
                <div class="mt-4 max-w-xl space-y-3 text-tinta/80">
                    <p>Peternak tidak perlu memasang aplikasi atau mengingat format. Tulis saja seperti biasa, termasuk singkatan dan bahasa Bali. Harlok membaca maksudnya, menanyakan ulang supaya tidak salah catat, lalu membalas dengan harga rata-rata di wilayahnya.</p>
                    <p>Pengepul melihat stok di peta dan mengirim tawaran dari web. Tawarannya sampai ke WhatsApp peternak bersama harga pasar, jadi peternak tahu kalau ditawar terlalu murah. Nomor kedua pihak baru dibuka setelah sepakat.</p>
                    <p>Koordinator kelompok ternak memverifikasi pelapor dan memeriksa harga yang janggal, supaya harga acuan tetap jujur.</p>
                </div>
            </div>

            <figure class="mx-auto w-full max-w-[25rem] rounded-[2rem] border-[10px] border-tinta bg-tinta shadow-[0_24px_50px_-20px_rgb(16_42_28/0.6)]" aria-label="Contoh percakapan">
                <div class="flex items-center gap-3 rounded-t-[1.4rem] bg-papan px-4 py-3 text-white">
                    <span class="grid size-9 place-items-center rounded-full bg-kuning"><x-ikon-komoditas slug="telur" size="26" /></span>
                    <div><p class="font-bold leading-tight">Harlok</p><p class="text-xs text-kapur/65">online</p></div>
                </div>
                <div class="space-y-2 rounded-b-[1.4rem] bg-wa-latar p-3 text-[0.95rem] leading-snug">
                    <p class="ml-auto w-fit max-w-[85%] rounded-lg rounded-tr-none bg-wa px-3 py-2">taluh 2 ton 26rb, siap kirim besok</p>
                    <p class="w-fit max-w-[90%] rounded-lg rounded-tl-none bg-white px-3 py-2">Saya catat begini: Telur Ayam Ras 2.000 kg, Rp26.000/kg, Tabanan. Kalau sudah benar balas <strong>YA</strong>.</p>
                    <p class="ml-auto w-fit rounded-lg rounded-tr-none bg-wa px-3 py-2">ya</p>
                    <p class="w-fit max-w-[90%] rounded-lg rounded-tl-none bg-white px-3 py-2">Tersimpan, stok Anda sekarang terlihat oleh pengepul di Tabanan. Rata-rata di sana Rp25.800/kg, harga Anda sedikit di atasnya.</p>
                    <p class="w-fit max-w-[90%] rounded-lg rounded-tl-none bg-white px-3 py-2">UD Sari Tani menawar 1.000 kg seharga Rp25.500/kg. Balas 1 untuk terima, 2 untuk tolak, atau 3 untuk tawar balik.</p>
                    <p class="ml-auto w-fit rounded-lg rounded-tr-none bg-wa px-3 py-2">26 lah</p>
                    <p class="w-fit max-w-[90%] rounded-lg rounded-tl-none bg-white px-3 py-2">Tawaran balik Rp26.000/kg sudah dikirim ke pembeli.</p>
                </div>
                <figcaption class="sr-only">Contoh percakapan peternak dengan Harlok.</figcaption>
            </figure>
        </div>
    </section>

    <section class="bg-kuning">
        <div class="mx-auto grid max-w-6xl items-center gap-x-14 gap-y-8 px-4 py-16 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
            <div class="max-w-xl">
                <h2 class="font-display text-[2.4rem] font-extrabold leading-none text-tinta">Harga ini boleh dipakai siapa saja</h2>
                <p class="mt-4 text-tinta/85">Dinas, koperasi, dan situs desa bisa memasang widget harga, mengunduh CSV, atau mengambil data lewat API. Yang dibagikan hanya angka gabungan per wilayah. Nama dan nomor peternak tidak pernah ikut keluar.</p>
                <a href="{{ route('api.docs') }}" class="btn mt-6 bg-tinta text-white hover:bg-papan">Lihat cara memakai data</a>
            </div>
            <pre class="overflow-x-auto rounded-2xl border-2 border-tinta bg-white p-5 font-mono text-sm leading-relaxed text-tinta shadow-[6px_6px_0_#1F2B24]"><code>GET /api/v1/harga?komoditas=telur&amp;wilayah=tabanan
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
        </div>
    </section>
</div>
@endsection

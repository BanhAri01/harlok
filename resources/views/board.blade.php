@extends('layouts.app')

@section('content')
<div x-data="papan({ url: @js(route('board.data')), commodity: @js($commodity->slug) })">
    <section class="papan-tulis">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 pb-14 pt-10 lg:grid-cols-[1fr_1.1fr] lg:pt-16">
            <div class="flex flex-col justify-center">
                <h1 class="font-display text-4xl font-extrabold leading-[1.05] tracking-tight text-white sm:text-5xl lg:text-6xl">
                    Tahu harga pasar sebelum menjual.
                </h1>
                <p class="mt-5 max-w-xl text-lg leading-relaxed text-kapur/80">
                    Peternak dan petani cukup mengirim chat WhatsApp seperti <span class="rounded bg-white/10 px-1.5 py-0.5 font-semibold text-kuning">telur 2 ton 26rb</span>.
                    Laporannya langsung menjadi harga rata-rata per wilayah, tampil ke pengepul terdekat, dan bisa dipakai siapa pun lewat API.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    @if ($waNumber)
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $waNumber) }}?text={{ rawurlencode('halo') }}" class="btn-utama px-5 py-3 text-base" target="_blank" rel="noopener">Lapor lewat WhatsApp</a>
                    @endif
                    @if (config('lapor.simulator.enabled'))
                        <a href="{{ route('simulator.show') }}" @class(['px-5 py-3 text-base', 'btn-utama' => ! $waNumber, 'btn border border-white/25 text-kapur hover:bg-white/10' => $waNumber])>Coba chat tanpa WhatsApp</a>
                    @endif
                </div>
                <dl class="mt-10 grid max-w-md grid-cols-3 gap-4 border-t border-white/15 pt-6">
                    <div>
                        <dt class="text-sm text-kapur/60">Pelapor</dt>
                        <dd class="angka mt-1 text-2xl font-bold text-white" x-text="(data?.stats.farmers ?? {{ $stats['farmers'] }}).toLocaleString('id-ID')">{{ number_format($stats['farmers'], 0, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-kapur/60">Laporan hari ini</dt>
                        <dd class="angka mt-1 text-2xl font-bold text-white" x-text="data?.stats.reports_today ?? {{ $stats['reports_today'] }}">{{ $stats['reports_today'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-kapur/60">Transaksi sepakat</dt>
                        <dd class="angka mt-1 text-2xl font-bold text-white" x-text="data?.stats.deals ?? {{ $stats['deals'] }}">{{ $stats['deals'] }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl border border-white/10 bg-black/15 p-5 shadow-2xl shadow-black/20 sm:p-6">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="font-display text-lg font-bold text-white">Papan harga</h2>
                    <div class="ml-auto flex flex-wrap gap-1" role="tablist" aria-label="Komoditas">
                        @foreach ($commodities as $item)
                            <button type="button" role="tab" @click="switchCommodity(@js($item->slug))"
                                :aria-selected="commodity === @js($item->slug)"
                                :class="commodity === @js($item->slug) ? 'bg-kuning text-tinta' : 'text-kapur/75 hover:bg-white/10'"
                                class="rounded-full px-3 py-1 text-xs font-semibold">{{ Str::before($item->name, ' (') }}</button>
                        @endforeach
                    </div>
                </div>
                <p class="mt-1 text-sm text-kapur/60">Rata-rata per kg dari laporan terverifikasi, diperbarui otomatis.</p>

                <div class="mt-4 max-h-[22rem] overflow-y-auto pr-1">
                    <template x-if="data && data.regions.length === 0">
                        <p class="py-10 text-center text-kapur/70">Belum ada laporan minggu ini. Laporan pertama akan muncul di sini.</p>
                    </template>
                    <table class="w-full text-left" x-show="data && data.regions.length">
                        <thead class="sr-only"><tr><th>Wilayah</th><th>Harga rata-rata</th><th>Perubahan</th></tr></thead>
                        <tbody>
                            <template x-for="row in data?.regions ?? []" :key="row.region + row.avg">
                                <tr class="harga-berubah border-b border-dashed border-white/12 last:border-0">
                                    <td class="py-2.5 pr-3">
                                        <span class="font-semibold text-white" x-text="row.region"></span>
                                        <span class="block text-xs text-kapur/55" x-text="row.count + ' laporan · ' + new Date(row.date + 'T00:00:00').toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })"></span>
                                    </td>
                                    <td class="angka py-2.5 text-right text-2xl font-bold text-kuning" x-text="rupiah(row.avg)"></td>
                                    <td class="angka w-24 py-2.5 pl-3 text-right text-sm font-semibold"
                                        :class="row.change > 0 ? 'text-[#7FD49B]' : (row.change < 0 ? 'text-[#FF9C8F]' : 'text-kapur/50')"
                                        x-text="row.change === null ? '—' : (row.change > 0 ? '▲ ' : (row.change < 0 ? '▼ ' : '')) + Math.abs(row.change).toLocaleString('id-ID')"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div class="mt-5 h-36"><canvas x-ref="chart" aria-label="Grafik harga rata-rata 14 hari" role="img"></canvas></div>
            </div>
        </div>
    </section>

    @if ($stats['has_samples'])
        <div class="border-b border-kuning/40 bg-kuning-muda/60">
            <p class="mx-auto max-w-7xl px-4 py-2 text-sm text-tinta/80">
                Sebagian titik dan harga di halaman ini adalah <strong>data contoh</strong> untuk demonstrasi. Laporan asli dari WhatsApp tampil bersamanya dan ditandai terverifikasi atau belum.
            </p>
        </div>
    @endif

    <section class="mx-auto max-w-7xl px-4 pt-12">
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <h2 class="font-display text-3xl font-extrabold tracking-tight">Stok yang sedang ditawarkan</h2>
                <p class="mt-1 max-w-2xl text-tinta/70">Setiap titik adalah laporan stok dari chat WhatsApp. Laporan baru berkedip kuning saat masuk.</p>
            </div>
            <div class="ml-auto flex gap-1 rounded-lg border border-garis bg-white p-1 text-sm font-semibold">
                <button type="button" @click="goTo('bali')" :class="area === 'bali' ? 'bg-papan text-kapur' : 'text-tinta/70 hover:text-tinta'" class="rounded-md px-3 py-1.5">Bali</button>
                <button type="button" @click="goTo('diy')" :class="area === 'diy' ? 'bg-papan text-kapur' : 'text-tinta/70 hover:text-tinta'" class="rounded-md px-3 py-1.5">DI Yogyakarta</button>
            </div>
        </div>

        <div class="mt-5 overflow-hidden rounded-2xl border border-garis bg-white">
            <div x-ref="map" class="h-[28rem] w-full sm:h-[32rem]" role="region" aria-label="Peta stok"></div>
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-garis px-4 py-3 text-sm text-tinta/75">
                <span class="flex items-center gap-2"><span class="titik-stok inline-block" style="background:#1E7A46"></span>Sekitar rata-rata</span>
                <span class="flex items-center gap-2"><span class="titik-stok inline-block" style="background:#B42318"></span>Di bawah rata-rata &gt;5%</span>
                <span class="flex items-center gap-2"><span class="titik-stok inline-block" style="background:#C98A00"></span>Di atas rata-rata &gt;5%</span>
                <span class="flex items-center gap-2"><span class="titik-stok inline-block" style="background:#8A968D"></span>Pelapor belum terverifikasi</span>
                <span class="ml-auto text-tinta/55" x-show="error" x-text="error"></span>
            </div>
        </div>
    </section>

    <section class="mx-auto mt-16 max-w-7xl px-4">
        <h2 class="font-display text-3xl font-extrabold tracking-tight">Dari chat sampai sepakat</h2>
        <ol class="mt-6 grid gap-4 md:grid-cols-3">
            <li class="panel p-5">
                <span class="angka text-3xl font-extrabold text-kuning">1</span>
                <h3 class="mt-2 font-display text-xl font-bold">Peternak mengirim chat</h3>
                <p class="mt-2 text-tinta/75">Tanpa aplikasi baru. Pesan seperti <em>"telur 20 peti 390rb/peti"</em> dibaca otomatis, dikonfirmasi ulang, lalu disimpan. Peternak langsung dibalas harga rata-rata wilayahnya.</p>
            </li>
            <li class="panel p-5">
                <span class="angka text-3xl font-extrabold text-kuning">2</span>
                <h3 class="mt-2 font-display text-xl font-bold">Pengepul menawar dari web</h3>
                <p class="mt-2 text-tinta/75">Stok tampil di peta. Tawaran dikirim ke WhatsApp peternak, lengkap dengan peringatan bila harganya jauh di bawah pasar.</p>
            </li>
            <li class="panel p-5">
                <span class="angka text-3xl font-extrabold text-kuning">3</span>
                <h3 class="mt-2 font-display text-xl font-bold">Peternak membalas 1, 2, atau 3</h3>
                <p class="mt-2 text-tinta/75">Terima, tolak, atau tawar balik cukup dengan satu angka. Setelah sepakat, kontak kedua pihak dibagikan. Sebelumnya nomor tetap dirahasiakan.</p>
            </li>
        </ol>
    </section>

    <section class="mx-auto mt-16 max-w-7xl px-4">
        <div class="papan-tulis grid gap-6 rounded-2xl p-6 sm:p-8 lg:grid-cols-[1fr_1.2fr]">
            <div>
                <h2 class="font-display text-3xl font-extrabold tracking-tight text-white">Data harga terbuka untuk ekosistem</h2>
                <p class="mt-3 text-kapur/80">Dinas, koperasi, dan aplikasi lain bisa mengambil harga rata-rata per wilayah lewat API ber-token. Yang dibagikan hanya angka gabungan — nama dan nomor peternak tidak pernah keluar.</p>
                <a href="{{ route('api.docs') }}" class="btn-utama mt-6">Lihat dokumentasi API</a>
            </div>
            <pre class="overflow-x-auto rounded-xl bg-black/30 p-4 text-sm leading-relaxed text-kapur"><code>GET /api/v1/harga?komoditas=telur&amp;wilayah=tabanan
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

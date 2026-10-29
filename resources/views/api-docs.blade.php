@extends('layouts.app', ['title' => 'Data Terbuka & API'])

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10">
    <h1 class="judul-halaman">Data terbuka dan API harga</h1>
    <p class="mt-3 text-lg text-tinta/75">Untuk dinas, koperasi, peneliti, dan aplikasi lain yang ingin memakai harga pasar dari laporan peternak. Semua jalur hanya mengeluarkan angka gabungan per wilayah. Nama dan nomor peternak tidak pernah dibagikan.</p>

    <section class="mt-10" x-data="widgetMaker({ baseUrl: @js(route('widget.prices')), commodity: @js($commodities->first()?->slug) })">
        <h2 class="judul-bagian">Tanpa token: widget dan data terbuka</h2>
        <p class="mt-2 text-tinta/75">Situs koperasi, desa, atau dinas bisa langsung menampilkan harga pasar terbaru tanpa pemrograman. Widget memperbarui diri setiap 5 menit.</p>

        <div class="mt-5 grid gap-6 lg:grid-cols-[1fr_1.1fr]">
            <div class="min-w-0 space-y-4">
                <div>
                    <label class="label" for="w-komoditas">Komoditas</label>
                    <select id="w-komoditas" class="input" x-model="commodity">
                        @foreach ($commodities as $commodity)
                            <option value="{{ $commodity->slug }}">{{ $commodity->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="w-wilayah">Wilayah</label>
                    <select id="w-wilayah" class="input" x-model="region">
                        <option value="">Semua wilayah</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region->slug }}">{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="w-tema">Warna</label>
                    <select id="w-tema" class="input" x-model="theme">
                        <option value="">Terang</option>
                        <option value="gelap">Papan hijau</option>
                    </select>
                </div>
                <div>
                    <p class="label">Kode untuk ditempel</p>
                    <pre class="kode overflow-x-auto p-4 text-xs leading-relaxed"><code x-text="code"></code></pre>
                    <button type="button" class="btn-gelap mt-2" @click="copy()" x-text="copied ? 'Kode tersalin' : 'Salin kode'">Salin kode</button>
                </div>
            </div>
            <div class="min-w-0">
                <p class="label">Pratinjau</p>
                <iframe :src="src" :style="`height: ${height}px`" title="Pratinjau widget harga" class="w-full rounded-xl border-0 bg-white" loading="lazy"></iframe>
            </div>
        </div>

        <h3 class="judul-bagian mt-8 !text-[1.35rem]">Unduh data harga harian (CSV)</h3>
        <p class="mt-2 text-tinta/75">Rata-rata, median, terendah, tertinggi, dan jumlah laporan per hari per wilayah. Ubah parameter <code>hari</code> di alamat unduhan (maksimal 366).</p>
        <ul class="mt-3 flex flex-wrap gap-2">
            <li><a class="btn-garis" href="{{ route('open-data.csv', ['hari' => 90]) }}">Semua komoditas, 90 hari</a></li>
            @foreach ($commodities as $commodity)
                <li><a class="btn-garis" href="{{ route('open-data.csv', ['komoditas' => $commodity->slug, 'hari' => 90]) }}">{{ $commodity->name }}</a></li>
            @endforeach
        </ul>
    </section>

    <section class="mt-10 space-y-3">
        <h2 class="judul-bagian">Akses API (dengan token)</h2>
        <ul class="list-disc space-y-1 pl-5 text-tinta/80">
            <li>Token diberikan koordinator kepada mitra yang terdaftar, berlaku 6 bulan, dan bisa dicabut kapan saja.</li>
            <li>Kirim token di header <code class="rounded bg-white px-1.5 py-0.5">Authorization: Bearer &lt;token&gt;</code>.</li>
            <li>Batas 60 permintaan per menit per token. Lebih dari itu dijawab <code class="rounded bg-white px-1.5 py-0.5">429</code>.</li>
            <li>Semua harga dalam rupiah per kilogram, dihitung dari laporan peternak terverifikasi selama 7 hari terakhir.</li>
        </ul>
    </section>

    @foreach ([
        ['GET /api/v1/harga?komoditas=telur&wilayah=tabanan', 'Harga terbaru per wilayah. Parameter wilayah boleh dikosongkan untuk semua wilayah.', '{
  "komoditas": "telur",
  "satuan": "rupiah/kg",
  "sumber": "Laporan peternak terverifikasi, 7 hari terakhir",
  "data": [{
    "wilayah": "tabanan",
    "nama_wilayah": "Tabanan",
    "tanggal": "2026-10-29",
    "rata_rata": 26400,
    "terendah": 25500,
    "tertinggi": 27200,
    "jumlah_laporan": 18,
    "perubahan": 150
  }]
}'],
        ['GET /api/v1/harga/riwayat?komoditas=telur&wilayah=tabanan&hari=30', 'Riwayat harga harian satu wilayah, maksimal 90 hari.', '{
  "komoditas": "telur",
  "wilayah": "tabanan",
  "data": [
    { "tanggal": "2026-10-28", "rata_rata": 26250, "median": 26200,
      "terendah": 25500, "tertinggi": 27000, "jumlah_laporan": 15 }
  ]
}'],
        ['GET /api/v1/komoditas', 'Daftar komoditas yang bisa dipakai di parameter komoditas.', null],
        ['GET /api/v1/wilayah', 'Daftar wilayah beserta koordinat pusatnya.', null],
    ] as [$endpoint, $description, $example])
        <section class="mt-10">
            <h2 class="text-xl font-bold text-papan"><code>{{ $endpoint }}</code></h2>
            <p class="mt-2 text-tinta/75">{{ $description }}</p>
            @if ($example)
                <pre class="kode mt-3 overflow-x-auto p-4 text-sm leading-relaxed"><code>{{ $example }}</code></pre>
            @endif
        </section>
    @endforeach

    <section class="mt-10">
        <h2 class="judul-bagian">Kode komoditas</h2>
        <ul class="mt-3 flex flex-wrap gap-2">
            @foreach ($commodities as $commodity)
                <li class="flex items-center gap-2 rounded-full border-2 border-garis bg-white py-1 pl-1 pr-3 text-sm"><x-ikon-komoditas :slug="$commodity->slug" size="22" /><code>{{ $commodity->slug }}</code> {{ $commodity->name }}</li>
            @endforeach
        </ul>
    </section>

    <section class="mt-10">
        <h2 class="judul-bagian">Contoh pemanggilan</h2>
        <pre class="kode mt-3 overflow-x-auto p-4 text-sm"><code>curl -H "Authorization: Bearer TOKEN_ANDA" \
  "{{ url('/api/v1/harga') }}?komoditas=telur"</code></pre>
    </section>
</div>
@endsection

@extends('layouts.app', ['title' => 'API Data Harga'])

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10">
    <h1 class="font-display text-4xl font-extrabold tracking-tight">API data harga</h1>
    <p class="mt-3 text-lg text-tinta/75">Untuk dinas, koperasi, peneliti, dan aplikasi lain yang ingin memakai harga pasar dari laporan peternak. API hanya membaca data gabungan per wilayah. Nama dan nomor peternak tidak pernah dibagikan.</p>

    <section class="mt-10 space-y-3">
        <h2 class="font-display text-2xl font-bold">Akses</h2>
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
            <h2 class="font-display text-xl font-bold"><code>{{ $endpoint }}</code></h2>
            <p class="mt-2 text-tinta/75">{{ $description }}</p>
            @if ($example)
                <pre class="papan-tulis mt-3 overflow-x-auto rounded-xl p-4 text-sm leading-relaxed"><code>{{ $example }}</code></pre>
            @endif
        </section>
    @endforeach

    <section class="mt-10">
        <h2 class="font-display text-2xl font-bold">Kode komoditas</h2>
        <ul class="mt-3 flex flex-wrap gap-2">
            @foreach ($commodities as $commodity)
                <li class="rounded-full border border-garis bg-white px-3 py-1 text-sm"><code>{{ $commodity->slug }}</code> — {{ $commodity->name }}</li>
            @endforeach
        </ul>
    </section>

    <section class="mt-10">
        <h2 class="font-display text-2xl font-bold">Contoh pemanggilan</h2>
        <pre class="papan-tulis mt-3 overflow-x-auto rounded-xl p-4 text-sm"><code>curl -H "Authorization: Bearer TOKEN_ANDA" \
  "{{ url('/api/v1/harga') }}?komoditas=telur"</code></pre>
    </section>
</div>
@endsection

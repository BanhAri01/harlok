@extends('layouts.app', ['title' => 'Koordinator'])

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8">
    @include('admin._nav', ['heading' => 'Ringkasan koordinator', 'sub' => 'Verifikasi pelapor dan cek laporan yang harganya janggal.'])

    <dl class="mt-6 grid grid-cols-2 gap-3 md:grid-cols-6">
        @foreach ([
            'Peternak' => $counts['farmers'],
            'Belum terverifikasi' => $counts['unverified'],
            'Stok tersedia' => $counts['available'],
            'Pesan masuk hari ini' => $counts['inbound_today'],
            'WA terkirim hari ini' => $counts['outbound_today'].' / '.$limit,
            'WA gagal hari ini' => $counts['failed_today'],
        ] as $label => $value)
            <div @class(['rounded-2xl p-4', 'papan-pasar !border-[6px]' => $loop->first, 'border-2 border-garis bg-white' => ! $loop->first])>
                <dt @class(['text-sm', 'text-kapur/70' => $loop->first, 'text-tinta/60' => ! $loop->first])>{{ $label }}</dt>
                <dd @class(['mt-1 font-display text-[2.2rem] font-extrabold leading-none', 'text-kuning' => $loop->first, 'text-papan' => ! $loop->first])>{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    <section class="mt-6 rounded-2xl bg-kuning-muda p-6">
        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <h2 class="judul-bagian">Asisten AI, 7 hari terakhir</h2>
            <span @class(['rounded-full px-2.5 py-0.5 text-xs font-semibold', 'bg-papan text-white' => $ai['active'], 'bg-tinta/10 text-tinta/60' => ! $ai['active']])>
                {{ $ai['active'] ? 'Aktif: '.$ai['provider'] : 'Belum aktif, bot memakai pembaca aturan' }}
            </span>
        </div>
        <dl class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4">
            <div><dt class="text-sm text-tinta/60">Panggilan AI</dt><dd class="mt-1 font-display text-[2rem] font-extrabold leading-none text-papan">{{ number_format($ai['total'], 0, ',', '.') }}</dd></div>
            <div><dt class="text-sm text-tinta/60">Berhasil</dt><dd class="mt-1 font-display text-[2rem] font-extrabold leading-none text-papan">{{ $ai['total'] ? round($ai['ok'] / $ai['total'] * 100).'%' : '–' }}</dd></div>
            <div><dt class="text-sm text-tinta/60">Ditangani cadangan</dt><dd class="mt-1 font-display text-[2rem] font-extrabold leading-none text-papan">{{ number_format($ai['fallback'], 0, ',', '.') }}</dd></div>
            <div><dt class="text-sm text-tinta/60">Rata-rata waktu jawab</dt><dd class="mt-1 font-display text-[2rem] font-extrabold leading-none text-papan">{{ $ai['latency'] ? number_format($ai['latency'] / 1000, 1, ',', '.').' dtk' : '–' }}</dd></div>
        </dl>
        @if ($ai['purposes'])
            <p class="mt-3 text-sm text-tinta/60">
                @foreach ($ai['purposes'] as $purpose => $total)
                    {{ ['asisten' => 'Memahami chat', 'penjaga_harga' => 'Penjaga harga', 'ringkasan_pasar' => 'Ringkasan pasar'][$purpose] ?? $purpose }}: {{ $total }}@if (! $loop->last), @endif
                @endforeach
            </p>
        @endif
        <p class="mt-2 text-xs text-tinta/50">Yang dicatat hanya jenis, status, dan lama panggilan. Isi pesan, nama, dan nomor peternak tidak disimpan di log ini dan tidak dikirim ke AI.</p>
    </section>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="panel">
            <h2 class="judul-bagian border-b-2 border-garis px-5 py-3">Laporan janggal ({{ $flagged->count() }})</h2>
            <ul class="divide-y divide-garis">
                @forelse ($flagged as $report)
                    <li class="px-5 py-4">
                        <p class="font-semibold">{{ $report->farmer->name }}, {{ $report->region->name }}</p>
                        <p class="text-sm text-tinta/70">{{ $report->commodity->name }} {{ \App\Support\Rupiah::kg($report->quantity_kg) }}, <strong>{{ \App\Support\Rupiah::format($report->price_per_kg) }}/kg</strong></p>
                        <p class="text-sm text-turun">{{ $report->flag_reason }}</p>
                        @if ($report->ai_note)
                            <p class="mt-2 rounded-xl bg-kuning-muda px-3 py-2 text-sm"><span class="font-semibold">Penilaian AI, {{ str_replace('_', ' ', $report->ai_verdict) }}:</span> {{ $report->ai_note }}</p>
                        @endif
                        <p class="mt-1 text-xs text-tinta/50">Pesan asli "{{ $report->raw_text }}", {{ $report->confirmed_at?->diffForHumans() }}</p>
                        <div class="mt-3 flex gap-2">
                            <form method="POST" action="{{ route('admin.reports.approve', $report) }}">@csrf<button class="btn-gelap">Tampilkan</button></form>
                            <form method="POST" action="{{ route('admin.reports.reject', $report) }}">@csrf<button class="btn-garis">Tolak</button></form>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-tinta/60">Tidak ada laporan janggal.</li>
                @endforelse
            </ul>
        </section>

        <section class="panel">
            <h2 class="judul-bagian border-b-2 border-garis px-5 py-3">Menunggu verifikasi</h2>
            <ul class="divide-y divide-garis">
                @forelse ($unverified as $farmer)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold">{{ $farmer->name }}</p>
                            <p class="text-sm text-tinta/60">{{ $farmer->region->name }}, {{ \App\Support\Phone::display($farmer->phone) }}, daftar {{ $farmer->created_at->diffForHumans() }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.farmers.verify', $farmer) }}">
                            @csrf
                            <input type="hidden" name="verified" value="1">
                            <button class="btn-gelap">Verifikasi</button>
                        </form>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-tinta/60">Semua pelapor WhatsApp sudah terverifikasi.</li>
                @endforelse
            </ul>
            <a href="{{ route('admin.farmers', ['status' => 'belum']) }}" class="block border-t-2 border-garis px-5 py-3 text-sm font-bold text-papan hover:bg-daun">Lihat semua peternak</a>
        </section>
    </div>
</div>
@endsection

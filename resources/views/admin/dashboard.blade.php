@extends('layouts.app', ['title' => 'Koordinator'])

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8">
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
            <div class="panel p-4">
                <dt class="text-sm text-tinta/60">{{ $label }}</dt>
                <dd class="angka mt-1 text-2xl font-bold">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="panel">
            <h2 class="border-b border-garis px-5 py-3 font-display text-lg font-bold">Laporan janggal ({{ $flagged->count() }})</h2>
            <ul class="divide-y divide-garis">
                @forelse ($flagged as $report)
                    <li class="px-5 py-4">
                        <p class="font-semibold">{{ $report->farmer->name }} · {{ $report->region->name }}</p>
                        <p class="text-sm text-tinta/70">{{ $report->commodity->name }} {{ \App\Support\Rupiah::kg($report->quantity_kg) }} @ <strong>{{ \App\Support\Rupiah::format($report->price_per_kg) }}/kg</strong></p>
                        <p class="text-sm text-turun">{{ $report->flag_reason }}</p>
                        <p class="mt-1 text-xs text-tinta/50">Pesan asli: "{{ $report->raw_text }}" · {{ $report->confirmed_at?->diffForHumans() }}</p>
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
            <h2 class="border-b border-garis px-5 py-3 font-display text-lg font-bold">Menunggu verifikasi</h2>
            <ul class="divide-y divide-garis">
                @forelse ($unverified as $farmer)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold">{{ $farmer->name }}</p>
                            <p class="text-sm text-tinta/60">{{ $farmer->region->name }} · {{ \App\Support\Phone::display($farmer->phone) }} · daftar {{ $farmer->created_at->diffForHumans() }}</p>
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
            <a href="{{ route('admin.farmers', ['status' => 'belum']) }}" class="block border-t border-garis px-5 py-3 text-sm font-semibold text-papan">Lihat semua peternak</a>
        </section>
    </div>
</div>
@endsection

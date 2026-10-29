@extends('layouts.app', ['title' => 'Peternak'])

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8">
    @include('admin._nav', ['heading' => 'Peternak', 'sub' => 'Hanya laporan dari peternak terverifikasi yang dihitung dalam harga rata-rata.'])

    <form method="GET" class="mt-6 flex flex-wrap gap-2">
        <label for="q" class="sr-only">Cari</label>
        <input id="q" name="q" value="{{ $search }}" placeholder="Cari nama atau nomor" class="input max-w-xs">
        <select name="status" class="input w-auto" aria-label="Status">
            <option value="">Semua</option>
            <option value="belum" @selected(request('status') === 'belum')>Belum terverifikasi</option>
        </select>
        <button class="btn-gelap">Cari</button>
    </form>

    <div class="panel mt-4 overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b-2 border-garis bg-daun text-tinta/70">
                <tr><th class="px-4 py-3 font-semibold">Nama</th><th class="px-4 py-3 font-semibold">Nomor</th><th class="px-4 py-3 font-semibold">Wilayah</th><th class="px-4 py-3 font-semibold">Komoditas</th><th class="px-4 py-3 font-semibold">Laporan</th><th class="px-4 py-3 font-semibold">Asal</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-garis">
                @foreach ($farmers as $farmer)
                    <tr>
                        <td class="px-4 py-3 font-semibold">{{ $farmer->name }}</td>
                        <td class="px-4 py-3">{{ \App\Support\Phone::display($farmer->phone) }}</td>
                        <td class="px-4 py-3">{{ $farmer->region->name }}</td>
                        <td class="px-4 py-3">{{ $farmer->commodity?->name ?? '—' }}</td>
                        <td class="angka px-4 py-3">{{ $farmer->reports_count }}</td>
                        <td class="px-4 py-3">{{ ['wa' => 'WhatsApp', 'simulator' => 'Simulator', 'contoh' => 'Data contoh'][$farmer->channel] ?? $farmer->channel }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($farmer->channel === 'simulator')
                                <span class="text-tinta/50">Tidak dapat diverifikasi</span>
                            @else
                                <form method="POST" action="{{ route('admin.farmers.verify', $farmer) }}">
                                    @csrf
                                    <input type="hidden" name="verified" value="{{ $farmer->isVerified() ? 0 : 1 }}">
                                    <button @class(['btn-garis' => $farmer->isVerified(), 'btn-gelap' => ! $farmer->isVerified()])>{{ $farmer->isVerified() ? 'Cabut verifikasi' : 'Verifikasi' }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $farmers->links() }}</div>
</div>
@endsection

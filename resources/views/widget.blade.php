<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="300">
    <meta name="robots" content="noindex">
    <title>Harga {{ $commodity->name }} | {{ config('app.name') }}</title>
    <style>
        :root { --papan: #1D4A34; --kapur: #F3F6F1; --kuning: #F5B700; --tinta: #1F2B24; --kertas: #FCFCF9; --garis: #DCE2D8; --naik: #1E6B40; --turun: #A8321F; }
        * { box-sizing: border-box; margin: 0; }
        body { font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif; background: #fff; color: var(--tinta); font-size: 14px; }
        .kotak { border: 1px solid var(--garis); border-radius: 12px; overflow: hidden; }
        .kepala { background: var(--papan); color: var(--kapur); padding: 12px 14px; display: flex; justify-content: space-between; align-items: baseline; gap: 8px; }
        .kepala h1 { font-size: 16px; font-weight: 800; letter-spacing: -0.01em; }
        .kepala span { font-size: 12px; opacity: .75; }
        ul { list-style: none; padding: 0; }
        li { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; padding: 9px 14px; border-top: 1px solid var(--garis); }
        li:first-child { border-top: 0; }
        .harga { font-weight: 800; font-variant-numeric: tabular-nums; font-size: 15px; }
        .naik { color: var(--naik); } .turun { color: var(--turun); }
        .ubah { font-size: 12px; font-weight: 600; margin-left: 6px; }
        .lama { font-size: 11px; opacity: .6; }
        .kaki { display: flex; justify-content: space-between; gap: 8px; padding: 8px 14px; font-size: 11px; background: var(--kertas); border-top: 1px solid var(--garis); }
        .kaki a { color: var(--papan); font-weight: 700; text-decoration: none; }
        .kosong { padding: 18px 14px; opacity: .7; }
        body.gelap { background: var(--papan); color: var(--kapur); }
        body.gelap .kotak, body.gelap li, body.gelap .kaki { border-color: rgb(255 255 255 / .12); }
        body.gelap .kaki { background: #163A29; }
        body.gelap .kaki a { color: var(--kuning); }
        body.gelap .harga { color: var(--kuning); }
        body.gelap .naik { color: #7FD8A2; } body.gelap .turun { color: #FF9C8F; }
    </style>
</head>
<body @class(['gelap' => $dark])>
    <div class="kotak">
        <div class="kepala">
            <h1>Harga {{ $commodity->name }}</h1>
            <span>{{ $region?->name ?? 'per wilayah' }} · Rp/kg</span>
        </div>

        @if (empty($rows))
            <p class="kosong">Belum ada laporan harga minggu ini.</p>
        @else
            <ul>
                @foreach ($rows as $row)
                    <li>
                        <span>{{ $row['region'] }}</span>
                        <span>
                            <span class="harga">{{ \App\Support\Rupiah::format($row['avg']) }}</span>
                            @if ($row['change'])
                                <span @class(['ubah', 'naik' => $row['change'] > 0, 'turun' => $row['change'] < 0])>{{ $row['change'] > 0 ? '▲' : '▼' }}{{ number_format(abs($row['change']), 0, ',', '.') }}</span>
                            @endif
                            @unless (\Illuminate\Support\Carbon::parse($row['date'])->isToday())
                                <span class="lama">{{ \Illuminate\Support\Carbon::parse($row['date'])->translatedFormat('j M') }}</span>
                            @endunless
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="kaki">
            <span>{{ $hasSamples ? 'Memuat data contoh.' : 'Dari laporan peternak terverifikasi.' }}</span>
            <a href="{{ route('home', ['komoditas' => $commodity->slug]) }}" target="_blank" rel="noopener">Harlok</a>
        </div>
    </div>
</body>
</html>

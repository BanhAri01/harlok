<?php

use App\Models\Commodity;
use App\Models\Farmer;
use App\Models\StockReport;
use App\Models\User;
use App\Services\Messenger;
use App\Services\PriceBoard;
use App\Support\Rupiah;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('lapor:ringkasan-pagi', function (PriceBoard $prices, Messenger $messenger) {
    $sent = 0;

    Farmer::query()
        ->with('commodity', 'region')
        ->where('digest_opt_in', true)
        ->whereNotNull('commodity_id')
        ->where('channel', 'wa')
        ->chunkById(100, function ($farmers) use ($prices, $messenger, &$sent) {
            $summaries = [];

            foreach ($farmers as $farmer) {
                $summaries[$farmer->commodity_id] ??= $prices->regionSummary($farmer->commodity);
                $rows = $summaries[$farmer->commodity_id];

                if ($rows->isEmpty()) {
                    continue;
                }

                $own = $rows->first(fn ($row) => $row['region']->id === $farmer->region_id);
                $lines = $rows->take(5)->map(fn ($row) => "• {$row['region']->name}: ".Rupiah::format($row['avg']))->implode("\n");

                $messenger->send($farmer->phone, "🌅 Selamat pagi {$farmer->firstName()}!\n"
                    ."Harga {$farmer->commodity->name} (per kg):\n{$lines}"
                    .($own ? "\n\n📍 Wilayah Anda ({$farmer->region->name}): *".Rupiah::format($own['avg']).'*' : '')
                    ."\n\nKetik *STOP* untuk berhenti.");
                $sent++;
            }
        });

    $this->info("Ringkasan terkirim: {$sent}");
})->purpose('Kirim ringkasan harga pagi ke peternak yang berlangganan');

Artisan::command('lapor:rapikan', function () {
    $expiredPending = StockReport::query()->where('status', StockReport::PENDING)->where('expires_at', '<', now())
        ->update(['status' => StockReport::CANCELLED, 'closed_at' => now()]);
    $expiredStock = StockReport::query()->where('status', StockReport::CONFIRMED)->where('expires_at', '<', now())
        ->update(['status' => StockReport::CLOSED, 'closed_at' => now()]);

    $this->info("Laporan menunggu kedaluwarsa: {$expiredPending}, stok ditutup: {$expiredStock}");
})->purpose('Tutup laporan yang kedaluwarsa');

Artisan::command('lapor:admin {email} {name=Admin}', function (string $email, string $name) {
    $password = Illuminate\Support\Str::password(14, symbols: false);

    User::query()->updateOrCreate(['email' => $email], [
        'name' => $name,
        'password' => $password,
        'role' => User::ADMIN,
        'is_active' => true,
    ]);

    $this->info("Admin {$email} siap. Kata sandi: {$password}");
})->purpose('Buat atau atur ulang akun admin');

Artisan::command('lapor:hapus-contoh', function () {
    $farmers = Farmer::query()->where('channel', 'contoh')->pluck('id');
    StockReport::query()->whereIn('farmer_id', $farmers)->delete();
    Farmer::query()->whereIn('id', $farmers)->delete();
    App\Models\DailyPrice::query()->delete();

    $board = app(PriceBoard::class);
    StockReport::query()->whereIn('status', StockReport::PRICED)->whereNotNull('confirmed_at')
        ->get(['commodity_id', 'region_id', 'confirmed_at'])
        ->unique(fn ($report) => $report->commodity_id.'-'.$report->region_id.'-'.$report->confirmed_at->toDateString())
        ->each(fn ($report) => $board->recompute($report->commodity_id, $report->region_id, $report->confirmed_at));

    $this->info('Data contoh dihapus: '.$farmers->count().' peternak.');
})->purpose('Hapus seluruh data contoh sebelum dipakai sungguhan');

Schedule::command('lapor:ringkasan-pagi')->dailyAt('06:00');
Schedule::command('lapor:rapikan')->everyThirtyMinutes();

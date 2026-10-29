<?php

namespace App\Services;

use App\Chat\ParsedReport;
use App\Models\AuditLog;
use App\Models\Farmer;
use App\Models\StockReport;
use App\Models\User;
use App\Support\Rupiah;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function __construct(private PriceBoard $prices, private Messenger $messenger, private AiPriceGuard $guard) {}

    public function createPending(Farmer $farmer, ParsedReport $parsed, string $rawText): StockReport
    {
        StockReport::query()
            ->where('farmer_id', $farmer->id)
            ->where('status', StockReport::PENDING)
            ->update(['status' => StockReport::CANCELLED, 'closed_at' => now()]);

        $region = $parsed->region ?? $farmer->region;
        [$lat, $lng] = $this->position($farmer, $region->id === $farmer->region_id ? null : $region);

        return StockReport::query()->create([
            'farmer_id' => $farmer->id,
            'commodity_id' => $parsed->commodity->id,
            'region_id' => $region->id,
            'quantity_kg' => $parsed->quantityKg,
            'price_per_kg' => $parsed->pricePerKg,
            'status' => StockReport::PENDING,
            'parsed_by' => $parsed->source,
            'raw_text' => mb_substr($rawText, 0, 500),
            'lat' => $lat,
            'lng' => $lng,
            'expires_at' => now()->addMinutes((int) config('lapor.market.confirm_minutes')),
        ]);
    }

    public function confirm(StockReport $report): array
    {
        $market = $this->prices->baselineFor($report);
        $outlier = (int) config('lapor.market.outlier_percent');
        $deviation = $market ? ($report->price_per_kg - $market['price']) / $market['price'] * 100 : null;

        DB::transaction(function () use ($report, $deviation, $outlier, $market) {
            StockReport::query()
                ->where('farmer_id', $report->farmer_id)
                ->where('commodity_id', $report->commodity_id)
                ->where('status', StockReport::CONFIRMED)
                ->whereKeyNot($report->id)
                ->update(['status' => StockReport::CLOSED, 'closed_at' => now()]);

            StockReport::query()
                ->where('farmer_id', $report->farmer_id)
                ->where('commodity_id', $report->commodity_id)
                ->where('status', StockReport::FLAGGED)
                ->whereKeyNot($report->id)
                ->update(['status' => StockReport::CANCELLED, 'closed_at' => now()]);

            $flagged = $deviation !== null && abs($deviation) > $outlier;

            $report->forceFill([
                'status' => $flagged ? StockReport::FLAGGED : StockReport::CONFIRMED,
                'flag_reason' => $flagged ? 'Selisih '.Rupiah::percent($deviation).' dari rata-rata '.Rupiah::format($market['price']) : null,
                'confirmed_at' => now(),
                'expires_at' => now()->addDays((int) config('lapor.market.report_active_days')),
            ])->save();
        });

        AuditLog::record('wa:'.$report->farmer->phone, 'report.'.$report->status, $report, [
            'price' => $report->price_per_kg,
            'qty' => $report->quantity_kg,
        ]);

        if ($report->status === StockReport::CONFIRMED) {
            $this->prices->recomputeFor($report);
            $this->notifyBuyers($report);
        }

        return [
            'market' => $market,
            'deviation' => $deviation,
            'review' => $report->status === StockReport::FLAGGED ? $this->guard->review($report, $market) : null,
        ];
    }

    public function approve(StockReport $report, User $admin): void
    {
        $report->forceFill([
            'status' => StockReport::CONFIRMED,
            'flag_reason' => null,
            'expires_at' => now()->addDays((int) config('lapor.market.report_active_days')),
        ])->save();

        $this->prices->recomputeFor($report);
        AuditLog::record('user:'.$admin->id, 'report.approved', $report);

        $this->messenger->send($report->farmer->phone, '✅ Laporan '.$report->commodity->name.' '.Rupiah::format($report->price_per_kg).'/kg sudah dicek koordinator dan kini tampil ke pengepul.');
        $this->notifyBuyers($report);
    }

    public function reject(StockReport $report, User $admin): void
    {
        $report->forceFill(['status' => StockReport::REJECTED, 'closed_at' => now()])->save();
        AuditLog::record('user:'.$admin->id, 'report.rejected', $report);

        $this->messenger->send($report->farmer->phone, 'ℹ️ Laporan '.$report->commodity->name.' '.Rupiah::format($report->price_per_kg).'/kg tidak ditampilkan karena harganya jauh dari harga pasar. Bila benar, silakan lapor ulang atau hubungi koordinator.');
    }

    public function close(StockReport $report, string $actor): void
    {
        $report->forceFill(['status' => StockReport::CLOSED, 'closed_at' => now()])->save();
        AuditLog::record($actor, 'report.closed', $report);
    }

    public function position(Farmer $farmer, $overrideRegion = null): array
    {
        $region = $overrideRegion ?? $farmer->region;

        if (! $overrideRegion && $farmer->lat && $farmer->lng) {
            return [$farmer->lat, $farmer->lng];
        }

        $seed = hexdec(substr(hash_hmac('sha256', $farmer->id.'|'.$region->id, (string) config('app.key')), 0, 8));
        $latOffset = (($seed % 1000) / 1000 - 0.5) * 0.07;
        $lngOffset = ((intdiv($seed, 1000) % 1000) / 1000 - 0.5) * 0.07;

        return [round($region->lat + $latOffset, 7), round($region->lng + $lngOffset, 7)];
    }

    private function notifyBuyers(StockReport $report): void
    {
        $farmer = $report->farmer;

        if ($farmer->channel !== 'wa' || ! $farmer->isVerified()) {
            return;
        }

        $cooldown = 'notif-pengepul:'.$farmer->id.':'.$report->commodity_id;
        if (! Cache::add($cooldown, true, now()->addMinutes((int) config('lapor.market.notify_cooldown_minutes')))) {
            return;
        }

        $buyers = User::query()
            ->where('role', User::BUYER)
            ->where('is_active', true)
            ->where('notify_wa', true)
            ->where('region_id', $report->region_id)
            ->whereNotNull('phone')
            ->limit((int) config('lapor.market.nearby_buyers_limit'))
            ->get(['id', 'phone']);

        $body = "🆕 Stok baru di {$report->region->name}\n"
            ."{$report->commodity->name} ".Rupiah::kg($report->quantity_kg).' @ '.Rupiah::format($report->price_per_kg)."/kg\n"
            .'Buka: '.route('buyer.dashboard', ['report' => $report->id]);

        foreach ($buyers as $buyer) {
            $this->messenger->send($buyer->phone, $body);
        }
    }
}

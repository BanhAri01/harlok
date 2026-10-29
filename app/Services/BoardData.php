<?php

namespace App\Services;

use App\Models\Commodity;
use App\Models\Farmer;
use App\Models\Offer;
use App\Models\StockReport;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class BoardData
{
    public function __construct(private PriceBoard $prices) {}

    public function commodity(?string $slug): Commodity
    {
        $commodities = Commodity::active();

        return $commodities->firstWhere('slug', $slug) ?? $commodities->first();
    }

    public function payload(Commodity $commodity, ?User $buyer = null): array
    {
        $reports = StockReport::query()
            ->with('region:id,name', 'farmer:id,name,verified_at,channel')
            ->where('commodity_id', $commodity->id)
            ->available()
            ->latest('confirmed_at')
            ->limit(300)
            ->get();

        $myOffers = $buyer
            ? Offer::query()->where('buyer_id', $buyer->id)->whereIn('stock_report_id', $reports->modelKeys())->whereIn('status', Offer::OPEN)->pluck('status', 'stock_report_id')
            : collect();

        return [
            'commodity' => ['slug' => $commodity->slug, 'name' => $commodity->name],
            'points' => $reports->map(fn (StockReport $report) => [
                'id' => $report->id,
                'lat' => $report->lat,
                'lng' => $report->lng,
                'region' => $report->region->name,
                'quantity_kg' => $report->quantity_kg,
                'price' => $report->price_per_kg,
                'verified' => $report->farmer->isVerified(),
                'sample' => $report->farmer->channel !== 'wa',
                'source' => $report->farmer->channel,
                'farmer' => $buyer ? $report->farmer->firstName() : null,
                'my_offer' => $myOffers->get($report->id),
                'confirmed_at' => $report->confirmed_at->toIso8601String(),
                'ago' => $report->confirmed_at->diffForHumans(),
            ])->values(),
            'regions' => $this->prices->regionSummary($commodity)->map(fn (array $row) => [
                'region' => $row['region']->name,
                'avg' => $row['avg'],
                'min' => $row['min'],
                'max' => $row['max'],
                'count' => $row['count'],
                'change' => $row['change'],
                'date' => $row['date']->toDateString(),
            ]),
            'trend' => $this->prices->trend($commodity->id, null, 14),
            'stats' => $this->stats(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function stats(): array
    {
        return Cache::remember('board-stats', 30, fn () => [
            'farmers' => Farmer::query()->count(),
            'verified' => Farmer::query()->whereNotNull('verified_at')->count(),
            'reports_today' => StockReport::query()->whereIn('status', StockReport::PRICED)->where('confirmed_at', '>=', now()->startOfDay())->count(),
            'deals' => Offer::query()->where('status', Offer::ACCEPTED)->count(),
            'has_samples' => Farmer::query()->where('channel', 'contoh')->exists(),
        ]);
    }
}

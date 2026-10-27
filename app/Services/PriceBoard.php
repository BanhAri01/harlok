<?php

namespace App\Services;

use App\Models\Commodity;
use App\Models\DailyPrice;
use App\Models\Region;
use App\Models\StockReport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PriceBoard
{
    public function recompute(int $commodityId, int $regionId, Carbon $date): ?DailyPrice
    {
        $prices = StockReport::query()
            ->where('commodity_id', $commodityId)
            ->where('region_id', $regionId)
            ->whereIn('status', StockReport::PRICED)
            ->whereBetween('confirmed_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->whereHas('farmer', fn ($query) => $query->whereNotNull('verified_at'))
            ->pluck('price_per_kg')
            ->sort()
            ->values();

        $key = ['commodity_id' => $commodityId, 'region_id' => $regionId, 'date' => $date->toDateString()];

        if ($prices->isEmpty()) {
            DailyPrice::query()->where($key)->delete();

            return null;
        }

        return DailyPrice::query()->updateOrCreate($key, [
            'avg_price' => (int) round($prices->avg()),
            'min_price' => $prices->first(),
            'max_price' => $prices->last(),
            'median_price' => (int) round($prices->median()),
            'reports_count' => $prices->count(),
        ]);
    }

    public function recomputeFor(StockReport $report): void
    {
        $this->recompute($report->commodity_id, $report->region_id, ($report->confirmed_at ?? now())->copy());
    }

    public function marketAverage(int $commodityId, int $regionId): ?array
    {
        $since = now()->subDays((int) config('lapor.market.lookback_days'))->toDateString();

        $regional = DailyPrice::query()
            ->where('commodity_id', $commodityId)
            ->where('region_id', $regionId)
            ->where('date', '>=', $since)
            ->orderByDesc('date')
            ->first(['avg_price', 'date']);

        if ($regional) {
            return ['price' => $regional->avg_price, 'scope' => 'region', 'date' => $regional->date];
        }

        $latestDate = DailyPrice::query()->where('commodity_id', $commodityId)->where('date', '>=', $since)->max('date');

        if (! $latestDate) {
            return null;
        }

        $average = DailyPrice::query()->where('commodity_id', $commodityId)->where('date', $latestDate)->avg('avg_price');

        return ['price' => (int) round($average), 'scope' => 'all', 'date' => Carbon::parse($latestDate)];
    }

    public function regionSummary(Commodity $commodity): Collection
    {
        $since = now()->subDays((int) config('lapor.market.lookback_days'))->toDateString();
        $regions = Region::ordered()->keyBy('id');

        return DailyPrice::query()
            ->where('commodity_id', $commodity->id)
            ->where('date', '>=', $since)
            ->orderByDesc('date')
            ->get(['region_id', 'date', 'avg_price', 'min_price', 'max_price', 'reports_count'])
            ->groupBy('region_id')
            ->map(function (Collection $rows, int $regionId) use ($regions) {
                $latest = $rows->first();
                $previous = $rows->get(1);

                return [
                    'region' => $regions->get($regionId),
                    'date' => $latest->date,
                    'avg' => $latest->avg_price,
                    'min' => $latest->min_price,
                    'max' => $latest->max_price,
                    'count' => $latest->reports_count,
                    'change' => $previous ? $latest->avg_price - $previous->avg_price : null,
                ];
            })
            ->sortBy(fn (array $row) => $row['region']?->sort)
            ->values();
    }

    public function trend(int $commodityId, ?int $regionId, int $days): Collection
    {
        $query = DailyPrice::query()
            ->where('commodity_id', $commodityId)
            ->where('date', '>=', now()->subDays($days - 1)->toDateString());

        if ($regionId) {
            $query->where('region_id', $regionId);
        }

        return $query->get(['date', 'avg_price', 'reports_count'])
            ->groupBy(fn (DailyPrice $row) => $row->date->toDateString())
            ->map(fn (Collection $rows, string $date) => [
                'date' => $date,
                'avg' => (int) round($rows->sum(fn ($row) => $row->avg_price * $row->reports_count) / max($rows->sum('reports_count'), 1)),
                'reports' => $rows->sum('reports_count'),
            ])
            ->sortKeys()
            ->values();
    }
}

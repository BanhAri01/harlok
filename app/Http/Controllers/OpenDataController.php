<?php

namespace App\Http\Controllers;

use App\Models\Commodity;
use App\Models\DailyPrice;
use App\Models\Region;
use App\Services\BoardData;
use App\Services\PriceBoard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OpenDataController extends Controller
{
    private const CSV_MAX_DAYS = 366;

    public function widget(Request $request, PriceBoard $prices, BoardData $board): View
    {
        $commodity = $this->commodityOrFail($request->query('komoditas'));
        $region = $this->regionOrFail($request->query('wilayah'));

        $rows = Cache::remember("widget:{$commodity->id}:".($region?->id ?? 'semua'), 120, function () use ($prices, $commodity, $region) {
            $rows = $prices->regionSummary($commodity);

            return ($region ? $rows->filter(fn (array $row) => $row['region']->id === $region->id) : $rows)
                ->map(fn (array $row) => [
                    'region' => $row['region']->name,
                    'avg' => $row['avg'],
                    'change' => $row['change'],
                    'date' => $row['date']->toDateString(),
                ])
                ->values()
                ->all();
        });

        return view('widget', [
            'commodity' => $commodity,
            'region' => $region,
            'rows' => $rows,
            'hasSamples' => $board->stats()['has_samples'],
            'dark' => $request->query('tema') === 'gelap',
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        $commodity = $request->filled('komoditas') ? $this->commodityOrFail($request->query('komoditas')) : null;
        $days = min(max((int) $request->query('hari', 90), 1), self::CSV_MAX_DAYS);
        $commodities = Commodity::active()->keyBy('id');
        $regions = Region::ordered()->keyBy('id');

        $query = DailyPrice::query()
            ->where('date', '>=', now()->subDays($days - 1)->toDateString())
            ->when($commodity, fn ($query) => $query->where('commodity_id', $commodity->id))
            ->orderBy('date')
            ->orderBy('commodity_id')
            ->orderBy('region_id');

        $filename = 'harlok-'.($commodity?->slug ?? 'semua').'-'.now()->toDateString().'.csv';

        return response()->streamDownload(function () use ($query, $commodities, $regions) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['tanggal', 'komoditas', 'wilayah', 'provinsi', 'rata_rata', 'median', 'terendah', 'tertinggi', 'jumlah_laporan', 'satuan']);

            foreach ($query->lazy(500) as $row) {
                $region = $regions->get($row->region_id);
                $commodity = $commodities->get($row->commodity_id);

                if (! $region || ! $commodity) {
                    continue;
                }

                fputcsv($out, [
                    $row->date->toDateString(),
                    $commodity->slug,
                    $region->slug,
                    $region->province,
                    $row->avg_price,
                    $row->median_price,
                    $row->min_price,
                    $row->max_price,
                    $row->reports_count,
                    'rupiah/kg',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'public, max-age=600']);
    }

    private function commodityOrFail(?string $slug): Commodity
    {
        return Commodity::active()->firstWhere('slug', $slug ?: Commodity::active()->first()?->slug) ?? abort(404);
    }

    private function regionOrFail(?string $slug): ?Region
    {
        if (! $slug) {
            return null;
        }

        return Region::ordered()->firstWhere('slug', $slug) ?? abort(404);
    }
}

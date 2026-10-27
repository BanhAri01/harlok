<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commodity;
use App\Models\DailyPrice;
use App\Models\Region;
use App\Services\PriceBoard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PriceApiController extends Controller
{
    public function commodities(): JsonResponse
    {
        return response()->json([
            'data' => Commodity::active()->map(fn (Commodity $commodity) => [
                'slug' => $commodity->slug,
                'nama' => $commodity->name,
                'satuan_harga' => 'kg',
            ])->values(),
        ]);
    }

    public function regions(): JsonResponse
    {
        return response()->json([
            'data' => Region::ordered()->map(fn (Region $region) => [
                'slug' => $region->slug,
                'nama' => $region->name,
                'provinsi' => $region->province,
                'lat' => $region->lat,
                'lng' => $region->lng,
            ])->values(),
        ]);
    }

    public function prices(Request $request, PriceBoard $prices): JsonResponse
    {
        $data = $request->validate([
            'komoditas' => ['required', 'string', Rule::in(Commodity::active()->pluck('slug'))],
            'wilayah' => ['nullable', 'string', Rule::in(Region::ordered()->pluck('slug'))],
        ]);

        $commodity = Commodity::active()->firstWhere('slug', $data['komoditas']);
        $rows = $prices->regionSummary($commodity);

        if (isset($data['wilayah'])) {
            $rows = $rows->filter(fn (array $row) => $row['region']->slug === $data['wilayah'])->values();
        }

        return response()->json([
            'komoditas' => $commodity->slug,
            'satuan' => 'rupiah/kg',
            'sumber' => 'Laporan peternak terverifikasi, '.config('lapor.market.lookback_days').' hari terakhir',
            'data' => $rows->map(fn (array $row) => [
                'wilayah' => $row['region']->slug,
                'nama_wilayah' => $row['region']->name,
                'tanggal' => $row['date']->toDateString(),
                'rata_rata' => $row['avg'],
                'terendah' => $row['min'],
                'tertinggi' => $row['max'],
                'jumlah_laporan' => $row['count'],
                'perubahan' => $row['change'],
            ]),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $data = $request->validate([
            'komoditas' => ['required', 'string', Rule::in(Commodity::active()->pluck('slug'))],
            'wilayah' => ['required', 'string', Rule::in(Region::ordered()->pluck('slug'))],
            'hari' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);

        $commodity = Commodity::active()->firstWhere('slug', $data['komoditas']);
        $region = Region::ordered()->firstWhere('slug', $data['wilayah']);
        $days = (int) ($data['hari'] ?? 30);

        $rows = DailyPrice::query()
            ->where('commodity_id', $commodity->id)
            ->where('region_id', $region->id)
            ->where('date', '>=', now()->subDays($days - 1)->toDateString())
            ->orderBy('date')
            ->get(['date', 'avg_price', 'min_price', 'max_price', 'median_price', 'reports_count']);

        return response()->json([
            'komoditas' => $commodity->slug,
            'wilayah' => $region->slug,
            'satuan' => 'rupiah/kg',
            'data' => $rows->map(fn (DailyPrice $row) => [
                'tanggal' => $row->date->toDateString(),
                'rata_rata' => $row->avg_price,
                'median' => $row->median_price,
                'terendah' => $row->min_price,
                'tertinggi' => $row->max_price,
                'jumlah_laporan' => $row->reports_count,
            ]),
        ]);
    }
}

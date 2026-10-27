<?php

namespace Database\Seeders;

use App\Models\Commodity;
use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MarketSeeder extends Seeder
{
    public const REGIONS = [
        ['Badung', 'Bali', -8.5650, 115.1830],
        ['Bangli', 'Bali', -8.4540, 115.3550],
        ['Buleleng', 'Bali', -8.1700, 115.0900],
        ['Denpasar', 'Bali', -8.6600, 115.2160],
        ['Gianyar', 'Bali', -8.5370, 115.3300],
        ['Jembrana', 'Bali', -8.3600, 114.6400],
        ['Karangasem', 'Bali', -8.4500, 115.6000],
        ['Klungkung', 'Bali', -8.5400, 115.4000],
        ['Tabanan', 'Bali', -8.5000, 115.1000],
        ['Bantul', 'DI Yogyakarta', -7.8900, 110.3300],
        ['Gunungkidul', 'DI Yogyakarta', -7.9700, 110.5900],
        ['Kulon Progo', 'DI Yogyakarta', -7.8500, 110.1600],
        ['Sleman', 'DI Yogyakarta', -7.7200, 110.3600],
        ['Kota Yogyakarta', 'DI Yogyakarta', -7.8000, 110.3700],
    ];

    public const COMMODITIES = [
        ['telur', 'Telur Ayam Ras', ['telur', 'telor', 'endog', 'taluh', 'telur ayam'], ['peti' => 15, 'tray' => 1.9], 15000, 45000],
        ['ayam-broiler', 'Ayam Broiler (hidup)', ['ayam', 'broiler', 'ayam potong', 'ayam pedaging'], ['ekor' => 2], 12000, 45000],
        ['cabai-rawit', 'Cabai Rawit', ['cabai rawit', 'cabe rawit', 'rawit', 'cabai', 'cabe', 'tabia'], null, 5000, 150000],
        ['beras', 'Beras Medium', ['beras', 'baas'], ['karung' => 50], 8000, 25000],
        ['jagung', 'Jagung Pipil Kering', ['jagung', 'jagung pipil'], ['karung' => 50], 3000, 12000],
    ];

    public function run(): void
    {
        foreach (self::REGIONS as $index => [$name, $province, $lat, $lng]) {
            Region::query()->updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'province' => $province,
                'lat' => $lat,
                'lng' => $lng,
                'sort' => $index + 1,
            ]);
        }

        foreach (self::COMMODITIES as $index => [$slug, $name, $aliases, $units, $min, $max]) {
            Commodity::query()->updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'aliases' => $aliases,
                'unit_sizes' => $units,
                'min_price' => $min,
                'max_price' => $max,
                'sort' => $index + 1,
            ]);
        }
    }
}

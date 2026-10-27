<?php

namespace Database\Seeders;

use App\Models\Commodity;
use App\Models\Farmer;
use App\Models\Region;
use App\Models\StockReport;
use App\Models\User;
use App\Services\PriceBoard;
use App\Services\ReportService;
use App\Support\Phone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SampleDataSeeder extends Seeder
{
    private const BASE_PRICES = [
        'telur' => [25800, 26600],
        'ayam-broiler' => [20500, 21500],
        'cabai-rawit' => [38000, 42000],
        'beras' => [12800, 13200],
        'jagung' => [5400, 5800],
    ];

    private const NAMES = ['Made', 'Wayan', 'Nyoman', 'Ketut', 'Putu', 'Komang', 'Gede', 'Kadek', 'Sri', 'Budi', 'Slamet', 'Siti', 'Joko', 'Tri', 'Agus', 'Dewi'];

    public function run(PriceBoard $prices, ReportService $reports): void
    {
        mt_srand(2026);

        $regions = Region::ordered();
        $commodities = Commodity::active()->keyBy('slug');
        $sequence = 0;
        $touched = [];

        foreach ($regions as $region) {
            $isBali = $region->province === 'Bali';
            $plan = ['telur' => $isBali ? 4 : 3, 'ayam-broiler' => 1, 'cabai-rawit' => $isBali ? 1 : 2, 'beras' => 1, 'jagung' => $isBali ? 0 : 1];

            foreach ($plan as $slug => $count) {
                $commodity = $commodities->get($slug);

                for ($i = 0; $i < $count; $i++) {
                    $sequence++;
                    $farmer = Farmer::query()->create([
                        'name' => self::NAMES[$sequence % count(self::NAMES)].' (contoh)',
                        'phone' => Phone::SAMPLE_PREFIX.str_pad((string) $sequence, 7, '0', STR_PAD_LEFT),
                        'region_id' => $region->id,
                        'commodity_id' => $commodity->id,
                        'channel' => 'contoh',
                        'verified_at' => now()->subDays(20),
                    ]);
                    [$lat, $lng] = $reports->position($farmer);

                    $base = self::BASE_PRICES[$slug][$isBali ? 0 : 1] * (1 + (crc32($region->slug) % 7 - 3) / 100);

                    for ($daysAgo = 13; $daysAgo >= 0; $daysAgo--) {
                        if (mt_rand(1, 100) > 45 && $daysAgo > 0) {
                            continue;
                        }

                        $trend = $slug === 'cabai-rawit' ? (13 - $daysAgo) * 0.012 : (13 - $daysAgo) * 0.002;
                        $noise = mt_rand(-25, 25) / 1000;
                        $price = (int) (round($base * (1 + $trend + $noise) / 100) * 100);
                        $confirmedAt = now()->subDays($daysAgo)->setTime(mt_rand(6, 17), mt_rand(0, 59));
                        if ($confirmedAt->isFuture()) {
                            $confirmedAt = now()->subMinutes(mt_rand(20, 300));
                        }
                        $active = $daysAgo <= 1;

                        StockReport::query()->create([
                            'farmer_id' => $farmer->id,
                            'commodity_id' => $commodity->id,
                            'region_id' => $region->id,
                            'quantity_kg' => $this->quantity($slug),
                            'price_per_kg' => $price,
                            'status' => $active ? StockReport::CONFIRMED : StockReport::CLOSED,
                            'parsed_by' => 'contoh',
                            'raw_text' => 'Data contoh',
                            'lat' => $lat,
                            'lng' => $lng,
                            'confirmed_at' => $confirmedAt,
                            'expires_at' => $active ? now()->addDays(2) : $confirmedAt->copy()->addDays(1),
                            'closed_at' => $active ? null : $confirmedAt->copy()->addDays(1),
                        ]);

                        $touched[$commodity->id.'-'.$region->id.'-'.$confirmedAt->toDateString()] = [$commodity->id, $region->id, $confirmedAt];
                    }
                }
            }
        }

        foreach ($touched as [$commodityId, $regionId, $date]) {
            $prices->recompute($commodityId, $regionId, $date);
        }

        User::query()->updateOrCreate(['email' => 'pengepul@contoh.id'], [
            'name' => 'Pengepul Demo',
            'business_name' => 'UD Contoh Makmur (demo)',
            'password' => Str::password(16),
            'role' => User::BUYER,
            'region_id' => $regions->firstWhere('slug', 'tabanan')?->id,
            'is_active' => true,
            'is_demo' => true,
        ]);
    }

    private function quantity(string $slug): float
    {
        return match ($slug) {
            'telur' => mt_rand(4, 40) * 50,
            'ayam-broiler' => mt_rand(5, 30) * 100,
            'cabai-rawit' => mt_rand(2, 15) * 20,
            default => mt_rand(5, 30) * 100,
        };
    }
}

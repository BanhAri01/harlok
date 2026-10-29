<?php

namespace App\Services;

use App\Ai\AiGateway;
use App\Models\Commodity;
use App\Support\Rupiah;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MarketBrief
{
    private const SYSTEM = <<<'PROMPT'
Kamu menulis ringkasan pasar harian Harlok untuk peternak, petani, dan pengepul kecil di Bali. Pembacanya membaca dari HP, jadi pakai bahasa Indonesia yang sederhana.

Dari DATA harga rata-rata per kg per wilayah dan tren harian, tulis summary: 2 kalimat, maksimal 260 karakter. Sebut arah harga, wilayah termurah dan termahal bila ada, serta satu hal yang berguna untuk keputusan menjual atau membeli hari ini. Isi direction dengan naik, turun, stabil, atau campuran.

Gunakan hanya angka pada DATA, tulis rupiah seperti Rp26.000. Jangan meramal angka yang tidak ada dan jangan menjanjikan keuntungan.
PROMPT;

    public function __construct(private AiGateway $ai, private PriceBoard $prices) {}

    public function for(Commodity $commodity): ?array
    {
        $rows = $this->prices->regionSummary($commodity);

        if ($rows->isEmpty()) {
            return null;
        }

        $data = [
            'komoditas' => $commodity->name,
            'wilayah' => $rows->map(fn (array $row) => [
                'nama' => $row['region']?->name,
                'rata_rata' => $row['avg'],
                'perubahan' => $row['change'],
                'laporan' => $row['count'],
                'tanggal' => $row['date']->toDateString(),
            ])->values()->all(),
            'tren_harian' => $this->prices->trend($commodity->id, null, 7),
        ];
        $key = 'ringkasan-pasar:'.$commodity->id.':'.md5(json_encode($data));

        if ($cached = Cache::get($key)) {
            return $cached;
        }

        if (! $this->ai->available()) {
            return $this->fallback($commodity, $rows);
        }

        $brief = Cache::lock($key.':kunci', 30)->get(function () use ($key, $data, $commodity, $rows) {
            $answer = $this->ai->json('ringkasan_pasar', self::SYSTEM, json_encode($data, JSON_UNESCAPED_UNICODE), [
                'type' => 'object',
                'properties' => [
                    'summary' => ['type' => 'string'],
                    'direction' => ['type' => 'string', 'enum' => ['naik', 'turun', 'stabil', 'campuran']],
                ],
                'required' => ['summary', 'direction'],
                'additionalProperties' => false,
            ], 300);

            $summary = Str::limit(Str::squish(preg_replace('~https?://\S+~i', '', strip_tags((string) ($answer['summary'] ?? '')))), 300);

            if ($summary === '') {
                return tap($this->fallback($commodity, $rows), fn (array $brief) => Cache::put($key, $brief, now()->addMinutes(15)));
            }

            $brief = ['text' => $summary, 'direction' => $answer['direction'], 'source' => 'ai', 'generated_at' => now()->toIso8601String()];
            Cache::put($key, $brief, now()->addHours(12));

            return $brief;
        });

        return $brief ?: $this->fallback($commodity, $rows);
    }

    private function fallback(Commodity $commodity, Collection $rows): array
    {
        $cheapest = $rows->sortBy('avg')->first();
        $priciest = $rows->sortByDesc('avg')->first();
        $up = $rows->filter(fn (array $row) => $row['change'] > 0)->count();
        $down = $rows->filter(fn (array $row) => $row['change'] < 0)->count();

        $text = $rows->count() > 1
            ? "Harga {$commodity->name} termurah di {$cheapest['region']->name} (".Rupiah::format($cheapest['avg']).'/kg) dan termahal di '
                ."{$priciest['region']->name} (".Rupiah::format($priciest['avg']).'/kg).'
            : "Harga {$commodity->name} di {$cheapest['region']->name} rata-rata ".Rupiah::format($cheapest['avg']).'/kg.';

        if ($up || $down) {
            $text .= " {$up} wilayah naik dan {$down} wilayah turun dari laporan sebelumnya.";
        }

        return [
            'text' => $text,
            'direction' => match (true) {
                $up && $down => 'campuran',
                $up > 0 => 'naik',
                $down > 0 => 'turun',
                default => 'stabil',
            },
            'source' => 'aturan',
            'generated_at' => now()->toIso8601String(),
        ];
    }
}

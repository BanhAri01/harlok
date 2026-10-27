<?php

namespace App\Chat;

use App\Models\Commodity;
use App\Models\Region;
use Illuminate\Support\Str;

class ReportParser
{
    public const WEIGHT_UNITS = ['ton' => 1000, 'kwintal' => 100, 'kw' => 100, 'kilogram' => 1, 'kilo' => 1, 'kg' => 1];

    public const PACK_UNITS = ['peti', 'tray', 'krat', 'ikat', 'ekor', 'karung'];

    public const REGION_VARIANTS = [
        'gunungkidul' => ['gunung kidul', 'wonosari'],
        'kulon-progo' => ['kulonprogo', 'wates'],
        'kota-yogyakarta' => ['jogja', 'yogya', 'jogjakarta', 'yogyakarta'],
        'buleleng' => ['singaraja'],
        'klungkung' => ['semarapura'],
        'karangasem' => ['amlapura'],
    ];

    public const PRICE_SCALES = ['ribu' => 1000, 'rbu' => 1000, 'rb' => 1000, 'k' => 1000, 'juta' => 1000000, 'jt' => 1000000];

    private const TOKEN = '/(?<prefix>rp\.?\s*|@\s*|harga\s*:?\s*)?(?<num>\d+(?:[.,]\d+)*)\s*(?<unit>kilogram|kilo|kwintal|kw|kg|ton|peti|tray|krat|ikat|ekor|karung|ribu|rbu|rb|juta|jt|k)?\b(?:\s*(?:\/|per|se)\s*(?<per>kilogram|kilo|kg|ton|peti|tray|krat|ikat|ekor|karung))?/u';

    public function parse(string $text, ?Commodity $defaultCommodity = null, ?Region $defaultRegion = null): ParsedReport
    {
        $normalized = $this->normalize($text);
        $commodity = $this->findCommodity($normalized) ?? $defaultCommodity;
        $region = $this->findRegion($normalized) ?? $defaultRegion;

        $quantity = null;
        $price = null;

        preg_match_all(self::TOKEN, $normalized, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $value = $this->number($match['num']);
            $unit = $match['unit'] ?? '';
            $per = $match['per'] ?? '';
            $prefix = trim($match['prefix'] ?? '');

            if ($value <= 0) {
                continue;
            }

            if ($unit !== '' && ($quantityKg = $this->toKilograms($value, $unit, $commodity)) !== null) {
                $quantity ??= $quantityKg;

                continue;
            }

            if (isset(self::PRICE_SCALES[$unit])) {
                $price ??= $this->perKilogram($value * self::PRICE_SCALES[$unit], $per, $commodity);

                continue;
            }

            if ($unit !== '') {
                continue;
            }

            if ($prefix !== '' || $per !== '' || $value >= 1000) {
                $amount = $value < 1000 && $per === '' ? $value * 1000 : $value;
                $price ??= $this->perKilogram($amount, $per, $commodity);

                continue;
            }

            $quantity ??= $value;
        }

        return new ParsedReport(
            $commodity,
            $quantity !== null ? round($quantity, 2) : null,
            $price !== null && $price > 0 ? (int) round($price) : null,
            $region,
        );
    }

    public function toKilograms(float $value, string $unit, ?Commodity $commodity): ?float
    {
        if (isset(self::WEIGHT_UNITS[$unit])) {
            return $value * self::WEIGHT_UNITS[$unit];
        }

        if (in_array($unit, self::PACK_UNITS, true)) {
            $size = $commodity?->unitSize($unit);

            return $size ? $value * $size : null;
        }

        return null;
    }

    public function perKilogram(float $price, string $per, ?Commodity $commodity): ?float
    {
        if ($per === '' || isset(self::WEIGHT_UNITS[$per]) && self::WEIGHT_UNITS[$per] === 1) {
            return $price;
        }

        $kilograms = $this->toKilograms(1, $per, $commodity);

        return $kilograms ? $price / $kilograms : null;
    }

    public function findCommodity(string $normalized): ?Commodity
    {
        $best = null;
        $bestLength = 0;

        foreach (Commodity::active() as $commodity) {
            foreach ([$commodity->slug, ...$commodity->aliases] as $alias) {
                $alias = Str::lower($alias);
                if (mb_strlen($alias) > $bestLength && preg_match('/(?<![a-z])'.preg_quote($alias, '/').'(?![a-z])/u', $normalized)) {
                    $best = $commodity;
                    $bestLength = mb_strlen($alias);
                }
            }
        }

        return $best;
    }

    public function findRegion(string $normalized): ?Region
    {
        foreach (Region::ordered() as $region) {
            foreach ($this->regionAliases($region) as $alias) {
                if (preg_match('/(?<![a-z])'.preg_quote($alias, '/').'(?![a-z])/u', $normalized)) {
                    return $region;
                }
            }
        }

        return null;
    }

    public function regionAliases(Region $region): array
    {
        $name = Str::lower($region->name);
        $short = trim(preg_replace('/^(kota|kabupaten|kab\.?)\s+/u', '', $name));

        return array_values(array_unique(array_filter([
            $name,
            $short,
            str_replace(' ', '', $short),
            str_replace('-', ' ', $region->slug),
            ...(self::REGION_VARIANTS[$region->slug] ?? []),
        ])));
    }

    public function number(string $raw): float
    {
        if (str_contains($raw, '.') && str_contains($raw, ',')) {
            return (float) str_replace(['.', ','], ['', '.'], $raw);
        }

        if (str_contains($raw, ',')) {
            return preg_match('/^\d{1,3}(,\d{3})+$/', $raw) ? (float) str_replace(',', '', $raw) : (float) str_replace(',', '.', $raw);
        }

        if (str_contains($raw, '.')) {
            return preg_match('/^\d{1,3}(\.\d{3})+$/', $raw) ? (float) str_replace('.', '', $raw) : (float) $raw;
        }

        return (float) $raw;
    }

    public function normalize(string $text): string
    {
        $text = Str::lower(Str::squish($text));

        return str_replace(['kilo gram', 'per-kg', 'perkg', '/ kg'], ['kilogram', 'per kg', 'per kg', '/kg'], $text);
    }
}

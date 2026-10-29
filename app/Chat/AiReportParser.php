<?php

namespace App\Chat;

use Anthropic\Client;
use App\Models\Commodity;
use App\Models\Region;
use App\Support\Phone;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class AiReportParser
{
    public function __construct(private ReportParser $units) {}

    public function available(): bool
    {
        return (string) config('lapor.ai.api_key') !== '';
    }

    public function parse(string $phone, string $text, ?Commodity $defaultCommodity, ?Region $defaultRegion): ?ParsedReport
    {
        if (! $this->available() || ! preg_match('/\d/', $text)) {
            return null;
        }

        $budgets = ['ai-parse:'.$phone => (int) config('lapor.ai.daily_per_phone'), 'ai-parse:semua' => (int) config('lapor.ai.daily_global')];
        if (Phone::channel($phone) !== 'wa') {
            $budgets['ai-parse:simulator'] = (int) config('lapor.ai.daily_simulator');
        }

        foreach ($budgets as $key => $limit) {
            if (RateLimiter::tooManyAttempts($key, $limit)) {
                return null;
            }
        }

        foreach (array_keys($budgets) as $key) {
            RateLimiter::hit($key, 86400);
        }

        try {
            $fields = $this->extract($text);
        } catch (Throwable $exception) {
            Log::warning('AI gagal membaca laporan: '.$exception->getMessage());

            return null;
        }

        return $fields ? $this->toReport($fields, $defaultCommodity, $defaultRegion) : null;
    }

    public function toReport(array $fields, ?Commodity $defaultCommodity, ?Region $defaultRegion): ParsedReport
    {
        $commodity = Commodity::active()->firstWhere('slug', $fields['commodity'] ?? null) ?? $defaultCommodity;
        $region = Region::ordered()->firstWhere('slug', $fields['region'] ?? null) ?? $defaultRegion;

        $quantity = null;
        if (is_numeric($fields['quantity'] ?? null) && $fields['quantity'] > 0) {
            $quantity = $this->units->toKilograms((float) $fields['quantity'], (string) ($fields['quantity_unit'] ?? 'kg'), $commodity);
        }

        $price = null;
        if (is_numeric($fields['price'] ?? null) && $fields['price'] > 0) {
            $per = (string) ($fields['price_unit'] ?? 'kg');
            $price = $this->units->perKilogram((float) $fields['price'], $per === 'kg' ? '' : $per, $commodity);
        }

        return new ParsedReport(
            $commodity,
            $quantity !== null ? round($quantity, 2) : null,
            $price ? (int) round($price) : null,
            $region,
            'ai',
        );
    }

    private function extract(string $text): ?array
    {
        $units = array_merge(array_keys(ReportParser::WEIGHT_UNITS), ReportParser::PACK_UNITS);
        $commodities = Commodity::active()->map(fn (Commodity $commodity) => $commodity->slug.' ('.implode(', ', $commodity->aliases).')')->implode('; ');
        $regions = Region::ordered()->pluck('slug')->implode(', ');

        $system = 'Kamu membaca pesan WhatsApp peternak/petani Indonesia yang melaporkan stok dan harga jual. '
            .'Ambil hanya yang benar-benar tertulis; jangan menebak. Kosongkan (null) bila tidak ada. '
            .'Harga dalam rupiah penuh (26rb = 26000, 1,2jt = 1200000). '
            ."Komoditas yang dikenal: {$commodities}. Wilayah (slug): {$regions}. "
            .'Satuan jumlah: '.implode(', ', $units).'. Satuan harga: kg, ton, peti, tray, krat, ikat, ekor.';

        $schema = [
            'type' => 'object',
            'properties' => [
                'commodity' => ['type' => ['string', 'null']],
                'quantity' => ['type' => ['number', 'null']],
                'quantity_unit' => ['type' => ['string', 'null']],
                'price' => ['type' => ['number', 'null']],
                'price_unit' => ['type' => ['string', 'null']],
                'region' => ['type' => ['string', 'null']],
            ],
            'required' => ['commodity', 'quantity', 'quantity_unit', 'price', 'price_unit', 'region'],
            'additionalProperties' => false,
        ];

        $message = $this->client()->messages->create(
            maxTokens: 300,
            messages: [['role' => 'user', 'content' => mb_substr($text, 0, 500)]],
            model: (string) config('lapor.ai.model'),
            outputConfig: ['format' => ['type' => 'json_schema', 'schema' => $schema]],
            system: $system,
        );

        $json = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $json .= $block->text;
            }
        }

        $fields = json_decode($json, true);

        return is_array($fields) ? $fields : null;
    }

    private function client(): Client
    {
        return new Client(
            apiKey: (string) config('lapor.ai.api_key'),
            requestOptions: [
                'maxRetries' => 1,
                'transporter' => new GuzzleClient(['timeout' => (float) config('lapor.ai.timeout'), 'connect_timeout' => 8]),
            ],
        );
    }
}

<?php

use App\Models\AiCall;
use App\Models\Offer;
use App\Models\StockReport;
use App\Support\Phone;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

function fakeAi(array ...$replies): void
{
    config(['lapor.ai.driver' => 'openai', 'lapor.ai.api_key' => 'kunci-uji', 'lapor.ai.base_url' => 'https://ai.contoh.test/v1', 'lapor.ai.model' => 'model-uji']);

    $sequence = Http::sequence();
    foreach ($replies as $reply) {
        $sequence->push(['choices' => [['message' => ['content' => json_encode($reply)], 'finish_reason' => 'stop']]]);
    }

    Http::fake(['ai.contoh.test/*' => $sequence]);
}

function intent(string $intent, array $fields = []): array
{
    return [
        'intent' => $intent, 'commodity' => null, 'quantity' => null, 'quantity_unit' => null,
        'price' => null, 'price_unit' => null, 'region' => null, 'answer' => null, ...$fields,
    ];
}

it('AI memahami laporan berbahasa Bali tanpa mengirim nama atau nomor peternak', function () {
    $farmer = farmer(['name' => 'Wayan Rahasia']);
    fakeAi(intent('lapor_stok', ['commodity' => 'telur', 'quantity' => 2, 'quantity_unit' => 'ton', 'price' => 26000, 'price_unit' => 'kg']));

    expect(chat($farmer->phone, 'taluh 2 ton 26 tali, siap kirim')[0])->toContain('Mohon cek')->toContain('2.000 kg');

    expect(StockReport::query()->sole()->parsed_by)->toBe('ai')
        ->and(AiCall::query()->sole()->status)->toBe(AiCall::OK);

    Http::assertSent(fn (Request $request) => ! str_contains($request->body(), $farmer->phone)
        && ! str_contains($request->body(), 'Wayan')
        && $request->hasHeader('Authorization', 'Bearer kunci-uji'));
});

it('AI menjawab pertanyaan pasar dengan penanda AI dan tanpa tautan', function () {
    $farmer = farmer();
    fakeAi(intent('pertanyaan', ['answer' => 'Harga telur di Tabanan stabil minggu ini. Lihat https://contoh.id/promo']));

    $reply = chat($farmer->phone, 'gimana harga telur minggu ini, bagus jual sekarang?')[0];

    expect($reply)->toStartWith('🤖 Harga telur di Tabanan stabil')
        ->toContain('Dijawab AI')
        ->not->toContain('https://');
});

it('bila AI gagal, bot tetap jalan memakai pembaca aturan', function () {
    $farmer = farmer();
    config(['lapor.ai.driver' => 'openai', 'lapor.ai.api_key' => 'kunci-uji', 'lapor.ai.base_url' => 'https://ai.contoh.test/v1']);
    Http::fake(['ai.contoh.test/*' => Http::response(['error' => ['message' => 'server sibuk']], 503)]);

    expect(chat($farmer->phone, 'telur 1 ton 26rb')[0])->toContain('Mohon cek');

    expect(StockReport::query()->sole()->parsed_by)->not->toBe('ai')
        ->and(AiCall::query()->sole())->status->toBe(AiCall::FAILED)->error->toContain('503');
});

it('jawaban AI yang tidak sesuai skema ditolak lalu memakai pembaca aturan', function () {
    $farmer = farmer();
    fakeAi(['intent' => 'hapus_semua_data', 'commodity' => null]);

    expect(chat($farmer->phone, 'telur 1 ton 26rb')[0])->toContain('Mohon cek')
        ->and(AiCall::query()->sole()->status)->toBe(AiCall::INVALID);
});

it('AI tidak pernah menerima tawaran sendiri; peternak tetap harus membalas 1', function () {
    $farmer = farmer();
    $report = stock($farmer);
    $offer = Offer::query()->create(['stock_report_id' => $report->id, 'buyer_id' => buyer()->id, 'price_per_kg' => 25500, 'quantity_kg' => 500, 'status' => Offer::SENT]);
    fakeAi(intent('terima_tawaran'));

    expect(chat($farmer->phone, 'oke deh sy ambil yg td')[0])->toContain('Untuk menerima, balas *1*')
        ->and($offer->refresh()->status)->toBe(Offer::SENT);

    chat($farmer->phone, '1');
    expect($offer->refresh()->status)->toBe(Offer::ACCEPTED);
});

it('AI meneruskan tawar balik dengan harga yang disebut peternak', function () {
    $farmer = farmer();
    $report = stock($farmer);
    $offer = Offer::query()->create(['stock_report_id' => $report->id, 'buyer_id' => buyer()->id, 'price_per_kg' => 24000, 'quantity_kg' => 500, 'status' => Offer::SENT]);
    fakeAi(intent('tawar_balik', ['price' => 26500]));

    expect(chat($farmer->phone, 'kemurahan, 26.5 lah')[0])->toContain('Rp26.500');
    expect($offer->refresh())->status->toBe(Offer::COUNTERED)->counter_price->toBe(26500);
});

it('AI mengarahkan pertanyaan harga ke komoditas yang dimaksud', function () {
    $farmer = farmer();
    fakeAi(intent('cek_harga', ['commodity' => 'cabai-rawit']));

    expect(chat($farmer->phone, 'tabia kenken ajine jani?')[0])->toContain('Cabai Rawit');
});

it('kuota AI habis membuat bot memakai pembaca aturan tanpa memanggil AI', function () {
    $farmer = farmer();
    fakeAi(intent('bantuan'));
    config(['lapor.ai.daily_global' => 1]);
    RateLimiter::hit('ai:semua', 86400);

    expect(chat($farmer->phone, 'telur 1 ton 26rb')[0])->toContain('Mohon cek');

    Http::assertNothingSent();
    expect(AiCall::query()->sole()->status)->toBe(AiCall::SKIPPED);
});

it('simulator punya kuota AI terpisah agar tidak menghabiskan jatah peternak WhatsApp', function () {
    $farmer = farmer(['phone' => Phone::SIMULATOR_PREFIX.'ai0001', 'channel' => 'simulator']);
    fakeAi(intent('bantuan'));
    config(['lapor.ai.daily_simulator' => 1]);
    RateLimiter::hit('ai:simulator', 86400);

    chat($farmer->phone, 'bingung caranya');

    Http::assertNothingSent();
});

it('tanpa kunci AI, bot memakai pembaca aturan dan tidak mencatat panggilan AI', function () {
    $farmer = farmer();
    Http::fake();

    expect(chat($farmer->phone, 'telur 1 ton 26rb')[0])->toContain('Mohon cek')
        ->and(AiCall::query()->count())->toBe(0);

    Http::assertNothingSent();
});

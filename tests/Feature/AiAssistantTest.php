<?php

use App\Models\AiCall;
use App\Models\Offer;
use App\Models\StockReport;
use App\Services\PriceBoard;
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

    expect(chat($farmer->phone, 'taluh 2 ton 26 tali, siap kirim')[0])->toContain('Saya catat begini')->toContain('2.000 kg');

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

    expect($reply)->toStartWith('Harga telur di Tabanan stabil')
        ->toContain('Jawaban ini dibuat AI')
        ->not->toContain('https://');
});

it('bila AI gagal, bot tetap jalan memakai pembaca aturan', function () {
    $farmer = farmer();
    config(['lapor.ai.driver' => 'openai', 'lapor.ai.api_key' => 'kunci-uji', 'lapor.ai.base_url' => 'https://ai.contoh.test/v1']);
    Http::fake(['ai.contoh.test/*' => Http::response(['error' => ['message' => 'server sibuk']], 503)]);

    expect(chat($farmer->phone, 'telur 1 ton 26rb')[0])->toContain('Saya catat begini');

    expect(StockReport::query()->sole()->parsed_by)->not->toBe('ai')
        ->and(AiCall::query()->sole())->status->toBe(AiCall::FAILED)->error->toContain('503');
});

it('jawaban AI yang tidak sesuai skema ditolak lalu memakai pembaca aturan', function () {
    $farmer = farmer();
    fakeAi(['intent' => 'hapus_semua_data', 'commodity' => null]);

    expect(chat($farmer->phone, 'telur 1 ton 26rb')[0])->toContain('Saya catat begini')
        ->and(AiCall::query()->sole()->status)->toBe(AiCall::INVALID);
});

it('AI tidak pernah menerima tawaran sendiri; peternak tetap harus membalas 1', function () {
    $farmer = farmer();
    $report = stock($farmer);
    $offer = Offer::query()->create(['stock_report_id' => $report->id, 'buyer_id' => buyer()->id, 'price_per_kg' => 25500, 'quantity_kg' => 500, 'status' => Offer::SENT]);
    fakeAi(intent('terima_tawaran'));

    expect(chat($farmer->phone, 'oke deh sy ambil yg td')[0])->toContain('Kalau mau diterima, balas *1*')
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

    expect(chat($farmer->phone, 'telur 1 ton 26rb')[0])->toContain('Saya catat begini');

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

    expect(chat($farmer->phone, 'telur 1 ton 26rb')[0])->toContain('Saya catat begini')
        ->and(AiCall::query()->count())->toBe(0);

    Http::assertNothingSent();
});

it('penjaga harga AI menjelaskan laporan janggal ke koordinator dan memberi saran ke peternak', function () {
    $other = farmer();
    stock($other, 26000);
    $farmer = farmer();
    fakeAi(
        intent('lapor_stok', ['commodity' => 'telur', 'quantity' => 1, 'quantity_unit' => 'ton', 'price' => 36000, 'price_unit' => 'kg']),
        ['verdict' => 'salah_ketik', 'suggested_price' => 26000, 'note' => 'Rp36.000 jauh di atas rata-rata Tabanan Rp26.000; kemungkinan angka 2 tertulis 3.', 'farmer_hint' => 'Harga Anda jauh di atas pasar, mungkin salah ketik.'],
    );

    chat($farmer->phone, 'taluh 1 ton 36rb');
    $reply = chat($farmer->phone, 'ya')[0];

    expect($reply)->toContain('jauh dari rata-rata')->toContain('Harga Anda jauh di atas pasar')->toContain('Rp26.000/kg, kirim ulang');

    $report = StockReport::query()->where('farmer_id', $farmer->id)->sole();
    expect($report->status)->toBe(StockReport::FLAGGED)
        ->and($report->ai_verdict)->toBe('salah_ketik')
        ->and($report->ai_note)->toContain('Dugaan harga: Rp26.000/kg');

    Http::assertSent(fn (Request $request) => str_contains($request->body(), 'penjaga') || str_contains($request->body(), 'koordinator Harlok'));
    $this->actingAs(admin())->get(route('admin.dashboard'))->assertSee('Penilaian AI, salah ketik');
});

it('laporan janggal tetap masuk antrean koordinator walau penjaga harga AI gagal', function () {
    $other = farmer();
    stock($other, 26000);
    $farmer = farmer();
    config(['lapor.ai.driver' => 'openai', 'lapor.ai.api_key' => 'kunci-uji', 'lapor.ai.base_url' => 'https://ai.contoh.test/v1']);
    Http::fake(['ai.contoh.test/*' => Http::response([], 500)]);

    chat($farmer->phone, 'telur 1 ton 36rb');
    expect(chat($farmer->phone, 'ya')[0])->toContain('Koordinator akan mengecek')->not->toContain('mungkin salah ketik');

    expect(StockReport::query()->where('farmer_id', $farmer->id)->sole())->status->toBe(StockReport::FLAGGED)->ai_verdict->toBeNull();
});

it('laporan ulang menggantikan laporan janggal sebelumnya', function () {
    $other = farmer();
    stock($other, 26000);
    $farmer = farmer();

    chat($farmer->phone, 'telur 1 ton 36rb');
    chat($farmer->phone, 'ya');
    chat($farmer->phone, 'telur 1 ton 26rb');
    chat($farmer->phone, 'ya');

    expect(StockReport::query()->where('farmer_id', $farmer->id)->pluck('status')->all())->toBe([StockReport::CANCELLED, StockReport::CONFIRMED]);
});

function pricedMarket(): void
{
    stock(farmer(), 26000);
    stock(farmer(['region_id' => region('gianyar')->id]), 27500);
    app(PriceBoard::class)->recompute(commodity()->id, region('tabanan')->id, now());
    app(PriceBoard::class)->recompute(commodity()->id, region('gianyar')->id, now());
}

it('papan harga publik menampilkan ringkasan pasar otomatis walau AI belum aktif', function () {
    pricedMarket();

    $brief = $this->getJson(route('board.data', ['komoditas' => 'telur']))->assertOk()->json('brief');

    expect($brief['source'])->toBe('aturan')
        ->and($brief['text'])->toContain('termurah di Tabanan (Rp26.000/kg)')->toContain('termahal di Gianyar');
});

it('ringkasan pasar AI dibuat sekali lalu disimpan selama data harga tidak berubah', function () {
    pricedMarket();
    fakeAi(['summary' => 'Harga telur stabil; Tabanan paling murah Rp26.000. Cek https://iklan.test', 'direction' => 'stabil']);

    $first = $this->getJson(route('board.data', ['komoditas' => 'telur']))->json('brief');
    $this->getJson(route('board.data', ['komoditas' => 'telur']))->assertOk();

    expect($first)->source->toBe('ai')->direction->toBe('stabil')
        ->and($first['text'])->toContain('Tabanan paling murah')->not->toContain('https://');
    Http::assertSentCount(1);
});

it('ringkasan pasar memakai versi otomatis dan tidak membanjiri AI saat AI gagal', function () {
    pricedMarket();
    config(['lapor.ai.driver' => 'openai', 'lapor.ai.api_key' => 'kunci-uji', 'lapor.ai.base_url' => 'https://ai.contoh.test/v1']);
    Http::fake(['ai.contoh.test/*' => Http::response([], 500)]);

    expect($this->getJson(route('board.data', ['komoditas' => 'telur']))->json('brief.source'))->toBe('aturan');
    $this->getJson(route('board.data', ['komoditas' => 'telur']))->assertOk();

    Http::assertSentCount(1);
});

it('perintah HARGA di WhatsApp menyertakan ringkasan AI', function () {
    pricedMarket();
    $farmer = farmer();
    fakeAi(['summary' => 'Harga telur naik tipis di Gianyar.', 'direction' => 'naik']);

    expect(chat($farmer->phone, 'harga')[0])->toContain('Harga Telur Ayam Ras per kg')->toContain('Harga telur naik tipis di Gianyar. (ringkasan AI)');
});

it('dasbor koordinator memantau keandalan AI tanpa menampilkan isi pesan', function () {
    $farmer = farmer();
    fakeAi(intent('pertanyaan', ['answer' => 'Datanya belum cukup.']));
    chat($farmer->phone, 'pesan rahasia peternak soal harga');

    $this->actingAs(admin())->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Aktif: openai · model-uji')
        ->assertSee('Memahami chat: 1')
        ->assertSee('100%')
        ->assertDontSee('pesan rahasia peternak');
});

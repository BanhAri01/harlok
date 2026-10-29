<?php

use App\Chat\AiReportParser;
use App\Models\Conversation;
use App\Models\DailyPrice;
use App\Models\Farmer;
use App\Models\OutboundMessage;
use App\Models\StockReport;
use App\Services\PriceBoard;
use App\Services\ReportService;
use App\Support\Phone;
use Illuminate\Support\Facades\RateLimiter;

function simulatorFarmer(array $attributes = []): Farmer
{
    return farmer(['phone' => Phone::SIMULATOR_PREFIX.random_int(100000, 999999), 'channel' => 'simulator', ...$attributes]);
}

it('laporan dari simulator tidak mengirim WhatsApp ke pengepul sungguhan', function () {
    $buyer = buyer(['region_id' => region('tabanan')->id, 'notify_wa' => true]);
    $farmer = simulatorFarmer();

    chat($farmer->phone, 'telur 1 ton 26rb', 'ya');

    expect(StockReport::query()->sole()->status)->toBe(StockReport::CONFIRMED)
        ->and(OutboundMessage::query()->where('phone', $buyer->phone)->exists())->toBeFalse();
});

it('pengepul hanya diberi tahu sekali per peternak dan komoditas dalam masa jeda', function () {
    $buyer = buyer(['region_id' => region('tabanan')->id, 'notify_wa' => true]);
    $farmer = farmer();

    chat($farmer->phone, 'telur 1 ton 26rb', 'ya');
    chat($farmer->phone, 'telur 2 ton 26.500', 'ya');

    expect(OutboundMessage::query()->where('phone', $buyer->phone)->count())->toBe(1);
});

it('peternak yang belum terverifikasi tidak memicu notifikasi pengepul', function () {
    $buyer = buyer(['region_id' => region('tabanan')->id, 'notify_wa' => true]);
    $farmer = farmer(['verified_at' => null]);

    chat($farmer->phone, 'telur 1 ton 26rb', 'ya');

    expect(OutboundMessage::query()->where('phone', $buyer->phone)->exists())->toBeFalse();
});

it('pengepul sungguhan tidak bisa menawar stok simulator, akun demo dan admin bisa', function () {
    $report = stock(simulatorFarmer());

    $this->actingAs(buyer())
        ->postJson(route('buyer.offer', $report), ['price_per_kg' => 26000, 'quantity_kg' => 100])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('price_per_kg');

    $this->actingAs(buyer(['is_demo' => true]))
        ->postJson(route('buyer.offer', $report), ['price_per_kg' => 26000, 'quantity_kg' => 100])
        ->assertOk();
});

it('satu peternak hanya dihitung sekali dalam rata-rata harian', function () {
    $other = farmer();
    $farmer = farmer();
    stock($other, 26000);
    stock($farmer, 30000, attributes: ['status' => StockReport::CLOSED, 'confirmed_at' => now()->subMinutes(10)]);
    stock($farmer, 32000);

    $daily = app(PriceBoard::class)->recompute(commodity()->id, region('tabanan')->id, now());

    expect($daily->reports_count)->toBe(2)
        ->and($daily->avg_price)->toBe(29000);
});

it('harga pembanding tidak memakai laporan peternak itu sendiri sehingga tidak bisa didongkrak bertahap', function () {
    stock(farmer(), 26000);
    app(PriceBoard::class)->recompute(commodity()->id, region('tabanan')->id, now());
    $farmer = farmer();

    chat($farmer->phone, 'telur 1 ton 33rb', 'ya');
    chat($farmer->phone, 'telur 1 ton 42rb', 'ya');

    expect(StockReport::query()->where('farmer_id', $farmer->id)->latest('id')->value('status'))->toBe(StockReport::FLAGGED)
        ->and(DailyPrice::query()->sole()->avg_price)->toBe(29500);
});

it('menolak jumlah stok yang tidak masuk akal', function () {
    $farmer = farmer();

    expect(chat($farmer->phone, 'telur 500 ton 26rb')[0])->toContain('terlalu besar');
    expect(StockReport::query()->exists())->toBeFalse();
});

it('kuota AI harian global dan simulator membatasi pemanggilan', function () {
    config(['lapor.ai.api_key' => 'kunci-uji', 'lapor.ai.daily_global' => 1, 'lapor.ai.daily_simulator' => 1]);
    $parser = app(AiReportParser::class);

    RateLimiter::hit('ai-parse:semua', 86400);
    expect($parser->parse('6281299999999', 'telur dua ton 26 ribu', commodity(), region('tabanan')))->toBeNull()
        ->and(RateLimiter::attempts('ai-parse:6281299999999'))->toBe(0);

    RateLimiter::clear('ai-parse:semua');
    RateLimiter::hit('ai-parse:simulator', 86400);
    $phone = Phone::SIMULATOR_PREFIX.'123456';
    expect($parser->parse($phone, 'telur dua ton 26 ribu', commodity(), region('tabanan')))->toBeNull()
        ->and(RateLimiter::attempts('ai-parse:'.$phone))->toBe(0);
});

it('posisi peta peternak tidak bisa ditebak dari nomor telepon', function () {
    $farmer = farmer();
    $service = app(ReportService::class);
    $first = $service->position($farmer);

    config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

    expect($service->position($farmer))->not->toBe($first);
});

it('batas webhook dihitung per nomor pengirim, bukan per alamat gateway', function () {
    foreach (range(1, 31) as $index) {
        $this->post('/webhook/wa/rahasia-uji', ['sender' => '0812345'.str_pad((string) $index, 5, '0', STR_PAD_LEFT), 'message' => 'halo'])->assertOk();
    }

    foreach (range(1, 30) as $index) {
        $this->post('/webhook/wa/rahasia-uji', ['sender' => '081234500099', 'message' => 'halo '.$index]);
    }

    $this->post('/webhook/wa/rahasia-uji', ['sender' => '081234500099', 'message' => 'lagi'])->assertTooManyRequests();
});

it('perintah rapikan menghapus data simulator lama beserta percakapannya', function () {
    $old = simulatorFarmer();
    stock($old, 26000, attributes: ['confirmed_at' => now()->subDays(3)]);
    Farmer::query()->whereKey($old->id)->update(['updated_at' => now()->subDays(3)]);
    Conversation::query()->create(['phone' => $old->phone, 'state' => 'idle']);
    Conversation::query()->where('phone', $old->phone)->update(['updated_at' => now()->subDays(3)]);
    $fresh = simulatorFarmer();
    $real = farmer(['updated_at' => now()->subDays(30)]);

    $this->artisan('lapor:rapikan')->assertSuccessful();

    expect(Farmer::query()->whereKey($old->id)->exists())->toBeFalse()
        ->and(StockReport::query()->where('farmer_id', $old->id)->exists())->toBeFalse()
        ->and(Conversation::query()->where('phone', $old->phone)->exists())->toBeFalse()
        ->and(Farmer::query()->whereKey($fresh->id)->exists())->toBeTrue()
        ->and(Farmer::query()->whereKey($real->id)->exists())->toBeTrue();
});

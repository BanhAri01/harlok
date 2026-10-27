<?php

use App\Models\Conversation;
use App\Models\DailyPrice;
use App\Models\Farmer;
use App\Models\StockReport;

it('mendaftarkan peternak baru lewat tiga balasan', function () {
    $phone = '6281299990001';

    expect(chat($phone, 'halo')[0])->toContain('Siapa nama');
    expect(chat($phone, 'made sukarta')[0])->toContain('Lokasi');
    expect(chat($phone, '9')[0])->toContain('Komoditas');
    expect(chat($phone, '1')[0])->toContain('Terdaftar');

    $farmer = Farmer::query()->where('phone', $phone)->firstOrFail();
    expect($farmer->name)->toBe('Made Sukarta')
        ->and($farmer->region->slug)->toBe('tabanan')
        ->and($farmer->commodity->slug)->toBe('telur')
        ->and($farmer->isVerified())->toBeFalse();
});

it('meminta konfirmasi sebelum menyimpan laporan lalu menghitung harga rata-rata', function () {
    $farmer = farmer();

    $reply = chat($farmer->phone, 'telur 2 ton 26rb')[0];
    expect($reply)->toContain('2.000 kg')->toContain('Rp26.000/kg')->toContain('YA');
    expect(StockReport::query()->sole()->status)->toBe(StockReport::PENDING);

    $reply = chat($farmer->phone, 'ya')[0];
    expect($reply)->toContain('Tersimpan');

    $report = StockReport::query()->sole();
    expect($report->status)->toBe(StockReport::CONFIRMED)
        ->and(DailyPrice::query()->sole()->avg_price)->toBe(26000);
});

it('membandingkan harga laporan dengan rata-rata wilayah', function () {
    stock(farmer(), 27000);
    app(App\Services\PriceBoard::class)->recompute(commodity()->id, region('tabanan')->id, now());

    $farmer = farmer();
    $reply = chat($farmer->phone, 'telur 1 ton 25.650', 'ya')[0];

    expect($reply)->toContain('Rata-rata Tabanan: Rp27.000')->toContain('5,0% di bawah');
});

it('tidak menghitung harga dari peternak yang belum terverifikasi', function () {
    $farmer = farmer(['verified_at' => null]);

    $reply = chat($farmer->phone, 'telur 1 ton 26rb', 'ya')[0];

    expect($reply)->toContain('belum diverifikasi')
        ->and(StockReport::query()->sole()->status)->toBe(StockReport::CONFIRMED)
        ->and(DailyPrice::query()->count())->toBe(0);
});

it('menahan laporan yang harganya jauh dari pasar untuk dicek koordinator', function () {
    stock(farmer(), 26000);
    app(App\Services\PriceBoard::class)->recompute(commodity()->id, region('tabanan')->id, now());

    $farmer = farmer();
    $reply = chat($farmer->phone, 'telur 1 ton 40rb', 'ya')[0];

    $report = StockReport::query()->where('farmer_id', $farmer->id)->sole();
    expect($reply)->toContain('Koordinator')
        ->and($report->status)->toBe(StockReport::FLAGGED)
        ->and(DailyPrice::query()->sole()->avg_price)->toBe(26000);
});

it('menolak harga yang tidak masuk akal sebelum disimpan', function () {
    $farmer = farmer();

    expect(chat($farmer->phone, 'telur 1 ton 260rb')[0])->toContain('tidak wajar');
    expect(StockReport::query()->count())->toBe(0);
});

it('membatalkan laporan bila peternak membalas ubah', function () {
    $farmer = farmer();

    chat($farmer->phone, 'telur 1 ton 26rb', 'ubah');

    expect(StockReport::query()->sole()->status)->toBe(StockReport::CANCELLED)
        ->and(Conversation::query()->where('phone', $farmer->phone)->value('state'))->toBe('idle');
});

it('laporan baru menutup stok lama dengan komoditas yang sama', function () {
    $farmer = farmer();
    $old = stock($farmer);

    chat($farmer->phone, 'telur 500 kg 26.500', 'ya');

    expect($old->refresh()->status)->toBe(StockReport::CLOSED)
        ->and(StockReport::query()->available()->count())->toBe(1);
});

it('menjawab perintah harga, stok, habis, dan langganan', function () {
    $farmer = farmer();
    stock($farmer, 26000);
    app(App\Services\PriceBoard::class)->recompute(commodity()->id, region('tabanan')->id, now());

    expect(chat($farmer->phone, 'harga')[0])->toContain('Tabanan: Rp26.000');
    expect(chat($farmer->phone, 'stok')[0])->toContain('1.000 kg');
    expect(chat($farmer->phone, 'habis')[0])->toContain('ditandai sudah habis');
    expect(StockReport::query()->available()->count())->toBe(0);

    chat($farmer->phone, 'mulai');
    expect($farmer->refresh()->digest_opt_in)->toBeTrue();
    chat($farmer->phone, 'stop');
    expect($farmer->refresh()->digest_opt_in)->toBeFalse();
});

it('meminta format contoh bila pesan tidak dipahami', function () {
    $farmer = farmer();

    expect(chat($farmer->phone, 'apa kabar')[0])->toContain('belum saya pahami');
    expect(chat($farmer->phone, 'telur 2 ton')[0])->toContain('harga');
});

<?php

use App\Models\Offer;
use App\Models\OutboundMessage;
use App\Models\StockReport;
use App\Services\PriceBoard;

beforeEach(function () {
    stock(farmer(), 26000);
    app(PriceBoard::class)->recompute(commodity()->id, region('tabanan')->id, now());
});

it('mengirim tawaran ke WhatsApp peternak dengan peringatan harga rendah', function () {
    $farmer = farmer();
    $report = stock($farmer, 26000);
    $buyer = buyer(['business_name' => 'UD Sumber Rejeki']);

    $this->actingAs($buyer)
        ->postJson(route('buyer.offer', $report), ['price_per_kg' => 23000, 'quantity_kg' => 1000])
        ->assertOk()
        ->assertJson(['status' => 'sent']);

    $message = OutboundMessage::query()->where('phone', $farmer->phone)->latest('id')->first();
    expect($message->body)->toContain('UD Sumber Rejeki')->toContain('DI BAWAH')->toContain('Rp26.000');
});

it('peternak menerima tawaran dengan membalas 1', function () {
    $farmer = farmer();
    $report = stock($farmer, 26000);
    $buyer = buyer();
    $other = buyer();

    $this->actingAs($other)->postJson(route('buyer.offer', $report), ['price_per_kg' => 25500, 'quantity_kg' => 500])->assertOk();
    $this->actingAs($buyer)->postJson(route('buyer.offer', $report), ['price_per_kg' => 26500, 'quantity_kg' => 1000])->assertOk();

    chat($farmer->phone, '1');

    $offer = Offer::query()->where('buyer_id', $buyer->id)->sole();
    expect($offer->status)->toBe(Offer::ACCEPTED)
        ->and($report->refresh()->status)->toBe(StockReport::SOLD)
        ->and(Offer::query()->where('buyer_id', $other->id)->value('status'))->toBe(Offer::REJECTED);

    expect(OutboundMessage::query()->where('phone', $buyer->phone)->latest('id')->value('body'))
        ->toContain('Sepakat')->toContain('0'.substr($farmer->phone, 2));
});

it('peternak menawar balik lalu pengepul menyetujui dari web', function () {
    $farmer = farmer();
    $report = stock($farmer, 26000);
    $buyer = buyer();

    $this->actingAs($buyer)->postJson(route('buyer.offer', $report), ['price_per_kg' => 24000, 'quantity_kg' => 1000])->assertOk();

    expect(chat($farmer->phone, '3')[0])->toContain('Berapa harga');
    expect(chat($farmer->phone, '27rb')[0])->toContain('Rp27.000');

    $offer = Offer::query()->sole();
    expect($offer->status)->toBe(Offer::COUNTERED)->and($offer->counter_price)->toBe(27000);

    $this->actingAs($buyer)->post(route('buyer.offers.accept', $offer))->assertRedirect();

    expect($offer->refresh()->status)->toBe(Offer::ACCEPTED)
        ->and($offer->price_per_kg)->toBe(27000)
        ->and(OutboundMessage::query()->where('phone', $farmer->phone)->latest('id')->value('body'))->toContain('Sepakat');
});

it('peternak menolak tawaran dengan membalas 2', function () {
    $farmer = farmer();
    $report = stock($farmer);
    $buyer = buyer();

    $this->actingAs($buyer)->postJson(route('buyer.offer', $report), ['price_per_kg' => 25000, 'quantity_kg' => 100])->assertOk();
    chat($farmer->phone, '2');

    expect(Offer::query()->sole()->status)->toBe(Offer::REJECTED)
        ->and($report->refresh()->status)->toBe(StockReport::CONFIRMED);
});

it('pengepul tidak bisa menyetujui atau membatalkan tawaran milik orang lain', function () {
    $report = stock(farmer());
    $owner = buyer();

    $this->actingAs($owner)->postJson(route('buyer.offer', $report), ['price_per_kg' => 25000, 'quantity_kg' => 100])->assertOk();
    $offer = Offer::query()->sole();

    $this->actingAs(buyer())->post(route('buyer.offers.cancel', $offer))->assertNotFound();
    $this->actingAs(buyer())->post(route('buyer.offers.accept', $offer))->assertNotFound();
    expect($offer->refresh()->status)->toBe(Offer::SENT);
});

it('akun demo tidak bisa menawar peternak sungguhan', function () {
    $report = stock(farmer());

    $this->actingAs(buyer(['is_demo' => true]))
        ->postJson(route('buyer.offer', $report), ['price_per_kg' => 25000, 'quantity_kg' => 100])
        ->assertUnprocessable();

    $simulated = stock(farmer(['phone' => 'simabcdef123456', 'channel' => 'simulator']));
    $this->actingAs(buyer(['is_demo' => true]))
        ->postJson(route('buyer.offer', $simulated), ['price_per_kg' => 25000, 'quantity_kg' => 100])
        ->assertOk();
});

it('menolak tawaran melebihi stok, harga tak wajar, atau stok yang sudah terjual', function () {
    $report = stock(farmer(), 26000, 500);
    $buyer = buyer();

    $this->actingAs($buyer)->postJson(route('buyer.offer', $report), ['price_per_kg' => 25000, 'quantity_kg' => 900])->assertUnprocessable();
    $this->actingAs($buyer)->postJson(route('buyer.offer', $report), ['price_per_kg' => 500, 'quantity_kg' => 100])->assertUnprocessable();

    $report->update(['status' => StockReport::SOLD]);
    $this->actingAs($buyer)->postJson(route('buyer.offer', $report), ['price_per_kg' => 25000, 'quantity_kg' => 100])->assertUnprocessable();
});

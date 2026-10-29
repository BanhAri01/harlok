<?php

use App\Models\DailyPrice;
use App\Models\Farmer;
use App\Models\InboundMessage;
use App\Models\OutboundMessage;
use App\Models\User;
use App\Services\PriceBoard;

it('menolak webhook dengan rahasia yang salah', function () {
    $this->post('/webhook/wa/salah', ['sender' => '081234567890', 'message' => 'halo'])->assertNotFound();
    expect(InboundMessage::query()->count())->toBe(0);
});

it('memproses pesan WhatsApp masuk dan membalas', function () {
    $this->post('/webhook/wa/rahasia-uji', ['sender' => '081234567890', 'message' => 'halo', 'inboxid' => 'abc1'])
        ->assertOk()->assertJson(['status' => 'accepted']);

    $inbound = InboundMessage::query()->sole();
    expect($inbound->phone)->toBe('6281234567890')
        ->and($inbound->status)->toBe('processed')
        ->and(OutboundMessage::query()->where('phone', '6281234567890')->value('body'))->toContain('Selamat datang');
});

it('memproses setiap pesan Fonnte paket gratis yang selalu mengirim inboxid 0 dalam mode lid', function () {
    $payload = fn (string $text) => [
        'device' => '085133642784', 'sender' => '6285179546032', 'pengirim' => '6285179546032', 'senderlid' => '212609857003555@lid',
        'mode' => 'lid', 'isgroup' => false, 'inboxid' => 0, 'message' => $text, 'pesan' => $text, 'name' => 'Ari',
    ];

    $this->post('/webhook/wa/rahasia-uji', $payload('halo'))->assertJson(['status' => 'accepted']);
    $this->post('/webhook/wa/rahasia-uji', $payload('Made'))->assertJson(['status' => 'accepted']);

    expect(InboundMessage::query()->where('phone', '6285179546032')->count())->toBe(2)
        ->and(OutboundMessage::query()->where('phone', '6285179546032')->count())->toBe(2);
});

it('tidak membalas ke ID privasi WhatsApp yang bukan nomor telepon', function () {
    $this->post('/webhook/wa/rahasia-uji', ['sender' => '212609857003555@lid', 'message' => 'halo'])->assertJson(['reason' => 'lid']);
    $this->post('/webhook/wa/rahasia-uji', ['sender' => '6281234567890', 'isgroup' => true, 'message' => 'halo'])->assertJson(['reason' => 'group']);

    expect(InboundMessage::query()->count())->toBe(0);
});

it('mengabaikan pesan grup dan pesan ganda', function () {
    $this->post('/webhook/wa/rahasia-uji', ['sender' => '1203630-123@g.us', 'message' => 'halo'])->assertJson(['reason' => 'group']);
    $this->post('/webhook/wa/rahasia-uji', ['sender' => '081234567890', 'message' => 'halo', 'member' => '0812'])->assertJson(['reason' => 'group']);

    $this->post('/webhook/wa/rahasia-uji', ['sender' => '081234567890', 'message' => 'halo', 'inboxid' => 'x1'])->assertJson(['status' => 'accepted']);
    $this->post('/webhook/wa/rahasia-uji', ['sender' => '081234567890', 'message' => 'halo', 'inboxid' => 'x1'])->assertJson(['reason' => 'duplicate']);

    expect(InboundMessage::query()->count())->toBe(1);
});

it('membatasi banjir pesan dari satu nomor', function () {
    foreach (range(1, 12) as $index) {
        $this->post('/webhook/wa/rahasia-uji', ['sender' => '081234567891', 'message' => 'halo '.$index]);
    }

    $this->post('/webhook/wa/rahasia-uji', ['sender' => '081234567891', 'message' => 'lagi'])->assertJson(['reason' => 'rate']);
});

it('simulator membalas tanpa mengirim WhatsApp sungguhan', function () {
    $this->postJson(route('simulator.send'), ['message' => 'halo'])
        ->assertOk()
        ->assertJsonPath('messages.0.body', fn ($body) => str_contains($body, 'Selamat datang'));

    expect(OutboundMessage::query()->sole()->channel)->toBe('simulator');
});

it('hanya koordinator yang bisa membuka panel admin', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    $this->actingAs(buyer())->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs(admin())->get(route('admin.dashboard'))->assertOk();
});

it('akun mitra API tidak bisa masuk ke web', function () {
    $partner = User::factory()->create(['role' => User::PARTNER, 'email' => 'mitra@contoh.id']);

    $this->post(route('login'), ['email' => 'mitra@contoh.id', 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('verifikasi koordinator memasukkan harga peternak ke rata-rata', function () {
    $farmer = farmer(['verified_at' => null]);
    stock($farmer, 26000);
    app(PriceBoard::class)->recompute(commodity()->id, region('tabanan')->id, now());
    expect(DailyPrice::query()->count())->toBe(0);

    $this->actingAs(admin())->post(route('admin.farmers.verify', $farmer), ['verified' => 1])->assertRedirect();

    expect($farmer->refresh()->isVerified())->toBeTrue()
        ->and(DailyPrice::query()->sole()->avg_price)->toBe(26000);
});

it('akun simulator tidak bisa diverifikasi', function () {
    $farmer = farmer(['phone' => 'simzzzzzzzz0001', 'channel' => 'simulator', 'verified_at' => null]);

    $this->actingAs(admin())->post(route('admin.farmers.verify', $farmer), ['verified' => 1])->assertStatus(422);
    expect(Farmer::query()->find($farmer->id)->isVerified())->toBeFalse();
});

it('papan harga publik tidak membocorkan nama atau nomor peternak', function () {
    $farmer = farmer(['name' => 'Rahasia Sekali']);
    stock($farmer);

    $response = $this->getJson(route('board.data', ['komoditas' => 'telur']))->assertOk();

    expect($response->getContent())->not->toContain('Rahasia')->not->toContain($farmer->phone);
});

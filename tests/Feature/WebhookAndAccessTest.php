<?php

use App\Models\DailyPrice;
use App\Models\Farmer;
use App\Models\InboundMessage;
use App\Models\Offer;
use App\Models\OutboundMessage;
use App\Models\StockReport;
use App\Models\User;
use App\Services\PriceBoard;
use App\Support\Phone;

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

it('nomor baru hanya dilayani bila pesannya memuat kata kunci HARLOK', function () {
    config(['lapor.whatsapp.start_keyword' => 'harlok']);

    expect(chat('6281234567001', 'kenapa gitu'))->toBe([])
        ->and(OutboundMessage::query()->count())->toBe(0);

    expect(chat('6281234567001', 'Harlok')[0])->toContain('Selamat datang');
    expect(chat('6281234567001', 'Made')[0])->toContain('Salam kenal, Made');
});

it('peternak terdaftar tetap dilayani tanpa kata kunci dan simulator diberi petunjuk', function () {
    config(['lapor.whatsapp.start_keyword' => 'harlok']);
    $farmer = farmer();

    expect(chat($farmer->phone, 'telur 1 ton 26rb')[0])->toContain('Mohon cek');
    expect(chat(Phone::SIMULATOR_PREFIX.'abc123', 'halo')[0])->toContain('HARLOK');
});

it('teks halaman bertingkat memakai bahasa Indonesia', function () {
    foreach (range(1, 30) as $index) {
        farmer();
    }

    $this->actingAs(admin())->get(route('admin.farmers'))->assertOk()->assertSee('Menampilkan')->assertDontSee('Showing');
});

it('STOP menghentikan pendaftaran di langkah mana pun lalu nomor itu tidak dibalas lagi', function () {
    config(['lapor.whatsapp.start_keyword' => 'harlok']);

    chat('6281234567002', 'HARLOK');
    expect(chat('6281234567002', 'Stop')[0])->toContain('pendaftaran dihentikan');
    expect(chat('6281234567002', 'halo'))->toBe([])
        ->and(Farmer::query()->count())->toBe(0);

    chat('6281234567002', 'harlok');
    chat('6281234567002', 'Made');
    expect(chat('6281234567002', 'berhenti!')[0])->toContain('pendaftaran dihentikan')
        ->and(chat('6281234567002', 'stop'))->toBe([]);
});

it('STOP membuat bot diam bagi peternak terdaftar sampai ia mengetik HARLOK lagi', function () {
    config(['lapor.whatsapp.start_keyword' => 'harlok']);
    $farmer = farmer(['digest_opt_in' => true]);
    $active = stock($farmer);
    $offer = Offer::query()->create(['stock_report_id' => $active->id, 'buyer_id' => buyer()->id, 'price_per_kg' => 25000, 'quantity_kg' => 500, 'status' => Offer::SENT]);

    expect(chat($farmer->phone, 'telur 1 ton 26rb')[0])->toContain('Mohon cek');
    expect(chat($farmer->phone, 'STOP')[0])->toContain('berhenti');

    expect($farmer->refresh()->isStopped())->toBeTrue()
        ->and($farmer->digest_opt_in)->toBeFalse()
        ->and($active->refresh()->status)->toBe(StockReport::CLOSED)
        ->and($offer->refresh()->status)->toBe(Offer::CANCELLED)
        ->and(StockReport::query()->where('farmer_id', $farmer->id)->where('status', StockReport::PENDING)->exists())->toBeFalse();

    expect(chat($farmer->phone, 'halo'))->toBe([])
        ->and(chat($farmer->phone, 'telur 2 ton 27rb'))->toBe([])
        ->and(chat($farmer->phone, 'menu'))->toBe([]);

    $back = chat($farmer->phone, 'Harlok');
    expect($back[0])->toContain('Selamat datang kembali')
        ->and($back[1])->toContain('Menu')
        ->and($farmer->refresh()->isStopped())->toBeFalse()
        ->and(chat($farmer->phone, 'harga')[0])->not->toBeEmpty();
});

it('peternak yang berhenti tetap bisa KELUAR untuk menghapus datanya', function () {
    $farmer = farmer();

    chat($farmer->phone, 'stop');
    expect(chat($farmer->phone, 'keluar')[0])->toContain('dihapus permanen');
    expect(chat($farmer->phone, 'ya')[0])->toContain('sudah dihapus')
        ->and(Farmer::query()->find($farmer->id))->toBeNull();
});

it('KELUAR menghapus data peternak setelah konfirmasi dan harganya tidak lagi dihitung', function () {
    config(['lapor.whatsapp.start_keyword' => 'harlok']);
    $farmer = farmer();
    stock($farmer, 26000);
    stock(farmer(), 30000);
    app(PriceBoard::class)->recompute(commodity()->id, region('tabanan')->id, now());
    $this->post('/webhook/wa/rahasia-uji', ['sender' => $farmer->phone, 'message' => 'halo', 'inboxid' => 'k1']);

    expect(chat($farmer->phone, 'Keluar')[0])->toContain('dihapus permanen');
    expect(chat($farmer->phone, 'YA')[0])->toContain('sudah dihapus');

    expect(Farmer::query()->find($farmer->id))->toBeNull()
        ->and(StockReport::query()->where('farmer_id', $farmer->id)->exists())->toBeFalse()
        ->and(InboundMessage::query()->where('phone', $farmer->phone)->exists())->toBeFalse()
        ->and(DailyPrice::query()->sole()->avg_price)->toBe(30000)
        ->and(chat($farmer->phone, 'halo'))->toBe([]);
});

it('KELUAR batal bila peternak tidak membalas YA', function () {
    $farmer = farmer();

    chat($farmer->phone, 'keluar');
    expect(chat($farmer->phone, 'tidak jadi')[0])->toContain('tidak jadi keluar')
        ->and(Farmer::query()->find($farmer->id))->not->toBeNull();
});

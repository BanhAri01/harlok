<?php

use App\Models\User;
use App\Services\PriceBoard;

beforeEach(function () {
    $this->farmer = farmer(['name' => 'Nama Peternak Rahasia']);
    stock($this->farmer, 26000);
    stock(farmer(), 27000);
    app(PriceBoard::class)->recompute(commodity()->id, region('tabanan')->id, now());
});

function partnerToken(array $abilities = ['prices:read']): string
{
    $partner = User::factory()->create(['role' => User::PARTNER]);

    return $partner->createToken('uji', $abilities)->plainTextToken;
}

it('menolak permintaan tanpa token', function () {
    $this->getJson('/api/v1/harga?komoditas=telur')->assertUnauthorized();
});

it('menolak token tanpa izin baca harga', function () {
    $this->withToken(partnerToken(['lain']))->getJson('/api/v1/harga?komoditas=telur')->assertForbidden();
});

it('menolak token milik pengepul', function () {
    $token = buyer()->createToken('uji', ['prices:read'])->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/harga?komoditas=telur')->assertForbidden();
});

it('mengembalikan harga gabungan per wilayah tanpa data pribadi', function () {
    $response = $this->withToken(partnerToken())
        ->getJson('/api/v1/harga?komoditas=telur&wilayah=tabanan')
        ->assertOk()
        ->assertJsonPath('data.0.nama_wilayah', 'Tabanan')
        ->assertJsonPath('data.0.rata_rata', 26500)
        ->assertJsonPath('data.0.terendah', 26000)
        ->assertJsonPath('data.0.jumlah_laporan', 2);

    expect($response->getContent())->not->toContain('Rahasia')->not->toContain($this->farmer->phone);
});

it('memvalidasi parameter komoditas dan wilayah', function () {
    $this->withToken(partnerToken())->getJson('/api/v1/harga?komoditas=emas')->assertUnprocessable();
    $this->withToken(partnerToken())->getJson('/api/v1/harga/riwayat?komoditas=telur&wilayah=jakarta')->assertUnprocessable();
});

it('mengembalikan riwayat harian', function () {
    $this->withToken(partnerToken())
        ->getJson('/api/v1/harga/riwayat?komoditas=telur&wilayah=tabanan&hari=7')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.median', 26500);
});

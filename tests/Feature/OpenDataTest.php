<?php

use App\Services\PriceBoard;

beforeEach(function () {
    stock(farmer(['name' => 'Nama Peternak Rahasia']), 26000);
    stock(farmer(), 27000);
    app(PriceBoard::class)->recompute(commodity()->id, region('tabanan')->id, now());
});

it('menampilkan widget harga yang boleh disematkan di situs lain', function () {
    $response = $this->get('/widget/harga?komoditas=telur&wilayah=tabanan')
        ->assertOk()
        ->assertSee('Harga Telur')
        ->assertSee('Tabanan')
        ->assertSee('26.500')
        ->assertDontSee('Nama Peternak Rahasia')
        ->assertHeaderMissing('X-Frame-Options');

    expect($response->headers->get('Content-Security-Policy'))->toContain('frame-ancestors *');
});

it('halaman lain tetap tidak boleh disematkan', function () {
    $this->get('/')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
});

it('menolak komoditas atau wilayah yang tidak dikenal di widget', function () {
    $this->get('/widget/harga?komoditas=emas')->assertNotFound();
    $this->get('/widget/harga?komoditas=telur&wilayah=jakarta')->assertNotFound();
});

it('mengunduh data terbuka csv berisi angka gabungan saja', function () {
    $response = $this->get('/data/harga.csv?komoditas=telur&hari=7')->assertOk();
    $csv = $response->streamedContent();

    expect($response->headers->get('Content-Type'))->toContain('text/csv')
        ->and($csv)->toStartWith('tanggal,komoditas,wilayah,provinsi,rata_rata')
        ->and($csv)->toContain(now()->toDateString().',telur,tabanan,Bali,26500')
        ->and($csv)->not->toContain('Rahasia')
        ->and($csv)->not->toContain('62812');
});

it('membatasi rentang hari data terbuka', function () {
    $this->get('/data/harga.csv?hari=99999')->assertOk();
    $this->get('/data/harga.csv?komoditas=emas')->assertNotFound();
});

it('menampilkan pembuat widget di halaman data terbuka', function () {
    $this->get('/dokumentasi-api')->assertOk()->assertSee('Salin kode')->assertSee('/data/harga.csv', false);
});

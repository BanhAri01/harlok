<?php

use App\Chat\ReportParser;

dataset('pesan', [
    'ton dan rb' => ['telur 2ton harga 26 rb', 'telur', 2000, 26000],
    'titik ribuan' => ['Telor 500 kg 26.500', 'telur', 500, 26500],
    'per peti' => ['telur 20 peti @390rb/peti', 'telur', 300, 26000],
    'kwintal dan per kg' => ['cabai rawit 3 kwintal 45.000/kg', 'cabai-rawit', 300, 45000],
    'koma desimal' => ['telur 1,5 ton 26,5rb', 'telur', 1500, 26500],
    'rp di depan' => ['ayam potong 800 kilo rp21000', 'ayam-broiler', 800, 21000],
    'k sebagai ribu' => ['jagung 2 ton 5.6k', 'jagung', 2000, 5600],
    'harga tanpa satuan' => ['taluh 300 kg harga 27', 'telur', 300, 27000],
    'karung' => ['beras 10 karung 13rb', 'beras', 500, 13000],
]);

it('membaca jumlah dan harga dari bahasa sehari-hari', function (string $text, string $slug, float $quantity, int $price) {
    $parsed = app(ReportParser::class)->parse($text);

    expect($parsed->commodity?->slug)->toBe($slug)
        ->and($parsed->quantityKg)->toEqual($quantity)
        ->and($parsed->pricePerKg)->toBe($price)
        ->and($parsed->isComplete())->toBeTrue();
})->with('pesan');

it('mengenali wilayah yang disebut dalam pesan', function () {
    $parsed = app(ReportParser::class)->parse('telur 1 ton 26rb gunung kidul');

    expect($parsed->region?->slug)->toBe('gunungkidul');
});

it('memakai komoditas bawaan peternak bila tidak disebut', function () {
    $parsed = app(ReportParser::class)->parse('2 ton 26rb', commodity('telur'));

    expect($parsed->commodity->slug)->toBe('telur')->and($parsed->isComplete())->toBeTrue();
});

it('menandai data yang kurang', function () {
    $parsed = app(ReportParser::class)->parse('telur 2 ton');

    expect($parsed->isComplete())->toBeFalse()->and($parsed->missing())->toBe(['harga']);
});

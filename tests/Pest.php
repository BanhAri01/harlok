<?php

use App\Chat\ChatBot;
use App\Models\Commodity;
use App\Models\Farmer;
use App\Models\Region;
use App\Models\StockReport;
use App\Models\User;
use Database\Seeders\MarketSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => $this->seed(MarketSeeder::class))
    ->in('Feature');

function region(string $slug): Region
{
    return Region::query()->where('slug', $slug)->firstOrFail();
}

function commodity(string $slug = 'telur'): Commodity
{
    return Commodity::query()->where('slug', $slug)->firstOrFail();
}

function farmer(array $attributes = []): Farmer
{
    static $sequence = 0;
    $sequence++;

    return Farmer::query()->create([
        'name' => 'Peternak '.$sequence,
        'phone' => '6281200000'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
        'region_id' => region('tabanan')->id,
        'commodity_id' => commodity()->id,
        'channel' => 'wa',
        'verified_at' => now(),
        ...$attributes,
    ]);
}

function chat(string $phone, string ...$messages): array
{
    $replies = [];
    foreach ($messages as $message) {
        $replies = app(ChatBot::class)->handle($phone, $message);
    }

    return $replies;
}

function stock(Farmer $farmer, int $price = 26000, float $quantity = 1000, array $attributes = []): StockReport
{
    return StockReport::query()->create([
        'farmer_id' => $farmer->id,
        'commodity_id' => $farmer->commodity_id,
        'region_id' => $farmer->region_id,
        'quantity_kg' => $quantity,
        'price_per_kg' => $price,
        'status' => StockReport::CONFIRMED,
        'parsed_by' => 'pattern',
        'raw_text' => 'uji',
        'lat' => -8.5,
        'lng' => 115.1,
        'confirmed_at' => now(),
        'expires_at' => now()->addDays(3),
        ...$attributes,
    ]);
}

function buyer(array $attributes = []): User
{
    return User::factory()->create(['role' => User::BUYER, 'phone' => '6285700000'.random_int(100, 999), ...$attributes]);
}

function admin(): User
{
    return User::factory()->create(['role' => User::ADMIN]);
}

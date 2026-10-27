<?php

use App\Http\Controllers\Api\PriceApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'abilities:prices:read', 'role:partner,admin', 'throttle:partner-api'])->group(function () {
    Route::get('/komoditas', [PriceApiController::class, 'commodities']);
    Route::get('/wilayah', [PriceApiController::class, 'regions']);
    Route::get('/harga', [PriceApiController::class, 'prices']);
    Route::get('/harga/riwayat', [PriceApiController::class, 'history']);
});

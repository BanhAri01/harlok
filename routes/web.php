<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\BuyerController;
use App\Http\Controllers\SimulatorController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BoardController::class, 'index'])->name('home');
Route::get('/papan/data', [BoardController::class, 'data'])->middleware('throttle:60,1')->name('board.data');
Route::get('/dokumentasi-api', [BoardController::class, 'apiDocs'])->name('api.docs');

Route::post('/webhook/wa/{secret}', WhatsAppWebhookController::class)->middleware('throttle:240,1')->name('webhooks.wa');

Route::middleware('simulator')->prefix('coba')->name('simulator.')->group(function () {
    Route::get('/', [SimulatorController::class, 'show'])->name('show');
    Route::post('/kirim', [SimulatorController::class, 'send'])->middleware('throttle:40,1')->name('send');
    Route::get('/pesan', [SimulatorController::class, 'messages'])->middleware('throttle:120,1')->name('messages');
    Route::post('/ulang', [SimulatorController::class, 'reset'])->middleware('throttle:10,1')->name('reset');
});

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'show'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:20,1');
});

Route::post('/keluar', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:buyer,admin'])->prefix('pengepul')->name('buyer.')->group(function () {
    Route::get('/', [BuyerController::class, 'dashboard'])->name('dashboard');
    Route::get('/data', [BuyerController::class, 'data'])->middleware('throttle:60,1')->name('data');
    Route::post('/stok/{report}/tawar', [BuyerController::class, 'offer'])->middleware('throttle:20,1')->name('offer');
    Route::get('/tawaran', [BuyerController::class, 'offers'])->name('offers');
    Route::post('/tawaran/{offer}/terima', [BuyerController::class, 'acceptCounter'])->name('offers.accept');
    Route::post('/tawaran/{offer}/batal', [BuyerController::class, 'cancel'])->name('offers.cancel');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/peternak', [AdminController::class, 'farmers'])->name('farmers');
    Route::post('/peternak/{farmer}/verifikasi', [AdminController::class, 'verify'])->name('farmers.verify');
    Route::post('/laporan/{report}/setujui', [AdminController::class, 'approve'])->name('reports.approve');
    Route::post('/laporan/{report}/tolak', [AdminController::class, 'reject'])->name('reports.reject');
    Route::get('/pengguna', [AdminController::class, 'users'])->name('users');
    Route::post('/pengguna', [AdminController::class, 'storeUser'])->name('users.store');
    Route::post('/pengguna/{user}/status', [AdminController::class, 'toggleUser'])->name('users.toggle');
    Route::get('/pengguna/{user}/token', [AdminController::class, 'tokens'])->name('tokens');
    Route::post('/pengguna/{user}/token', [AdminController::class, 'createToken'])->name('tokens.store');
    Route::delete('/token/{token}', [AdminController::class, 'revokeToken'])->name('tokens.destroy');
    Route::get('/pesan', [AdminController::class, 'messages'])->name('messages');
    Route::get('/audit', [AdminController::class, 'audit'])->name('audit');
});

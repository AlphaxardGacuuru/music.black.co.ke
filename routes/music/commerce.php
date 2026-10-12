<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\BoughtAudioController;
use App\Http\Controllers\BoughtVideoController;
use App\Http\Controllers\CartAudioController;
use App\Http\Controllers\CartVideoController;
use App\Http\Controllers\DecoController;
use App\Http\Controllers\MPESATransactionController;
use App\Http\Controllers\SongPayoutController;
use Illuminate\Support\Facades\Route;

/*
 * Commerce: carts, bought songs, decos, song payouts and payments.
 */

// Carts (index is empty for guests, adding needs a user)
Route::apiResource('cart-audios', CartAudioController::class)->except(['store']);
Route::apiResource('cart-videos', CartVideoController::class)->except(['store']);

// Bought songs (index is empty for guests). Buying itself happens when an
// M-Pesa payment lands, see App\Listeners\BuyCartListener.
Route::apiResource('bought-audios', BoughtAudioController::class);
Route::apiResource('bought-videos', BoughtVideoController::class);

// Stubs in OLD as well
Route::apiResource('decos', DecoController::class);

// Artist's bought songs
Route::get('artist/bought-videos/{username}', [BoughtVideoController::class, 'artistBoughtVideos']);
Route::get('artist/bought-audios/{username}', [BoughtAudioController::class, 'artistBoughtAudios']);

// OLD Kopokopo STK push callback URL, kept so callbacks for STK pushes sent
// by OLD still land. NEW sends api/mpesa-transactions as the callback.
Route::post('kopokopo', [MPESATransactionController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('cart-audios', [CartAudioController::class, 'store'])->name('cart-audios.store');
    Route::post('cart-videos', [CartVideoController::class, 'store'])->name('cart-videos.store');

    // Kopokopo STK Push
    Route::post('stk-push', [MPESATransactionController::class, 'stkPush']);

    // Artists' earnings from song sales, paid out to M-Pesa.
    Route::apiResource('song-payouts', SongPayoutController::class)->only(['index', 'store']);
});

/*
 * Admin. OLD only required auth:sanctum. GET admin/users and
 * GET admin/kopokopo-recipients are already served by NEW (routes/api.php).
 */
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('admin', [AdminController::class, 'admin']);
    Route::get('videos', [AdminController::class, 'videos']);
    Route::get('audios', [AdminController::class, 'audios']);
    Route::get('song-payouts', [AdminController::class, 'songPayouts']);
});

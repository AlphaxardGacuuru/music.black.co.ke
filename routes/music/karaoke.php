<?php

use App\Http\Controllers\FilePond\KaraokeFilePondController;
use App\Http\Controllers\KaraokeAudioController;
use App\Http\Controllers\KaraokeCommentController;
use App\Http\Controllers\KaraokeCommentLikeController;
use App\Http\Controllers\KaraokeController;
use App\Http\Controllers\KaraokeLikeController;
use App\Http\Controllers\SavedKaraokeController;
use Illuminate\Support\Facades\Route;

/*
 * Karaoke
 */
$karaokeResources = [
    'karaokes' => KaraokeController::class,
    'karaoke-comments' => KaraokeCommentController::class,
    'karaoke-comment-likes' => KaraokeCommentLikeController::class,
    'karaoke-likes' => KaraokeLikeController::class,
    'karaoke-audios' => KaraokeAudioController::class,
    'saved-karaokes' => SavedKaraokeController::class,
];

// Read endpoints are public (guests get hasLiked/hasSaved false).
Route::apiResources($karaokeResources, ['only' => ['index', 'show']]);

// Write endpoints need the current user.
Route::middleware('auth:sanctum')->group(function () use ($karaokeResources) {
    Route::apiResources($karaokeResources, ['only' => ['store', 'update', 'destroy']]);

    Route::prefix('filepond')->controller(KaraokeFilePondController::class)->group(function () {
        Route::post('karaokes', 'storeKaraoke');
        Route::delete('karaokes/{id}', 'destroyKaraoke');
    });
});

<?php

use App\Http\Controllers\AudioAlbumController;
use App\Http\Controllers\AudioCommentController;
use App\Http\Controllers\AudioCommentLikeController;
use App\Http\Controllers\AudioController;
use App\Http\Controllers\AudioLikeController;
use App\Http\Controllers\FilePond\AudioFilePondController;
use Illuminate\Support\Facades\Route;

/*
 * Audio
 */

// Registered before the "audios" resource so "audios/download" is not
// swallowed by "audios/{audio}". The frontend calls "audios/download/{id}".
Route::get('audios/download/{id?}', [AudioController::class, 'download']);

// Audio Charts
Route::get('audio-charts/newly-released', [AudioController::class, 'newlyReleased']);
Route::get('audio-charts/trending', [AudioController::class, 'trending']);
Route::get('audio-charts/top-downloaded', [AudioController::class, 'topDownloaded']);
Route::get('audio-charts/top-liked', [AudioController::class, 'topLiked']);

// Artist
Route::get('artist/audio-albums/{username}', [AudioAlbumController::class, 'artistAudioAlbums']);
Route::get('artist/audios/{username}', [AudioController::class, 'artistAudios']);

// Public reads
Route::apiResource('audios', AudioController::class)->only(['index', 'show']);
Route::apiResource('audio-comments', AudioCommentController::class)->only(['index', 'show']);
Route::apiResource('audio-albums', AudioAlbumController::class)->only(['index', 'show']);

// Writes need the current user
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('audios', AudioController::class)->only(['store', 'update']);
    Route::apiResource('audio-likes', AudioLikeController::class)->only(['store']);
    Route::apiResource('audio-comments', AudioCommentController::class)->only(['store', 'destroy']);
    Route::apiResource('audio-comment-likes', AudioCommentLikeController::class)->only(['store']);
    Route::apiResource('audio-albums', AudioAlbumController::class)->only(['store', 'update']);
});

// Filepond
Route::middleware('auth:sanctum')->prefix('filepond')->controller(AudioFilePondController::class)->group(function () {
    Route::post('audio-thumbnail', 'storeAudioThumbnail');
    Route::post('audio-thumbnail/{id}', 'updateAudioThumbnail');
    Route::post('audio', 'storeAudio');
    Route::post('audio/{id}', 'updateAudio');
    Route::delete('audio-thumbnail/{id}', 'destoryAudioThumbnail');
    Route::delete('audio/{id}', 'destoryAudio');
});

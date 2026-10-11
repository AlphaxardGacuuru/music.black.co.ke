<?php

use App\Http\Controllers\FilePond\VideoFilePondController;
use App\Http\Controllers\VideoAlbumController;
use App\Http\Controllers\VideoCommentController;
use App\Http\Controllers\VideoCommentLikeController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\VideoLikeController;
use Illuminate\Support\Facades\Route;

/*
 * Video
 */

// Download. Registered before the videos resource so "download" is not
// swallowed by videos/{video}. The OLD frontend calls videos/download/{id};
// videos/download?id= is also accepted.
Route::get('videos/download/{id?}', [VideoController::class, 'download']);

// Public reads
Route::apiResource('videos', VideoController::class)->only(['index', 'show']);
Route::apiResource('video-comments', VideoCommentController::class)->only(['index', 'show']);
Route::apiResource('video-albums', VideoAlbumController::class)->only(['index', 'show']);
Route::apiResource('video-likes', VideoLikeController::class)->only(['index', 'show']);
Route::apiResource('video-comment-likes', VideoCommentLikeController::class)->only(['index', 'show']);

// Video Charts
Route::get('video-charts/newly-released', [VideoController::class, 'newlyReleased']);
Route::get('video-charts/trending', [VideoController::class, 'trending']);
Route::get('video-charts/top-downloaded', [VideoController::class, 'topDownloaded']);
Route::get('video-charts/top-liked', [VideoController::class, 'topLiked']);
Route::get('artist/video-albums/{username}', [VideoAlbumController::class, 'artistVideoAlbums']);
Route::get('artist/videos/{username}', [VideoController::class, 'artistVideos']);

Route::middleware('auth:sanctum')->group(function() {
    Route::apiResource('videos', VideoController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('video-likes', VideoLikeController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('video-comments', VideoCommentController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('video-comment-likes', VideoCommentLikeController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('video-albums', VideoAlbumController::class)->only(['store', 'update', 'destroy']);
});

// Filepond: uploading needs an account, and replacing a video's files needs
// to be its owner (see VideoFilePondController).
Route::middleware('auth:sanctum')
    ->prefix('filepond')
    ->controller(VideoFilePondController::class)
    ->group(function() {
        Route::post('video-thumbnail', 'storeVideoThumbnail');
        Route::post('video-thumbnail/{id}', 'updateVideoThumbnail');
        Route::post('video', 'storeVideo');
        Route::post('video/{id}', 'updateVideo');
        Route::delete('video-thumbnail/{id}', 'destoryVideoThumbnail');
        Route::delete('video/{id}', 'destoryVideo');
    });

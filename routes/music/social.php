<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\FilePond\SocialFilePondController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\PostCommentController;
use App\Http\Controllers\PostCommentLikeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostLikeController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StoryController;
use Illuminate\Support\Facades\Route;

/*
 * Social: posts, polls, stories, follows, chats and search
 */

// Public reads
Route::apiResource('posts', PostController::class)->only(['index', 'show']);
Route::apiResource('post-comments', PostCommentController::class)->only(['index', 'show']);
Route::apiResource('stories', StoryController::class)->only(['index', 'show']);
Route::apiResource('chats', ChatController::class)->only(['index', 'show']);
Route::apiResource('search', SearchController::class)->only(['index']);

// Posts
Route::get('artist/posts/{username}', [PostController::class, 'artistPosts']);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('posts', PostController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('post-likes', PostLikeController::class)->only(['store']);
    Route::apiResource('post-comments', PostCommentController::class)->only(['store', 'destroy']);
    Route::apiResource('post-comment-likes', PostCommentLikeController::class)->only(['store']);
    Route::apiResource('polls', PollController::class)->only(['store']);
    Route::apiResource('stories', StoryController::class)->only(['store', 'destroy']);
    Route::apiResource('follows', FollowController::class)->only(['store']);
    Route::apiResource('chats', ChatController::class)->only(['store', 'destroy']);
    Route::apiResource('search', SearchController::class)->only(['store', 'destroy']);

    // Posts
    Route::put('posts/mute/{username}', [PostController::class, 'mute']);

    // Stories
    Route::post('stories/seen/{id}', [StoryController::class, 'seen']);
    Route::put('stories/mute/{username}', [StoryController::class, 'mute']);

    // Filepond
    Route::prefix('filepond')
        ->controller(SocialFilePondController::class)
        ->group(function () {
            // Post
            Route::post('posts', 'storePostMedia');
            Route::delete('posts/{id}', 'destroyPostMedia');

            // Chat
            Route::post('chats', 'storeChatMedia');
            Route::delete('chats/{id}', 'deleteChatMedia');

            // Story
            Route::post('stories', 'storeStory');
            Route::delete('stories/{id}', 'deleteStory');
        });
});

<?php

use App\Http\Controllers\UnsubscribeController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => view('app'))->name('home');

Route::get('/unsubscribe/{user}/{category}', [UnsubscribeController::class, 'show'])
    ->middleware('signed')
    ->name('unsubscribe.show');

require __DIR__ . '/auth.php';

require __DIR__ . '/settings.php';

// Old page URLs (still in sent emails and stored notifications) moved from
// e.g. /video/show/{id} to /videos/{id}/view.
foreach (['video', 'audio'] as $kind) {
    Route::permanentRedirect("{$kind}/show/{id}", "/{$kind}s/{id}/view");
    Route::permanentRedirect("{$kind}/edit/{id}", "/{$kind}s/{id}/edit");
    Route::permanentRedirect("{$kind}/album/edit/{id}", "/{$kind}s/album/{id}/edit");
    Route::get("{$kind}/{page?}", fn(?string $page = null) => redirect("/{$kind}s" . ($page ? "/{$page}" : ''), 301))
        ->where('page', 'charts|create|album/create');
}

foreach (['chat', 'karaoke', 'post', 'profile', 'story'] as $section) {
    Route::permanentRedirect("{$section}/show/{id}", "/{$section}/{id}/view");
}

Route::permanentRedirect('post/edit/{id}', '/post/{id}/edit');

Route::fallback(fn() => view('app'));

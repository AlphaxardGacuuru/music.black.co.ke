<?php

use Illuminate\Support\Facades\Broadcast;

// User ids are UUIDs, so compare them as strings.
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});

Broadcast::channel('chat-created', fn() => true);
Broadcast::channel('chat-deleted', fn() => true);
Broadcast::channel('chat', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'username' => $user->username,
    ];
});

Broadcast::channel('post-created', fn() => true);
Broadcast::channel('post-commented', fn() => true);
Broadcast::channel('kopokopo-created', fn() => true);
// Purchase receipts, for the buyer only.
Broadcast::channel('video-bought.{userId}', fn($user, $userId) => (string) $user->id === (string) $userId);
Broadcast::channel('audio-bought.{userId}', fn($user, $userId) => (string) $user->id === (string) $userId);

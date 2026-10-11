<?php

namespace App\Http\Services;

use App\Http\Resources\AudioResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\VideoResource;
use App\Models\Audio;
use App\Models\BoughtAudio;
use App\Models\BoughtVideo;
use App\Models\KopokopoRecipient;
use App\Models\SongPayout;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminService extends Service
{
    /**
     * Aggregated music counts.
     */
    public function admin(): array
    {
        $normal = User::where('account_type', 'normal')->count();
        $musicians = User::where('account_type', 'musician')->count();
        $videos = Video::count();
        $boughtVideos = BoughtVideo::count();
        $audios = Audio::count();
        $boughtAudios = BoughtAudio::count();

        return ['data' => [
            'normal' => $normal,
            'musicians' => $musicians,
            'users' => $normal + $musicians,
            'videos' => $videos,
            'boughtVideos' => $boughtVideos,
            'audios' => $audios,
            'boughtAudios' => $boughtAudios,
        ]];
    }

    /**
     * Display a listing of users.
     *
     * Not routed: NEW already serves GET api/admin/users (paginated).
     */
    public function users(): AnonymousResourceCollection
    {
        $getUsers = User::all();

        return UserResource::collection($getUsers);
    }

    /**
     * Display a listing of videos, oldest first (OLD ordered by the
     * auto-increment id, ids are now UUIDs).
     */
    public function videos(): AnonymousResourceCollection
    {
        $getVideos = Video::orderBy('created_at', 'ASC')->get();

        return VideoResource::collection($getVideos);
    }

    /**
     * Display a listing of audios, oldest first.
     */
    public function audios(): AnonymousResourceCollection
    {
        $getAudios = Audio::orderBy('created_at', 'ASC')->get();

        return AudioResource::collection($getAudios);
    }

    /**
     * Display a listing of kopokopo recipients.
     *
     * Not routed: NEW already serves GET api/admin/kopokopo-recipients.
     */
    public function kopokopoRecipients(): array
    {
        $getKopokopoRecipients = KopokopoRecipient::all();

        return ['data' => $getKopokopoRecipients];
    }

    /**
     * Display a listing of song payouts.
     */
    public function songPayouts(): array
    {
        // OLD returned the raw rows, which carried the payee's username.
        $getSongPayouts = SongPayout::with('user')
            ->get()
            ->map(fn (SongPayout $songPayout) => [
                'id' => $songPayout->id,
                'username' => $songPayout->user?->username,
                'amount' => $songPayout->amount,
                'created_at' => $songPayout->created_at,
                'updated_at' => $songPayout->updated_at,
            ]);

        return ['data' => $getSongPayouts];
    }
}

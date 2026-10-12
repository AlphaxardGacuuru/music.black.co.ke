<?php

namespace App\Http\Services;

use App\Http\Resources\AudioResource;
use App\Http\Resources\UserResource;
use App\Models\Audio;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class AudioService extends Service
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $getAudios = Audio::orderBy('created_at', 'ASC')->get();

        return AudioResource::collection($getAudios);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): AudioResource
    {
        $getAudio = Audio::findOrFail($id);

        return new AudioResource($getAudio);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return array{0: bool, 1: string, 2: Audio}
     */
    public function store(Request $request): array
    {
        /* Create new audio song */
        $audio = new Audio;
        $audio->audio = $request->input('audio');
        $audio->name = $request->input('name');
        $audio->user_id = $this->id;
        $audio->ft_id = $this->userIdFor($request->input('ft'));
        $audio->audio_album_id = $request->input('audio_album_id');
        $audio->genre = $request->input('genre');
        $audio->thumbnail = $request->input('thumbnail');
        $audio->description = $request->input('description');
        $audio->released = $request->input('released');
        $saved = $audio->save();

        return [$saved, 'Audio Uploaded', $audio];
    }

    /**
     * Update the specified resource in storage.
     *
     * @return array{0: bool, 1: string, 2: Audio}
     */
    public function update(Request $request, string $id): array
    {
        $audio = Audio::findOrFail($id);

        Gate::authorize('update', $audio);

        if ($request->filled('name')) {
            $audio->name = $request->input('name');
        }

        if ($request->filled('ft')) {
            $audio->ft_id = $this->userIdFor($request->input('ft'));
        }

        if ($request->filled('audio_album_id')) {
            $audio->audio_album_id = $request->input('audio_album_id');
        }

        if ($request->filled('genre')) {
            $audio->genre = $request->input('genre');
        }

        if ($request->filled('description')) {
            $audio->description = $request->input('description');
        }

        if ($request->filled('released')) {
            $audio->released = $request->input('released');
        }

        if ($request->filled('thumbnail')) {
            // Delete old thumbnail
            Storage::disk('public')->delete($audio->getRawOriginal('thumbnail'));

            $audio->thumbnail = $request->input('thumbnail');
        }

        if ($request->filled('audio')) {
            // Delete old audio
            Storage::disk('public')->delete($audio->getRawOriginal('audio'));

            $audio->audio = $request->input('audio');
        }

        $saved = $audio->save();

        $message = $audio->name . ' edited';

        return [$saved, $message, $audio];
    }

    /**
     * Download Audio.
     *
     * @return array{0: string, 1: string}
     */
    public function download(string $id): array
    {
        $audio = Audio::findOrFail($id);

        Gate::authorize('download', $audio);

        $path = $audio->getRawOriginal('audio');

        abort_unless(Storage::disk('public')->exists($path), 404);

        $src = Storage::disk('public')->path($path);

        $name = $audio->name . '.' . pathinfo($path, PATHINFO_EXTENSION);

        return [$src, $name];
    }

    /**
     * Newly Released Chart
     */
    public function newlyReleased(): Response
    {
        $newlyReleased = Audio::orderBy('created_at', 'desc')->get();

        return response($this->chart($newlyReleased), 200);
    }

    /**
     * Trending Chart
     */
    public function trending(): Response
    {
        $trending = DB::table('bought_audios')
            ->select('audio_id', DB::raw('count(*) as bought'))
            ->where('created_at', '>=', Carbon::now()->subWeek())
            ->groupBy('audio_id')
            ->orderBy('bought', 'DESC')
            ->get();

        return response($this->chart($trending, true), 200);
    }

    /**
     * Top Downloaded
     */
    public function topDownloaded(): Response
    {
        $topDownloaded = DB::table('bought_audios')
            ->select('audio_id', DB::raw('count(*) as bought'))
            ->groupBy('audio_id')
            ->orderBy('bought', 'DESC')
            ->get();

        return response($this->chart($topDownloaded, true), 200);
    }

    /**
     * Top Liked
     */
    public function topLiked(): Response
    {
        $topLiked = DB::table('audio_likes')
            ->select('audio_id', DB::raw('count(*) as likes'))
            ->groupBy('audio_id')
            ->orderBy('likes', 'DESC')
            ->get();

        return response($this->chart($topLiked, true), 200);
    }

    /**
     * Structure charts
     *
     * @return array{data: array{artists: array<int, UserResource>, audios: AnonymousResourceCollection}}
     */
    public function chart(iterable $list, bool $loop = false): array
    {
        $audios = $list;

        // Check if items should be fetched
        if ($loop) {
            $audios = [];

            foreach ($list as $item) {
                $audio = Audio::find($item->audio_id);

                if ($audio) {
                    $audios[] = $audio;
                }
            }
        }

        $chartArtists = [];

        // Populate Artists array
        foreach ($audios as $audio) {
            $chartArtists[] = $audio->user_id;
        }

        // Count occurrences of artists
        $chartArtists = array_count_values($chartArtists);

        // Sort artists based on most occurences
        arsort($chartArtists);

        $artists = [];

        // Get Artists
        foreach (array_keys($chartArtists) as $artistId) {
            $artists[] = new UserResource(User::find($artistId));
        }

        return ['data' => [
            'artists' => $artists,
            'audios' => AudioResource::collection($audios),
        ]];
    }

    /*
     * Artist's Audios */
    public function artistAudios(string $username): AnonymousResourceCollection
    {
        $userId = User::where('username', $username)->value('id');

        $getArtistAudios = Audio::where('user_id', $userId)->get();

        return AudioResource::collection($getArtistAudios);
    }

    /**
     * Resolve an API username to a user id.
     */
    protected function userIdFor(?string $username): ?string
    {
        return $username ? User::where('username', $username)->value('id') : null;
    }
}

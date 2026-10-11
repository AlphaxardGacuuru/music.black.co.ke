<?php

namespace App\Http\Services;

use App\Http\Resources\UserResource;
use App\Http\Resources\VideoResource;
use App\Models\User;
use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class VideoService extends Service
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get Videos
        $getVideos = Video::orderBy('created_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->get();

        return VideoResource::collection($getVideos);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // Get Video
        $getVideo = Video::find($id);

        return new VideoResource($getVideo);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store($request)
    {
        /* Create new video song */
        $video = new Video;
        $video->video = $request->input('video');
        $video->name = $request->input('name');
        $video->user_id = $this->id;
        $video->ft_id = $this->userId($request->input('ft'));
        $video->video_album_id = $request->input('video_album_id');
        $video->genre = $request->input('genre');
        $video->thumbnail = $request->input('thumbnail');
        $video->description = $request->input('description');
        $video->released = $request->input('released');
        $saved = $video->save();

        $message = 'Video ' . $video->name . ' uploaded';

        return [$saved, $message, $video];
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($request, $id)
    {
        $video = Video::findOrFail($id);

        Gate::authorize('update', $video);

        if ($request->filled('name')) {
            $video->name = $request->input('name');
        }

        if ($request->filled('ft')) {
            $video->ft_id = $this->userId($request->input('ft'));
        }

        if ($request->filled('video_album_id')) {
            $video->video_album_id = $request->input('video_album_id');
        }

        if ($request->filled('genre')) {
            $video->genre = $request->input('genre');
        }

        if ($request->filled('description')) {
            $video->description = $request->input('description');
        }

        if ($request->filled('released')) {
            $video->released = $request->input('released');
        }

        if ($request->filled('thumbnail')) {
            // Delete thumbnail
            Storage::disk('public')->delete(array_filter([$video->getRawOriginal('thumbnail')]));

            $video->thumbnail = $request->input('thumbnail');
        }

        if ($request->filled('video')) {
            // Delete video
            Storage::disk('public')->delete(array_filter([$video->getRawOriginal('video')]));

            $video->video = $request->input('video');
        }

        $saved = $video->save();

        $message = $video->name . ' edited';

        return [$saved, $message, $video];
    }

    /**
     * Download Video.
     */
    public function download($id)
    {
        $video = Video::findOrFail($id);

        $path = $video->getRawOriginal('video');

        // Get file extension
        $ext = substr($path, -3);

        $src = Storage::disk('public')->path($path);

        $name = $video->name . '.' . $ext;

        return [$src, $name];
    }

    /**
     * Newly Released Chart
     */
    public function newlyReleased()
    {
        // Get Videos
        $newlyReleased = Video::orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return response($this->chart($newlyReleased), 200);
    }

    /**
     * Trending Chart
     */
    public function trending()
    {
        $trending = DB::table('bought_videos')
            ->select('video_id', DB::raw('count(*) as bought'))
            ->where('created_at', '>=', Carbon::now()->subWeek())
            ->groupBy('video_id')
            ->orderBy('bought', 'DESC')
            ->get();

        return response($this->chart($trending, true), 200);
    }

    /**
     * Top Downloaded
     */
    public function topDownloaded()
    {
        $topDownloaded = DB::table('bought_videos')
            ->select('video_id', DB::raw('count(*) as bought'))
            ->groupBy('video_id')
            ->orderBy('bought', 'DESC')
            ->get();

        return response($this->chart($topDownloaded, true), 200);
    }

    /**
     * Top Liked
     */
    public function topLiked()
    {
        $topLiked = DB::table('video_likes')
            ->select('video_id', DB::raw('count(*) as likes'))
            ->groupBy('video_id')
            ->orderBy('likes', 'DESC')
            ->get();

        return response($this->chart($topLiked, true), 200);
    }

    /**
     * Structure charts
     */
    public function chart($list, $loop = false)
    {
        $videos = $list;

        // Check if items should be fetched
        if ($loop) {
            $videos = [];

            foreach ($list as $item) {
                $video = Video::find($item->video_id);

                array_push($videos, $video);
            }
        }

        $chartArtists = [];

        // Populate Artists array
        foreach ($videos as $video) {
            array_push($chartArtists, $video->user_id);
        }

        // Transform data using resource
        $videos = VideoResource::collection($videos);

        // Count occurrences of artists
        $chartArtists = array_count_values($chartArtists);

        // Sort artists based on most occurences
        arsort($chartArtists);

        // Get ids only
        $chartArtists = array_keys($chartArtists);

        $artists = [];

        // Get Artists
        foreach ($chartArtists as $artist) {
            $getArtist = User::find($artist);

            $getArtist = new UserResource($getArtist);

            array_push($artists, $getArtist);
        }

        return ['data' => [
            'artists' => $artists,
            'videos' => $videos,
        ]];
    }

    /*
     * Artist's Videos
     */
    public function artistVideos($username)
    {
        $getArtistVideos = Video::where('user_id', $this->userId($username))->get();

        return VideoResource::collection($getArtistVideos);
    }

    /*
     * Look up a user id from a username sent by the API.
     */
    protected function userId(?string $username): ?string
    {
        return $username ? User::where('username', $username)->value('id') : null;
    }
}

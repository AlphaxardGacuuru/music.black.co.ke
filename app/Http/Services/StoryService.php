<?php

namespace App\Http\Services;

use App\Http\Resources\StoryResource;
use App\Models\Follow;
use App\Models\SeenStory;
use App\Models\Story;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class StoryService extends Service
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        if ($this->id) {
            // Stories from the musicians the viewer follows (which includes
            // themselves and @blackmusic), unless they muted them
            $getStories = Story::select('stories.*', 'follows.muted->stories as muted', 'follows.blocked')
                ->join('follows', fn (JoinClause $join) => $join->on('follows.followed_id', '=', 'stories.user_id')
                    ->where('follows.user_id', '=', $this->id))
                ->where('follows.muted->stories', false);
        } else {
            $getStories = Story::whereHas('user', fn ($query) => $query->where('username', '@blackmusic'));
        }

        $stories = $getStories
            ->orderBy('stories.created_at', 'DESC')
            ->paginate(10);

        return StoryResource::collection($stories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): array
    {
        // The media field is a comma separated list of JSON objects, e.g.
        // {"image":"stories/a.jpg"},{"video":"stories/b.mp4"}
        $stringifiedMedia = explode(',', $request->input('media'));

        $arrayAndJsonMedia = [];

        foreach ($stringifiedMedia as $item) {
            $arrayAndJsonMedia[] = json_decode($item);
        }

        $story = new Story;
        $story->user_id = $this->id;
        $story->media = $arrayAndJsonMedia;
        $story->text = $request->input('text');
        $saved = $story->save();

        return [$saved, 'Story posted', $story];
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): StoryResource
    {
        $getStory = Story::findOrFail($id);

        return new StoryResource($getStory);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): array
    {
        $story = Story::findOrFail($id);

        Gate::authorize('delete', $story);

        // Delete media (every file of every item, images and videos)
        foreach ($story->media ?? [] as $media) {
            foreach ((array) $media as $path) {
                if (is_string($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        $deleted = $story->delete();

        return [$deleted, 'Story deleted'];
    }

    /*
     * Seen */
    public function seen(string $id): array
    {
        $seenStory = new SeenStory;
        $seenStory->story_id = $id;
        $seenStory->user_id = $this->id;
        $seenStory->seen_at = Carbon::now();
        $saved = $seenStory->save();

        return [$saved, 'Story seen', $seenStory];
    }

    /*
     * Mute */
    public function mute(string $username): array
    {
        $followedId = User::where('username', $username)->value('id');

        // Get follow
        $follow = Follow::where('followed_id', $followedId)
            ->where('user_id', $this->id)
            ->firstOrFail();

        $muted = $follow->muted ?? ['posts' => false, 'stories' => false];

        // Check if Stories are muted
        if ($muted['stories'] ?? false) {
            $muted['stories'] = false;

            $message = 'Stories from '.$username.' unmuted';
        } else {
            $muted['stories'] = true;

            $message = 'Stories from '.$username.' muted';
        }

        $follow->muted = $muted;

        $saved = $follow->save();

        return [$saved, $message, null];
    }
}

<?php

namespace App\Http\Services;

use App\Http\Resources\PostResource;
use App\Models\Follow;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class PostService extends Service
{
    public function index(): AnonymousResourceCollection
    {
        // Get posts based on whether current user is guest
        if ($this->id) {
            // Get Posts if the musician is followed (OLD joined on the
            // follows of @blackmusic, not the current user) and not muted
            $blackMusicId = User::where('username', '@blackmusic')->value('id');

            $getPosts = Post::select('posts.*', 'follows.muted->posts as muted', 'follows.blocked')
                ->join('follows', fn (JoinClause $join) => $join->on('follows.followed_id', '=', 'posts.user_id')
                    ->where('follows.user_id', '=', $blackMusicId))
                ->where('follows.muted->posts', false);
        } else {
            $getPosts = Post::whereHas('user', fn ($query) => $query->where('username', '@blackmusic'));
        }

        $posts = $getPosts
            ->orderBy('posts.id', 'DESC')
            ->paginate(10);

        return PostResource::collection($posts);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): PostResource
    {
        $post = Post::findOrFail($id);

        return new PostResource($post);
    }

    /* Create new post */
    public function store(Request $request): array
    {
        $post = new Post;
        $post->user_id = $this->id;
        $post->text = $request->input('text');
        $post->media = $request->input('media');
        $post->parameter_1 = $request->input('para1') ?: '';
        $post->parameter_2 = $request->input('para2') ?: '';
        $post->parameter_3 = $request->input('para3') ?: '';
        $post->parameter_4 = $request->input('para4') ?: '';
        $post->parameter_5 = $request->input('para5') ?: '';

        $saved = $post->save();

        return [$saved, 'Post posted', $post];
    }

    // Update Post
    public function update(Request $request, string $id): array
    {
        $post = Post::findOrFail($id);

        Gate::authorize('update', $post);

        $post->text = $request->input('text');
        $saved = $post->save();

        return [$saved, 'Post edited', $post];
    }

    // Delete Post
    public function destory(string $id): array
    {
        $post = Post::findOrFail($id);

        Gate::authorize('delete', $post);

        $media = $post->getRawOriginal('media');

        if ($media) {
            Storage::disk('public')->delete($media);
        }

        $deleted = $post->delete();

        return [$deleted, 'Post deleted'];
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

        // Check if Posts are muted
        if ($muted['posts'] ?? false) {
            $muted['posts'] = false;

            $message = 'You unmuted posts from '.$username;
        } else {
            $muted['posts'] = true;

            $message = 'You muted posts from '.$username;
        }

        $follow->muted = $muted;

        $saved = $follow->save();

        return [$saved, $message];
    }

    /*
     * Artist's Posts */
    public function artistPosts(string $username): AnonymousResourceCollection
    {
        $artistId = User::where('username', $username)->value('id');

        // Get Artist's Posts with muted info
        $getArtistPosts = Post::select('posts.*', 'follows.muted->posts as muted', 'follows.blocked')
            ->join('follows', fn (JoinClause $join) => $join->on('follows.followed_id', '=', 'posts.user_id')
                ->where('follows.user_id', '=', $this->id))
            ->where('posts.user_id', $artistId)
            ->orderBy('posts.id', 'DESC')
            ->get();

        return PostResource::collection($getArtistPosts);
    }
}

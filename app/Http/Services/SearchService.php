<?php

namespace App\Http\Services;

use App\Models\Search;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class SearchService extends Service
{
    /**
     * The current user's searches.
     */
    public function index(): Collection
    {
        // OLD returned the raw rows, whose "username" column is now user_id
        return Search::with('user')
            ->where('user_id', $this->id)
            ->get()
            ->map(fn (Search $search) => [
                'id' => $search->id,
                'username' => $search->user->username,
                'keyword' => $search->keyword,
                'created_at' => $search->created_at,
                'updated_at' => $search->updated_at,
            ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): void
    {
        $search = new Search;
        $search->user_id = $this->id;
        $search->keyword = $request->input('keyword');
        $search->save();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): array
    {
        $search = Search::findOrFail($id);

        Gate::authorize('delete', $search);

        $deleted = $search->delete();

        return [$deleted, 'Search deleted'];
    }
}

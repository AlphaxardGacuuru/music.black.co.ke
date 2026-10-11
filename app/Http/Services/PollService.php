<?php

namespace App\Http\Services;

use App\Models\Poll;
use Illuminate\Http\Request;

class PollService extends Service
{
    /**
     * Vote, or remove the vote if the user already voted on this post.
     */
    public function store(Request $request): array
    {
        // Check if user has voted
        $getPoll = Poll::where('user_id', $this->id)
            ->where('post_id', $request->input('post'));

        $hasVoted = $getPoll->exists();

        if ($hasVoted) {
            // Get Poll
            $poll = $getPoll->first();
            // Delete Poll
            $getPoll->delete();

            $message = 'Vote removed';
            $saved = false;
        } else {
            $poll = new Poll;
            $poll->post_id = $request->input('post');
            $poll->user_id = $this->id;
            $poll->parameter = $request->input('parameter');
            $poll->save();

            $message = 'Voted';
            $saved = true;
        }

        return [$saved, $message, $poll];
    }
}

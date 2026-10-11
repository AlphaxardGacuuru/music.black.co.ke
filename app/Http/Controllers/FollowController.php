<?php

namespace App\Http\Controllers;

use App\Events\FollowedEvent;
use App\Http\Services\FollowService;
use App\Models\User;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function __construct(protected FollowService $service) {}

    /**
     * Follow or unfollow a musician.
     */
    public function store(Request $request)
    {
        $request->validate([
            'musician' => 'required|exists:users,username',
        ]);

        [$added, $message] = $this->service->store($request);

        // Dispatch Event
        $musician = User::where('username', $request->input('musician'))->first();

        FollowedEvent::dispatchIf($added, $musician, $request->user());

        return response(['message' => $message], 200);
    }
}

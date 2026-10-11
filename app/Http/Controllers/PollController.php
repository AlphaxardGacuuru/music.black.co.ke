<?php

namespace App\Http\Controllers;

use App\Http\Services\PollService;
use Illuminate\Http\Request;

class PollController extends Controller
{
    public function __construct(protected PollService $service) {}

    /**
     * Vote, or remove a vote.
     */
    public function store(Request $request)
    {
        [$saved, $message, $poll] = $this->service->store($request);

        return response([
            'status' => $saved,
            'message' => $message,
        ], 200);
    }
}

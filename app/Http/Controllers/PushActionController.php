<?php

namespace App\Http\Controllers;

use App\Http\Services\UserService;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * The background buttons on web push notifications. The service worker
 * posts to these through the signed links in each push (see
 * App\Notifications\Messages\WebPushMessage::signedAction()), so $user
 * comes from the signed URL, not a token.
 */
class PushActionController extends Controller
{
    public function __construct(protected UserService $userService)
    {
        //
    }

    /**
     * "Verify" on a new signup, for the admin.
     */
    public function verifyUser(User $user, User $subject): JsonResponse
    {
        abort_unless($user->email === config('admin.email'), 403);

        $this->userService->verify($subject);

        return response()->json([
            'message' => "{$subject->name} verified",
        ]);
    }
}

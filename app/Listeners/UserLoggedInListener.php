<?php

namespace App\Listeners;

use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Str;

/**
 * Every way in (Google, email and password, its two-factor step and
 * signing up) ends in Auth::login(), which fires Login, so they're all
 * recorded here.
 */
class UserLoggedInListener
{
    public function __construct()
    {
        //
    }

    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->recordLogin($event->user);

        $event->user->markActive();
    }

    /**
     * Only the social login callback has a {website} parameter, and only
     * signing up comes through register.store, so anything else came
     * through the email and password form.
     */
    private function recordLogin(User $user): void
    {
        $request = request();

        $user->logins()
            ->create([
                'method' => $request->route()?->parameter('website')
                    ?? ($request->routeIs('register.store') ? UserLogin::METHOD_REGISTER : UserLogin::METHOD_PASSWORD),
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            ]);
    }
}

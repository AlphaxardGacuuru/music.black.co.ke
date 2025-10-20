<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AuthenticatedSessionController extends Controller
{
    /*
     * Social Logins*/
    public function redirectToProvider($website)
    {
        return Socialite::driver($website)->redirect();
    }

    /**
     * Obtain the user information from GitHub/Google/Twitter/Facebook.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback($website)
    {
		try {
			$user = Socialite::driver($website)->stateless()->user();
		} catch (Exception $e) {
			return redirect('/#/socialite/' . urlencode($e->getMessage()) . '/failed');
		}

        $name = $user->getName() ? trim(preg_replace('/[\r\n]+/', ' ', $user->getName())) : " ";

        $email = $user->getEmail() ? $user->getEmail() : redirect('/');

        $avatar = $user->getAvatar() ? $user->getAvatar() : "avatar/male-avatar.png";

        // Get Database User
        $dbUser = User::where('email', $user->getEmail());

        // Check if user exists
        if ($dbUser->exists()) {
            if ($dbUser->first()->phone) {

                $token = $dbUser
                    ->first()
                    ->createToken("deviceName")
                    ->plainTextToken;

                return redirect("/#/socialite/LoggedIn/" . $token);

            } else {
                // Remove forward slashes and URL encode
                $avatar = str_replace("/", " ", $avatar);

                return redirect('/#/register/' . urlencode($name) . '/' . urlencode($email) . '/' . urlencode($avatar));
            }
        } else {
            // Remove forward slashes and URL encode
            $avatar = str_replace("/", " ", $avatar);

            return redirect('/#/register/' . urlencode($name) . '/' . urlencode($email) . '/' . urlencode($avatar));
        }
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        return response()->noContent();
    }

    /*
     * Token Based Login
     */
    public function token(Request $request)
    {
        $request->validate([
            'phone' => 'required',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Give @blackmusic all abilities
        if ($user->username == '@blackmusic') {
            $token = $user->createToken($request->device_name, ['*'])->plainTextToken;
        } else {
            $token = $user->createToken($request->device_name)->plainTextToken;
        }

        return response([
            "message" => "Logged in",
            "data" => $token,
        ], 200);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // Delete Current Access Token
        $hasLoggedOut = auth("sanctum")
            ->user()
            ->currentAccessToken()
            ->delete();

        if ($hasLoggedOut) {
            $message = "Logged Out";
        } else {
            $message = "Failed to log out";
        }

        return response(["message" => $message], 200);
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OtpController extends Controller
{
    public function show(Request $request): Response
    {
        $user = User::findOrFail($request->session()->get('otp_user_id'));

        return Inertia::render('MeridianHR/OtpVerification', [
            'email'  => $user->email,
            'length' => config('one-time-passwords.password_length'),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['otp' => ['required', 'string']]);

        $userId = $request->session()->get('otp_user_id');
        $rateLimitKey = 'otp:' . $userId;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            throw ValidationException::withMessages([
                'otp' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        /** @var User $user */
        $user = User::findOrFail($userId);
        $result = $user->attemptLoginUsingOneTimePassword($request->string('otp'));

        if (! $result->isOk()) {
            RateLimiter::hit($rateLimitKey);

            throw ValidationException::withMessages([
                'otp' => 'The code you entered is invalid or has expired.',
            ]);
        }

        RateLimiter::clear($rateLimitKey);
        $request->session()->forget('otp_user_id');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('home', absolute: false));
    }

    public function resend(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = User::findOrFail($request->session()->get('otp_user_id'));
        $user->sendOneTimePassword();

        return back();
    }
}

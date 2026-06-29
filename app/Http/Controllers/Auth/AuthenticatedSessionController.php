<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\GeneralSettings\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('MeridianHR/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $user = $request->authenticateAndGetUser();

            $request->session()->regenerate();
            
            // Check if OTP is enabled via Setting
            if (!Setting::isOtpEnabled()) {
                // OTP disabled - log user in directly
                Auth::login($user);
                $request->session()->regenerate();
                Log::info('OTP bypassed (disabled) for user: ' . $user->email);
                
                return redirect()->intended(route('home', absolute: false));
            }
            
            // OTP enabled - proceed with OTP verification flow
            $request->session()->put('otp_user_id', $user->id);
            $user->sendOneTimePassword();

            return redirect()->route('otp.show');
        } catch (\Exception $e) {
            Log::error('AuthenticatedSessionController:store: Exception occurred', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse|HttpResponse
    {
        // Log::info('AuthenticatedSessionController:destroy: session before logout: ' . json_encode($request->session()->all()));


        if (session('login_method') === 'microsoft') {
            /** @var \SocialiteProviders\Microsoft\Provider $microsoft */
            // first log out of Laravel session, then redirect to Microsoft logout URL
            Auth::guard('web')->logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            // now redirect to Microsoft logout URL which will then redirect back to our login page
            $microsoft = Socialite::driver('microsoft');
            $microsoftLogoutUrl = $microsoft->getLogoutUrl(route('login'));
            // Log::info('AuthenticatedSessionController:destroy microsoftLogoutUrl: ' . $microsoftLogoutUrl);
            return Inertia::location($microsoftLogoutUrl);
        } else {

            Auth::guard('web')->logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            return redirect('/');
        }
    }
}

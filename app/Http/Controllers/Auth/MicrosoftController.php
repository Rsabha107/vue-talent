<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class MicrosoftController extends Controller
{
    public function redirectToMicrosoft()
    {
        return Socialite::driver('microsoft')->redirect();
    }

    public function handleMicrosoftCallback(Request $request)
    {
        try {
            $azureUser = Socialite::driver('microsoft')->user();
            $user = User::where('email', $azureUser->email)->first();

            if (!$user) {
                Log::warning('Microsoft login blocked — email not in system: ' . $azureUser->email);
                return $this->microsoftLogout($request, 'Your Microsoft account is not authorised to access this system. Please contact the administrator.');
            }

            $user->update([
                'socialite_id'    => $azureUser->getId(),
                'socialite_token' => $azureUser->token,
                'name'            => $azureUser->getName(),
                'provider'        => 'microsoft',
                'provider_id'     => $azureUser->getId(),
            ]);

            Auth::login($user);
            $request->session()->regenerate();
            session(['login_method' => 'microsoft']);

            Log::info('login_method set to microsoft for user: ' . $user->email . ' with value: ' . session('login_method'));
            Log::info('Microsoft login successful: ' . $user->email);

            return redirect()->intended('/');

        } catch (Exception $e) {
            Log::error('Microsoft callback error: ' . $e->getMessage());
            return $this->microsoftLogout($request, 'Unable to sign in with Microsoft. Please try again.');
        }
    }

    private function microsoftLogout(Request $request, string $errorMessage): \Illuminate\Http\RedirectResponse
    {
        Log::info('Initiating Microsoft logout process due to error: ' . $errorMessage);
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Flash into the fresh session so it survives the Microsoft round-trip
        $request->session()->flash('error', $errorMessage);

        $postLogoutRedirect = route('login');
        $microsoftLogoutUrl = Socialite::driver('microsoft')->getLogoutUrl($postLogoutRedirect);
        Log::info('Microsoft logout URL: ' . $microsoftLogoutUrl);

        return redirect($microsoftLogoutUrl);
    }
}

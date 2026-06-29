<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\SendForgotPasswordMail;
use App\Models\GeneralSettings\Setting;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    // {
    //     $request->validate([
    //         'email' => 'required|email',
    //     ]);

    //     // We will send the password reset link to this user. Once we have attempted
    //     // to send the link, we will examine the response then see the message we
    //     // need to show to the user. Finally, we'll send out a proper response.
    //     $status = Password::sendResetLink(
    //         $request->only('email')
    //     );

    //     if ($status == Password::RESET_LINK_SENT) {
    //         return back()->with('status', __($status));
    //     }

    //     throw ValidationException::withMessages([
    //         'email' => [trans($status)],
    //     ]);
    // }

    {
        Log::debug('PasswordResetLinkController@store called with email: ' . $request->email);  
        $rules = [
            'email' => 'required|email|exists:users',
        ];

        $validator = Validator::make($request->all(), $rules);

        Log::debug('Validation rules applied: ' . json_encode($rules));
        if ($validator->fails()) {
            Log::debug('Validation failed for email: ' . $request->email . ' with errors: ' . json_encode($validator->errors()->all()));
            return redirect()->back()
                ->withInput()
                ->withErrors($validator);
        }

        $token = sha1(time() . config('global.key'));

        Log::debug('Generated password reset token: ' . $token);
        try {
            Log::debug('Attempting to insert password reset token into database for email: ' . $request->email);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                ['token' => $token, 'created_at' => Carbon::now()]
            );
            Log::debug('Password reset token inserted into database for email: ' . $request->email);

        } catch (Exception $e) {
            Log::debug('Error inserting password reset token into database for email: ' . $request->email . ' with error: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->withErrors('A reset password was already sent to your email.  please check your inbox');
            // return $e->getMessage();
        }

        $content = [
            'token'     => $token,
            'subject'   => 'V: Reset Password Link',
            // 'url'       => route('password.reset', ['token' => $token]),
        ];

        Log::debug('Prepared email content for password reset: ' . json_encode($content));
        
        // Check if notifications are enabled before sending
        if (Setting::shouldSendNotifications()) {
            Mail::to($request->email)->queue(new SendForgotPasswordMail($content));
            Log::info('Password reset email queued for: ' . $request->email);
        } else {
            Log::info('Password reset email NOT sent (notifications disabled) for: ' . $request->email);
        }

        // Mail::send('emails.forgetPassword', ['token' => $token], function($message) use($request){
        //     $message->to($request->email);
        //     $message->subject('Reset Password');
        // });

        return redirect()->route('login')->with('status', 'We have sent you a password reset link! Please check your email.');
    } //submitForgetPasswordForm

}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Mail\SendOtpMail;
use App\Models\GeneralSettings\Setting;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Spatie\OneTimePasswords\Models\Concerns\HasOneTimePasswords;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password','status_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasOneTimePasswords;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function status()
    {
        return $this->belongsTo(GlobalStatus::class, 'status_id');
    }

    public function leaveRequests()
    {
        return $this->hasMany(EmployeeLeaveRequest::class, 'user_id');
    }

    public function performedLeaveActions()
    {
        return $this->hasMany(EmployeeLeaveRequest::class, 'performer_id');
    }

    /**
     * Send a one-time password to the user via email.
     * Overrides the default implementation from HasOneTimePasswords trait.
     */
    public function sendOneTimePassword(): void
    {
        $otpModel = $this->createOneTimePassword();
        $expiresInMinutes = config('one-time-passwords.default_expires_in_minutes', 2);

        // Check if notifications are enabled before sending
        if (Setting::shouldSendNotifications()) {
            Mail::to($this->email)->send(
                new SendOtpMail(
                    otpCode: $otpModel->password,
                    userName: $this->name,
                    expiresInMinutes: $expiresInMinutes
                )
            );
            \Log::info('OTP email sent to: ' . $this->email);
        } else {
            \Log::info('OTP email NOT sent (notifications disabled) for: ' . $this->email);
        }
    }
}

<?php

namespace App\Models\GeneralSettings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Check if notifications/emails should be sent
     * 
     * @return bool
     */
    public static function shouldSendNotifications(): bool
    {
        // Cache for 5 minutes to avoid repeated database queries
        return Cache::remember('setting_send_notifications', 300, function () {
            $setting = self::where('key', 'send_notifications')->first();
            
            // Default to true if setting doesn't exist
            if (!$setting) {
                return true;
            }
            
            // Check if value is '1', 'true', 'yes', or 1
            return in_array(strtolower($setting->value), ['1', 'true', 'yes']) || $setting->value === 1;
        });
    }

    /**
     * Check if OTP verification is enabled
     * 
     * @return bool
     */
    public static function isOtpEnabled(): bool
    {
        // Cache for 5 minutes to avoid repeated database queries
        return Cache::remember('setting_otp_enabled', 300, function () {
            $setting = self::where('key', 'otp_enabled')->first();
            
            // Default to true (enabled) if setting doesn't exist
            if (!$setting) {
                return true;
            }
            
            // Check if value is '1', 'true', 'yes', or 1
            return in_array(strtolower($setting->value), ['1', 'true', 'yes']) || $setting->value === 1;
        });
    }

    /**
     * Clear the settings cache when settings are updated
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($setting) {
            if ($setting->key === 'send_notifications') {
                Cache::forget('setting_send_notifications');
            }
            if ($setting->key === 'otp_enabled') {
                Cache::forget('setting_otp_enabled');
            }
        });
    }
}

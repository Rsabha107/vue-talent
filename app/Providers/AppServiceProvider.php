<?php

namespace App\Providers;

use App\Models\GeneralSettings\Setting;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        if (! app()->runningInConsole()) {
            try {
                if (Schema::hasTable('settings')) {
                    $settings = Cache::remember('app_settings', now()->addHours(24), function () {
                        return Setting::pluck('value', 'key')->toArray();
                    });
                    config(['settings' => $settings]);
                }
            } catch (\Throwable $e) {
                // Avoid crashing if DB is not ready yet
            }
        }

        Event::listen(SocialiteWasCalled::class, function (SocialiteWasCalled $event) {
            // $event->extendSocialite('google', \SocialiteProviders\Google\Provider::class);
            $event->extendSocialite('microsoft', \SocialiteProviders\Microsoft\Provider::class);
        });

        if ($this->app->environment('azure')) {
            URL::forceScheme('https');
        }

        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols();
        });
    }
}

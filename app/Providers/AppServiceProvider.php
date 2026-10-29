<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Carbon::setLocale('id');

        if ($this->app->isProduction() && config('app.url')) {
            URL::forceRootUrl(config('app.url'));
        }

        RateLimiter::for('wa-webhook', fn (Request $request) => Limit::perMinute(30)->by('wa-hook:'.(preg_replace('/\D/', '', (string) $request->input('sender')) ?: $request->ip())));

        RateLimiter::for('partner-api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->currentAccessToken()?->id ?: $request->ip()));
    }
}

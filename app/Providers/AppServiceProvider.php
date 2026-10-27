<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Carbon::setLocale('id');

        RateLimiter::for('partner-api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->currentAccessToken()?->id ?: $request->ip()));
    }
}

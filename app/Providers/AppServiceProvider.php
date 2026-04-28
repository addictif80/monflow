<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\NavidromeService::class);
        $this->app->singleton(\App\Services\StripeService::class);
    }

    public function boot(): void
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret_key'));

        RateLimiter::for('auth', fn ($request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('password-reset', fn ($request) => Limit::perMinute(3)->by($request->ip()));
    }
}

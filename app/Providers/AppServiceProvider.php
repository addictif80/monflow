<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

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
    }
}

<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Derrière NPM → Tailscale → CyberPanel : faire confiance aux headers X-Forwarded-*
        $middleware->trustProxies(at: '*');

        // Routes JSON (playlists) : déjà protégées par auth + CORS same-origin
        // OLS/CyberPanel interfère avec la validation CSRF sur les requêtes fetch
        $middleware->validateCsrfTokens(except: [
            '/portal/playlists',
            '/portal/playlists/*',
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'subscribed' => \App\Http\Middleware\SubscribedMiddleware::class,
        ]);
        $middleware->redirectUsersTo(fn () => auth()->user()?->is_admin ? '/admin' : '/portal');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->command('subscriptions:check-overdue')->hourly();
        $schedule->command('subscriptions:send-reminders')->dailyAt('09:00');
        $schedule->command('newsletter:weekly-new-music')->weeklyOn(1, '10:00');
    })
    ->create();

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
            '/portal/shared/*',
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'subscribed' => \App\Http\Middleware\SubscribedMiddleware::class,
        ]);
        $middleware->redirectUsersTo(fn () => auth()->user()?->is_admin ? '/admin' : '/portal');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // In production (APP_DEBUG=false): convert unhandled exceptions to friendly responses.
        // Debug mode keeps the Ignition error page for development.
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (config('app.debug')) return null;
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) return null;

            \Illuminate\Support\Facades\Log::error($e->getMessage(), ['exception' => $e]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Une erreur inattendue s\'est produite.'], 500);
            }
            if ($request->isMethod('post')) {
                return redirect()->back()->withInput()
                    ->with('error', 'Une erreur inattendue s\'est produite. Veuillez réessayer.');
            }
            return response()->view('errors.500', [], 500);
        });
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->command('subscriptions:check-overdue')->hourly();
        $schedule->command('subscriptions:send-reminders')->dailyAt('09:00');
        $schedule->command('newsletter:weekly-new-music')->weeklyOn(1, '10:00');
    })
    ->create();

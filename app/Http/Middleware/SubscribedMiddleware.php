<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SubscribedMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && ($user->is_admin || $user->activeSubscription)) {
            return $next($request);
        }
        return redirect('/portal/plans')->with('error', 'Cette fonctionnalité est réservée aux abonnés actifs.');
    }
}

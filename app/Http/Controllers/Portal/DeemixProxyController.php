<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Http, Log};

/**
 * Reverse proxy transparent vers l'instance Deemix.
 *
 * Toutes les requêtes sous /portal/deemix/* sont forwardées vers
 * l'URL configurée (services.deemix.url), et les réponses HTML sont
 * réécrites pour que toutes les URLs absolues pointent vers notre
 * proxy. L'utilisateur reste sur le domaine MonFlow et garde sa nav.
 *
 * Limitations :
 *  - Pas de support WebSocket (si Deemix en utilise, la partie temps
 *    réel ne fonctionnera pas — fallback polling ou activer un proxy
 *    WebSocket côté LiteSpeed).
 *  - Les requêtes fetch() JavaScript vers des URLs absolues (ex.
 *    `fetch('https://deemix.monflow.fr/api/...')`) passent en clair ;
 *    on compte sur le fait que Deemix utilise des chemins relatifs.
 */
class DeemixProxyController extends Controller
{
    private const PREFIX = '/portal/deemix';

    public function handle(Request $request, string $any = '')
    {
        $user = Auth::user();
        if (!$user->is_admin && !$user->activeSubscription) {
            return redirect('/portal/plans')->with('error', 'Deemix est réservé aux abonnés actifs.');
        }

        $base = rtrim(config('services.deemix.url'), '/');
        $target = $base . '/' . ltrim($any, '/');
        if ($qs = $request->getQueryString()) $target .= '?' . $qs;

        // Forward des headers utiles (on retire ceux qui cassent le proxy)
        $skipRequest = ['host', 'cookie', 'content-length', 'x-forwarded-for', 'x-forwarded-host', 'x-forwarded-proto', 'connection', 'upgrade'];
        $fwdHeaders = [];
        foreach ($request->headers->all() as $name => $values) {
            if (in_array(strtolower($name), $skipRequest, true)) continue;
            $fwdHeaders[$name] = implode(', ', $values);
        }

        // Cookie jar par session pour conserver l'authentification côté Deemix
        $jar = (array) session('deemix_cookies', []);
        if (!empty($jar)) {
            $fwdHeaders['Cookie'] = collect($jar)->map(fn($v, $k) => "{$k}={$v}")->implode('; ');
        }

        $http = Http::withHeaders($fwdHeaders)
            ->withOptions([
                'verify' => config('services.deemix.verify_ssl', true),
                'allow_redirects' => false,
                'timeout' => 30,
            ]);

        $body = $request->getContent();
        if ($body !== '') {
            $http = $http->withBody($body, $request->header('Content-Type', 'application/octet-stream'));
        }

        try {
            $response = $http->send($request->method(), $target);
        } catch (\Exception $e) {
            Log::error("Deemix proxy error: {$e->getMessage()}");
            return response('Deemix indisponible : ' . $e->getMessage(), 502);
        }

        // Capture des Set-Cookie pour les rejouer à la prochaine requête
        $setCookies = $response->headers()['Set-Cookie'] ?? $response->headers()['set-cookie'] ?? [];
        foreach ((array) $setCookies as $cookie) {
            if (preg_match('/^([^=;]+)=([^;]*)/', $cookie, $m)) {
                $jar[trim($m[1])] = $m[2];
            }
        }
        session(['deemix_cookies' => $jar]);

        // Suivi manuel des redirections pour réécrire la Location
        if ($response->status() >= 300 && $response->status() < 400 && $response->header('Location')) {
            $loc = $response->header('Location');
            $loc = str_replace($base, self::PREFIX, $loc);
            if (str_starts_with($loc, '/') && !str_starts_with($loc, self::PREFIX)) {
                $loc = self::PREFIX . $loc;
            }
            return redirect($loc, $response->status());
        }

        $contentType = $response->header('Content-Type') ?? 'application/octet-stream';
        $content = $response->body();

        // Réécriture des URLs dans les réponses HTML/CSS/JS
        if (str_contains($contentType, 'text/html') || str_contains($contentType, 'application/xhtml')) {
            $content = $this->rewriteHtml($content, $base);
        } elseif (str_contains($contentType, 'text/css')) {
            $content = $this->rewriteCss($content, $base);
        } elseif (str_contains($contentType, 'javascript') || str_contains($contentType, 'application/json')) {
            $content = str_replace($base, self::PREFIX, $content);
        }

        $laravelResponse = response($content, $response->status());
        foreach (['Content-Type', 'Cache-Control', 'ETag', 'Last-Modified', 'Content-Disposition'] as $h) {
            if ($v = $response->header($h)) $laravelResponse->header($h, $v);
        }
        // On ne transmet pas Content-Length (le corps a pu être réécrit)
        return $laravelResponse;
    }

    private function rewriteHtml(string $html, string $base): string
    {
        // URLs absolues vers le domaine Deemix
        $html = str_replace($base, self::PREFIX, $html);

        // Attributs href/src/action commençant par / mais pas déjà préfixés
        $prefix = self::PREFIX;
        $html = preg_replace_callback(
            '/((?:href|src|action|data-src)=)(["\'])(\/(?!\/|' . preg_quote(ltrim($prefix, '/'), '/') . ')[^"\']*)\2/i',
            fn($m) => $m[1] . $m[2] . $prefix . $m[3] . $m[2],
            $html
        );

        // Balise <base href> pour que les chemins relatifs tombent sous /portal/deemix/
        if (!str_contains($html, '<base ')) {
            $html = preg_replace('/<head(\s[^>]*)?>/i', '<head$1><base href="' . $prefix . '/">', $html, 1);
        }

        // Bouton "Retour au portail" injecté dans le body pour rester transparent
        $backBtn = '<a href="/portal" style="position:fixed;top:10px;right:10px;z-index:99999;background:#4f46e5;color:#fff;padding:8px 14px;border-radius:6px;font-family:-apple-system,Segoe UI,sans-serif;font-size:13px;text-decoration:none;box-shadow:0 2px 8px rgba(0,0,0,.3)">&larr; MonFlow</a>';
        $html = preg_replace('/<body(\s[^>]*)?>/i', '<body$1>' . $backBtn, $html, 1);

        return $html;
    }

    private function rewriteCss(string $css, string $base): string
    {
        $css = str_replace($base, self::PREFIX, $css);
        $prefix = self::PREFIX;
        // url(/xxx) → url(/portal/deemix/xxx)
        $css = preg_replace_callback(
            '/url\(\s*(["\']?)(\/(?!\/|' . preg_quote(ltrim($prefix, '/'), '/') . ')[^\)"\']*)\1\s*\)/i',
            fn($m) => 'url(' . $m[1] . $prefix . $m[2] . $m[1] . ')',
            $css
        );
        return $css;
    }
}

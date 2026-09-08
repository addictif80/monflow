<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Session Driver
    |--------------------------------------------------------------------------
    */

    // Ce fichier n'existait pas avant ce correctif : Laravel 11 retombe alors
    // sur son propre défaut interne ("database"). On le garde identique ici
    // pour ne rien changer au comportement de prod déjà en place — seuls les
    // réglages liés à la sécurité du cookie sont explicités ci-dessous.
    'driver' => env('SESSION_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime
    |--------------------------------------------------------------------------
    */

    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'expire_on_close' => (bool) env('SESSION_EXPIRE_ON_CLOSE', false),

    /*
    |--------------------------------------------------------------------------
    | Session Encryption
    |--------------------------------------------------------------------------
    */

    'encrypt' => (bool) env('SESSION_ENCRYPT', true),

    /*
    |--------------------------------------------------------------------------
    | Session File Location / Database Connection / Table
    |--------------------------------------------------------------------------
    */

    'files' => storage_path('framework/sessions'),

    'connection' => env('SESSION_CONNECTION'),

    'table' => env('SESSION_TABLE', 'sessions'),

    'store' => env('SESSION_STORE'),

    'lottery' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Session Cookie
    |--------------------------------------------------------------------------
    */

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug(env('APP_NAME', 'monflow'), '_').'_session'
    ),

    'path' => env('SESSION_PATH', '/'),

    'domain' => env('SESSION_DOMAIN'),

    // En prod (derrière le reverse proxy NPM en HTTPS), le cookie de session
    // doit être Secure : sans quoi il transite en clair si jamais servi en
    // HTTP par erreur. Activer explicitement via SESSION_SECURE_COOKIE=true.
    'secure' => (bool) env('SESSION_SECURE_COOKIE', true),

    'http_only' => (bool) env('SESSION_HTTP_ONLY', true),

    // 'lax' : le cookie n'est jamais envoyé sur une requête POST/DELETE/PUT
    // cross-site (form ou fetch), ce qui neutralise le CSRF sur les routes
    // exemptées de token CSRF (/portal/playlists*, /portal/shared/*) sans
    // dépendre uniquement du header X-CSRF-TOKEN.
    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    'partitioned' => (bool) env('SESSION_PARTITIONED_COOKIE', false),

];

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin — MonFlow')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/css/monflow.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
</head>
<body class="bg-zinc-950 text-zinc-100 min-h-screen flex">
    <aside class="w-56 bg-zinc-950 border-r border-zinc-800/60 min-h-screen p-3 flex flex-col gap-0.5 text-sm flex-shrink-0">
        <a href="/admin" class="flex items-center gap-2 px-3 py-2 mb-3">
            <span class="text-sm font-semibold text-zinc-100">MonFlow</span>
            <span class="text-xs text-zinc-600">Admin</span>
        </a>

        <span class="text-[10px] font-semibold text-zinc-600 uppercase tracking-wider px-3 mt-2 mb-1">Navigation</span>
        <a href="/admin" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin') ? 'text-zinc-100 bg-zinc-800' : '' }}">Tableau de bord</a>
        <a href="/admin/users" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/users*') ? 'text-zinc-100 bg-zinc-800' : '' }}">Utilisateurs</a>
        <a href="/admin/plans" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/plans*') ? 'text-zinc-100 bg-zinc-800' : '' }}">Formules</a>
        <a href="/admin/subscriptions" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/subscriptions*') ? 'text-zinc-100 bg-zinc-800' : '' }}">Abonnements</a>

        <span class="text-[10px] font-semibold text-zinc-600 uppercase tracking-wider px-3 mt-4 mb-1">Finance</span>
        <a href="/admin/promos" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/promos*') ? 'text-zinc-100 bg-zinc-800' : '' }}">Codes promo</a>
        <a href="/admin/payments" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/payments*') ? 'text-zinc-100 bg-zinc-800' : '' }}">Paiements</a>
        <a href="/admin/refunds" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/refunds*') ? 'text-zinc-100 bg-zinc-800' : '' }}">Remboursements</a>

        <span class="text-[10px] font-semibold text-zinc-600 uppercase tracking-wider px-3 mt-4 mb-1">Support</span>
        <a href="/admin/tickets" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/tickets*') ? 'text-zinc-100 bg-zinc-800' : '' }}">Tickets</a>
        <a href="/admin/feedbacks" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/feedbacks*') ? 'text-zinc-100 bg-zinc-800' : '' }}">Feedbacks</a>
        <a href="/admin/newsletters" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/newsletters*') ? 'text-zinc-100 bg-zinc-800' : '' }}">Newsletters</a>

        <span class="text-[10px] font-semibold text-zinc-600 uppercase tracking-wider px-3 mt-4 mb-1">Musique</span>
        <a href="/admin/lyrics" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/lyrics*') ? 'text-zinc-100 bg-zinc-800' : '' }}">Paroles</a>
        <a href="/admin/metadata" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/metadata*') ? 'text-zinc-100 bg-zinc-800' : '' }}">Métadonnées</a>
        <a href="/admin/duplicates" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/duplicates*') ? 'text-zinc-100 bg-zinc-800' : '' }}">Doublons</a>

        <span class="text-[10px] font-semibold text-zinc-600 uppercase tracking-wider px-3 mt-4 mb-1">Système</span>
        <a href="/admin/audit-logs" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/audit-logs*') ? 'text-zinc-100 bg-zinc-800' : '' }}">Journal d'audit</a>
        <a href="/admin/settings/smtp" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/settings/smtp*') ? 'text-zinc-100 bg-zinc-800' : '' }}">Config SMTP</a>
        <a href="/admin/settings/email-templates" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/settings/email-templates*') ? 'text-zinc-100 bg-zinc-800' : '' }}">Templates email</a>

        <div class="mt-auto pt-4 border-t border-zinc-800">
            <span class="text-zinc-500 block px-3 py-1 text-xs">{{ Auth::user()->username }}</span>
            <form action="/logout" method="POST" class="px-3">@csrf<button class="text-sm text-zinc-600 hover:text-zinc-400">Déconnexion</button></form>
        </div>
    </aside>
    <main class="flex-1 p-8 overflow-auto bg-zinc-950">
        @if(session('success'))<div class="mb-5 flex items-start gap-3 bg-emerald-500/10 border border-emerald-500/20 rounded-lg p-3 text-sm text-emerald-400">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-5 flex items-start gap-3 bg-red-500/10 border border-red-500/20 rounded-lg p-3 text-sm text-red-400">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="mb-5 bg-red-500/10 border border-red-500/20 rounded-lg p-3 text-sm text-red-400 space-y-1">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        @yield('content')
    </main>
</body>
</html>

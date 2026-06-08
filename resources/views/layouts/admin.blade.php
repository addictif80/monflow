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
    <aside class="w-56 border-r min-h-screen p-3 flex flex-col gap-0.5 text-sm flex-shrink-0" style="background:rgba(7,8,16,.9);border-color:rgba(255,255,255,.07);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px)">
        <a href="/admin" class="flex items-center gap-2 px-3 py-2 mb-3">
            <img src="/icons/icon-192.png" alt="MonFlow" class="w-6 h-6 rounded-md flex-shrink-0">
            <span class="text-sm font-bold text-gradient">MonFlow</span>
            <span class="text-xs text-zinc-600 bg-zinc-800/60 px-1.5 py-0.5 rounded font-medium">Admin</span>
        </a>

        <span class="text-[10px] font-semibold text-zinc-600 uppercase tracking-wider px-3 mt-2 mb-1">Navigation</span>
        <a href="/admin" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin') ? 'text-indigo-300 bg-indigo-500/10' : '' }}">Tableau de bord</a>
        <a href="/admin/users" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/users*') ? 'text-indigo-300 bg-indigo-500/10' : '' }}">Utilisateurs</a>
        <a href="/admin/plans" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/plans*') ? 'text-indigo-300 bg-indigo-500/10' : '' }}">Formules</a>
        <a href="/admin/subscriptions" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/subscriptions*') ? 'text-indigo-300 bg-indigo-500/10' : '' }}">Abonnements</a>

        <span class="text-[10px] font-semibold text-zinc-600 uppercase tracking-wider px-3 mt-4 mb-1">Finance</span>
        <a href="/admin/promos" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/promos*') ? 'text-indigo-300 bg-indigo-500/10' : '' }}">Codes promo</a>
        <a href="/admin/payments" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/payments*') ? 'text-indigo-300 bg-indigo-500/10' : '' }}">Paiements</a>
        <a href="/admin/refunds" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/refunds*') ? 'text-indigo-300 bg-indigo-500/10' : '' }}">Remboursements</a>

        <span class="text-[10px] font-semibold text-zinc-600 uppercase tracking-wider px-3 mt-4 mb-1">Support</span>
        <a href="/admin/tickets" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/tickets*') ? 'text-indigo-300 bg-indigo-500/10' : '' }}">Tickets</a>
        <a href="/admin/feedbacks" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/feedbacks*') ? 'text-indigo-300 bg-indigo-500/10' : '' }}">Feedbacks</a>
        <a href="/admin/newsletters" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/newsletters*') ? 'text-indigo-300 bg-indigo-500/10' : '' }}">Newsletters</a>

        <span class="text-[10px] font-semibold text-zinc-600 uppercase tracking-wider px-3 mt-4 mb-1">Musique</span>
        <a href="/admin/lyrics" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/lyrics*') ? 'text-indigo-300 bg-indigo-500/10' : '' }}">Paroles</a>
        <a href="/admin/metadata" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/metadata*') ? 'text-indigo-300 bg-indigo-500/10' : '' }}">Métadonnées</a>
        <a href="/admin/duplicates" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/duplicates*') ? 'text-indigo-300 bg-indigo-500/10' : '' }}">Doublons</a>

        <span class="text-[10px] font-semibold text-zinc-600 uppercase tracking-wider px-3 mt-4 mb-1">Système</span>
        <a href="/admin/email-logs" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/email-logs*') ? 'text-indigo-300 bg-indigo-500/10' : '' }}">Journal des mails</a>
        <a href="/admin/audit-logs" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/audit-logs*') ? 'text-indigo-300 bg-indigo-500/10' : '' }}">Journal d'audit</a>
        <a href="/admin/logs" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/logs*') ? 'text-indigo-300 bg-indigo-500/10' : '' }}">Logs serveur</a>
        <a href="/admin/settings/smtp" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/settings/smtp*') ? 'text-indigo-300 bg-indigo-500/10' : '' }}">Config SMTP</a>
        <a href="/admin/settings/email-templates" class="text-sm text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50 px-3 py-2 rounded-lg flex items-center gap-2 {{ request()->is('admin/settings/email-templates*') ? 'text-indigo-300 bg-indigo-500/10' : '' }}">Templates email</a>

        <div class="mt-auto pt-4 border-t border-zinc-800">
            <span class="text-zinc-500 block px-3 py-1 text-xs">{{ Auth::user()->username }}</span>
            <form action="/logout" method="POST" class="px-3">@csrf<button class="text-sm text-zinc-600 hover:text-zinc-400">Déconnexion</button></form>
        </div>
    </aside>
    <main class="flex-1 p-8 overflow-auto" style="background:transparent">
        @if(session('success'))<div class="mb-5 flex items-start gap-3 bg-emerald-500/10 border border-emerald-500/20 rounded-lg p-3 text-sm text-emerald-400">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-5 flex items-start gap-3 bg-red-500/10 border border-red-500/20 rounded-lg p-3 text-sm text-red-400">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="mb-5 bg-red-500/10 border border-red-500/20 rounded-lg p-3 text-sm text-red-400 space-y-1">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        @yield('content')
    </main>
</body>
</html>

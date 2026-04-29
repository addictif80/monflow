<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin — MonFlow')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex">
    <aside class="w-56 bg-gray-800 border-r border-gray-700 min-h-screen p-4 flex flex-col gap-1 text-sm">
        <a href="/admin" class="text-xl font-bold text-indigo-400 mb-4 block">MonFlow Admin</a>
        <a href="/admin" class="px-3 py-2 rounded hover:bg-gray-700">Tableau de bord</a>
        <a href="/admin/users" class="px-3 py-2 rounded hover:bg-gray-700">Utilisateurs</a>
        <a href="/admin/plans" class="px-3 py-2 rounded hover:bg-gray-700">Formules</a>
        <a href="/admin/subscriptions" class="px-3 py-2 rounded hover:bg-gray-700">Abonnements</a>
        <a href="/admin/promos" class="px-3 py-2 rounded hover:bg-gray-700">Codes promo</a>
        <a href="/admin/payments" class="px-3 py-2 rounded hover:bg-gray-700">Paiements</a>
        <a href="/admin/refunds" class="px-3 py-2 rounded hover:bg-gray-700">Remboursements</a>
        <a href="/admin/tickets" class="px-3 py-2 rounded hover:bg-gray-700">Tickets</a>
        <a href="/admin/feedbacks" class="px-3 py-2 rounded hover:bg-gray-700">Feedbacks</a>
        <a href="/admin/newsletters" class="px-3 py-2 rounded hover:bg-gray-700">Newsletters</a>
        <div class="border-t border-gray-700 mt-2 pt-2">
            <a href="/admin/audit-logs" class="px-3 py-2 rounded hover:bg-gray-700 block">Journal d'audit</a>
            <a href="/admin/settings/smtp" class="px-3 py-2 rounded hover:bg-gray-700 block">Config SMTP</a>
            <a href="/admin/settings/email-templates" class="px-3 py-2 rounded hover:bg-gray-700 block">Templates email</a>
        </div>
        <div class="mt-auto pt-4 border-t border-gray-700">
            <span class="text-gray-400 block px-3 py-1">{{ Auth::user()->username }}</span>
            <form action="/logout" method="POST" class="px-3">@csrf<button class="text-red-400 hover:text-red-300">Déconnexion</button></form>
        </div>
    </aside>
    <main class="flex-1 p-8 overflow-auto">
        @if(session('success'))<div class="mb-4 p-3 bg-green-900/50 border border-green-700 rounded text-green-300">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-4 p-3 bg-red-900/50 border border-red-700 rounded text-red-300">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="mb-4 p-3 bg-red-900/50 border border-red-700 rounded text-red-300">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        @yield('content')
    </main>
</body>
</html>

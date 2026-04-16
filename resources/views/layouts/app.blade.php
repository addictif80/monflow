<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'MonFlow')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex flex-col">
    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-16">
            <a href="/portal" class="text-xl font-bold text-indigo-400">MonFlow</a>
            <div class="flex items-center gap-4 text-sm">
                <a href="/portal" class="hover:text-indigo-400">Tableau de bord</a>
                <a href="/portal/plans" class="hover:text-indigo-400">Formules</a>
                <a href="/portal/wallet" class="hover:text-indigo-400">Portefeuille</a>
                <a href="/portal/payments" class="hover:text-indigo-400">Paiements</a>
                <a href="/portal/devices" class="hover:text-indigo-400">Appareils</a>
                <a href="/support/tickets" class="hover:text-indigo-400">Support</a>
                <a href="/player" class="hover:text-indigo-400 text-indigo-300 font-medium">Lecteur</a>
                <a href="/portal/deemix" class="hover:text-indigo-400 text-indigo-300 font-medium">Deemix</a>
                <span class="text-gray-400">|</span>
                <a href="/portal/profile" class="text-gray-300 hover:text-indigo-400">{{ Auth::user()->username }}</a>
                <form action="/logout" method="POST" class="inline">@csrf<button class="hover:text-red-400">Déconnexion</button></form>
            </div>
        </div>
    </nav>
    <main class="flex-1 max-w-7xl mx-auto w-full px-4 py-8">
        @if(session('success'))<div class="mb-4 p-3 bg-green-900/50 border border-green-700 rounded text-green-300">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-4 p-3 bg-red-900/50 border border-red-700 rounded text-red-300">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="mb-4 p-3 bg-red-900/50 border border-red-700 rounded text-red-300">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        @yield('content')
    </main>
    <footer class="bg-gray-800 border-t border-gray-700 py-4 text-center text-gray-500 text-sm">MonFlow &copy; {{ date('Y') }}</footer>
</body>
</html>
